<?php

class UsersController extends ApiController
{

    protected $user_mapper;

    public function getAction($request, $db)
    {
        $user_id = $this->getItemId($request);

        // verbosity
        $verbose = $this->getVerbosity($request);

        // pagination settings
        $start          = $this->getStart($request);
        $resultsperpage = $this->getResultsPerPage($request);

        if (isset($request->url_elements[4])) {
            switch ($request->url_elements[4]) {
                case 'talks':
                    $talk_mapper = new TalkMapper($db, $request);
                    $talks       = $talk_mapper->getTalksBySpeaker($user_id, $resultsperpage, $start);
                    $list        = $talks->getOutputView($request, $verbose);
                    break;
                case 'hosted':
                    $event_mapper = new EventMapper($db, $request);
                    $list         = $event_mapper->getEventsHostedByUser($user_id, $resultsperpage, $start, $verbose);
                    break;
                case 'attended':
                    $event_mapper = new EventMapper($db, $request);
                    $list         = $event_mapper->getEventsAttendedByUser($user_id, $resultsperpage, $start, $verbose);
                    break;
                case 'talk_comments':
                    $talkComment_mapper = new TalkCommentMapper($db, $request);
                    $list               = $talkComment_mapper->getCommentsByUserId(
                        $user_id,
                        $resultsperpage,
                        $start,
                        $verbose
                    );
                    break;
                default:
                    throw new InvalidArgumentException('Unknown Subrequest', 404);
                    break;
            }
        } else {
            $mapper = new UserMapper($db, $request);
            if ($user_id) {
                $list = $mapper->getUserById($user_id, $verbose);
                if (count($list['users']) == 0) {
                    throw new Exception('User not found', 404);
                }
            } else {
                if (isset($request->parameters['username'])) {
                    $username = filter_var(
                        $request->parameters['username'],
                        FILTER_SANITIZE_STRING,
                        FILTER_FLAG_NO_ENCODE_QUOTES
                    );
                    $list     = $mapper->getUserByUsername($username, $verbose);
                    if ($list === false) {
                        throw new Exception('Username not found', 404);
                    }
                } else {
                    $list = $mapper->getUserList($resultsperpage, $start, $verbose);
                }
            }
        }

        return $list;
    }

    public function postAction($request, $db)
    {
        // check element 3, there's no user associated with the not-logged-in collections
        if (isset($request->url_elements[3])) {
            switch ($request->url_elements[3]) {
                case 'verifications':
                    $user_mapper = new UserMapper($db, $request);
                    $token       = filter_var($request->getParameter("token"), FILTER_SANITIZE_STRING);
                    if (empty($token)) {
                        throw new Exception("Verification token must be supplied", 400);
                    } else {
                        $success = $user_mapper->verifyUser($token);
                        if ($success) {
                            $view = $request->getView();
                            $view->setHeader('Content-Length', 0);
                            $view->setResponseCode(204);
                            return;

                        } else {
                            throw new Exception("Verification failed", 400);
                        }
                    }
                    break;
                default:
                    throw new InvalidArgumentException('Unknown Subrequest', 404);
                    break;
            }
        } else {
            $user   = array();
            $errors = array();

            $user_mapper = new UserMapper($db, $request);

            // Required Fields
            $user['username'] = filter_var(trim(
                $request->getParameter("username")),
                FILTER_SANITIZE_STRING,
                FILTER_FLAG_NO_ENCODE_QUOTES
            );
            if (empty($user['username'])) {
                $errors[] = "'username' is a required field";
            } else {
                // does anyone else have this username?
                $existing_user = $user_mapper->getUserByUsername($user['username']);
                if ($existing_user['users']) {
                    $errors[] = "That username is already in use. Choose another";
                }
            }

            $user['full_name'] = filter_var(
                trim($request->getParameter("full_name")),
                FILTER_SANITIZE_STRING,
                FILTER_FLAG_NO_ENCODE_QUOTES
            );
            if (empty($user['full_name'])) {
                $errors[] = "'full_name' is a required field";
            }

            $user['email'] = filter_var(
                trim($request->getParameter("email")),
                FILTER_VALIDATE_EMAIL,
                FILTER_FLAG_NO_ENCODE_QUOTES
            );
            if (empty($user['email'])) {
                $errors[] = "A valid entry for 'email' is required";
            } else {
                // does anyone else have this email?
                $existing_user = $user_mapper->getUserByEmail($user['email']);
                if ($existing_user['users']) {
                    $errors[] = "That email is already associated with another account";
                }
            }

            $password = $request->getParameter("password");
            if (empty($password)) {
                $errors[] = "'password' is a required field";
            } else {
                // check it's sane
                $validity = $user_mapper->checkPasswordValidity($password);
                if (true === $validity) {
                    // OK good, go ahead
                    $user['password'] = $password;
                } else {
                    // the password wasn't acceptable, tell the user why
                    $errors = array_merge($errors, $validity);
                }
            }

            // Optional Fields
            $user['twitter_username'] = filter_var(
                trim($request->getParameter("twitter_username")),
                FILTER_SANITIZE_STRING,
                FILTER_FLAG_NO_ENCODE_QUOTES
            );

            // How does it look?  With no errors, we can proceed
            if ($errors) {
                throw new Exception(implode(". ", $errors), 400);
            } else {
                $user_id = $user_mapper->createUser($user);
                $view = $request->getView();
                $view->setHeader('Location', $request->base . $request->path_info . '/' . $user_id);
                $view->setResponseCode(201);

                // autoverify for test platforms
                if (isset($this->config['features']['allow_auto_verify_users'])
                    && $this->config['features']['allow_auto_verify_users']
                ) {
                    if ($request->getParameter("auto_verify_user") == "true") {
                        // the test suite sends this extra field, if we got
                        // this far then this platform supports this
                        $user_mapper->verifyThisTestUser($user_id);
                    }
                }

                // Generate a verification token and email it to the user
                $token = $user_mapper->generateEmailVerificationTokenForUserId($user_id);

                $recipients   = array($user['email']);
                $emailService = new UserRegistrationEmailService($this->config, $recipients, $token);
                $emailService->sendEmail();

                return;
            }
        }
    }

    /**
     * Allow a user to edit their own record
     *
     * @param Request $request the request.
     * @param         $db      the database.
     *
     * @return mixed
     */
    public function updateUser(Request $request, $db)
    {
        if (false == ($request->getUserId())) {
            throw new Exception("You must be logged in to change a user account", 401);
        }

        $userId = $this->getItemId($request);

        $user_mapper = new UserMapper($db, $request);
        if ($user_mapper->thisUserHasAdminOn($userId)) {
            $oauthModel  = $request->getOauthModel($db);
            $accessToken = $request->getAccessToken();

            // only trusted clients can change account details
            if (! $oauthModel->isAccessTokenPermittedPasswordGrant($accessToken)) {
                throw new Exception("This client does not have permission to perform this operation", 403);
            }

            // start building up a representation of the user
            $user   = array("user_id" => $userId);
            $errors = array();

            // start with passwords
            $password = $request->getParameter('password');
            if (! empty($password)) {
                // they must supply their old password to be allowed to set a new one
                $old_password = $request->getParameter('old_password');
                if (empty($old_password)) {
                    throw new Exception('The field "old_password" is needed to update a user password', 400);
                }

                // is the old password correct before we proceed?
                if (! $oauthModel->reverifyUserPassword($userId, $old_password)) {
                    throw new Exception("The credentials could not be verified", 403);
                }

                $validity = $user_mapper->checkPasswordValidity($password);
                if (true === $validity) {
                    // OK good, go ahead
                    $user['password'] = $password;
                } else {
                    // the password wasn't acceptable, tell the user why
                    $errors = array_merge($errors, $validity);
                }
            }

            $user['full_name'] = filter_var(
                trim($request->getParameter("full_name")),
                FILTER_SANITIZE_STRING,
                FILTER_FLAG_NO_ENCODE_QUOTES
            );
            if (empty($user['full_name'])) {
                $errors[] = "'full_name' is a required field";
            }

            $user['email'] = filter_var(
                trim($request->getParameter("email")),
                FILTER_VALIDATE_EMAIL,
                FILTER_FLAG_NO_ENCODE_QUOTES
            );
            if (empty($user['email'])) {
                $errors[] = "A valid entry for 'email' is required";
            } else {
                // does anyone else have this email?
                $existing_user = $user_mapper->getUserByEmail($user['email']);
                if ($existing_user['users']) {
                    // yes but is that our existing user being found?
                    $old_user = $user_mapper->getUserById($userId);
                    if ($old_user['users'][0]['uri'] != $existing_user['users'][0]['uri']) {
                        // the email address exists and not on this user's account
                        $errors[] = "That email is already associated with another account";
                    }
                }
            }

            $username = $request->getParameter("username", false);
            if (false !== $username) {
                $user['username'] = filter_var(
                    trim($username),
                    FILTER_SANITIZE_STRING,
                    FILTER_FLAG_NO_ENCODE_QUOTES
                );
                // does anyone else have this username?
                $existing_user = $user_mapper->getUserByUsername($user['username']);
                if ($existing_user['users']) {
                    // yes but is that our existing user being found?
                    $old_user = $user_mapper->getUserById($userId);
                    if ($old_user['users'][0]['uri'] != $existing_user['users'][0]['uri']) {
                        // the username exists and not on this user's account
                        $errors[] = "That username is already associated with another account";
                    }
                }
            }

            // Optional Fields
            $twitter_username = $request->getParameter("twitter_username", false);
            if (false !== $twitter_username) {
                $user['twitter_username'] = filter_var(
                    trim($twitter_username),
                    FILTER_SANITIZE_STRING,
                    FILTER_FLAG_NO_ENCODE_QUOTES
                );
            }

            if ($errors) {
                throw new Exception(implode(". ", $errors), 400);
            } else {
                // now update the user
                if (! $user_mapper->editUser($user, $userId)) {
                    throw new Exception("User not updated", 400);
                }

                // we're good!
                $view = $request->getView();
                $view->setHeader('Content-Length', 0);
                $view->setResponseCode(204);
                return;

            }
        }
        throw new Exception("Could not update user", 400);
    }

    public function passwordReset(Request $request, $db)
    {
        $token = filter_var($request->getParameter("token"), FILTER_SANITIZE_STRING);
        if (empty($token)) {
            throw new Exception("Reset token must be supplied", 400);
        }

        $password = $request->getParameter("password");
        if (empty($password)) {
            throw new Exception("New password must be supplied", 400);
        }
        // now check the password complies with our rules
        $user_mapper = new UserMapper($db, $request);
        $validity    = $user_mapper->checkPasswordValidity($password);
        if (true === $validity) {
            // OK, go ahead
            $success = $user_mapper->resetPassword($token, $password);
            if ($success) {
                $view = $request->getView();
                $view->setHeader('Content-Length', 0);
                $view->setResponseCode(204);
                return;
            } else {
                throw new Exception("Password could not be reset", 400);
            }
        } else {
            // the password wasn't acceptable, tell the user why
            throw new Exception(implode(". ", $validity), 400);
        }

    }


    public function deleteUser($request, $db)
    {
        if (! isset($request->user_id)) {
            throw new Exception("You must be logged in to delete data", 401);
        }
        // delete the user
        $user_id = $this->getItemId($request);

        $user_mapper = $this->getUserMapper($db, $request);

        $is_admin = $user_mapper->thisUserHasAdminOn($user_id);
        if (! $is_admin) {
            throw new Exception("You do not have permission to do that", 403);
        }

        if (! $user_mapper->delete($user_id)) {
            throw new Exception("There was a problem trying to delete the user", 400);
        }

        $view = $request->getView();
        $view->setHeader('Content-Length', 0);
        $view->setResponseCode(204);
    }

    public function setUserMapper(UserMapper $user_mapper)
    {
        $this->user_mapper = $user_mapper;
    }

    public function getUserMapper($db, $request)
    {
        if (! $this->user_mapper) {
            $this->user_mapper = new UserMapper($db, $request);
        }

        return $this->user_mapper;
    }
}
