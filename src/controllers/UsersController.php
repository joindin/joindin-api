<?php

class UsersController extends ApiController {

    /**
     * {@inheritdoc}
     */
    public function handle(Request $request, $db) {
        // only GET is implemented so far
        if($request->getVerb() == 'GET') {
            return $this->getAction($request, $db);
        } elseif ($request->getVerb() == 'POST') {
            return $this->postAction($request, $db);
        }
        
        throw new Exception('Endpoint not found', 404);
    }

	public function getAction($request, $db) {
        $user_id = $this->getItemId($request);

        // verbosity
        $verbose = $this->getVerbosity($request);

        // pagination settings
        $start = $this->getStart($request);
        $resultsperpage = $this->getResultsPerPage($request);

        if(isset($request->url_elements[4])) {
            switch($request->url_elements[4]) {
                case 'talks':
                            $talk_mapper = new TalkMapper($db, $request);
                            $list = $talk_mapper->getTalksBySpeaker($user_id, $resultsperpage, $start, $verbose);
                            break;
                case 'hosted':
                            $event_mapper = new EventMapper($db, $request);
                            $list = $event_mapper->getEventsHostedByUser($user_id, $resultsperpage, $start, $verbose);
                            break;
                case 'attended':
                            $event_mapper = new EventMapper($db, $request);
                            $list = $event_mapper->getEventsAttendedByUser($user_id, $resultsperpage, $start, $verbose);
                            break;
                case 'talk_comments':
                            $talkComment_mapper = new TalkCommentMapper($db, $request);
                            $list = $talkComment_mapper->getCommentsByUserId($user_id, $resultsperpage, $start, $verbose);
                            break;
                default:
                            throw new InvalidArgumentException('Unknown Subrequest', 404);
                            break;
            }
        } else {
            $mapper = new UserMapper($db, $request);
            if($user_id) {
                $list = $mapper->getUserById($user_id, $verbose);
                if(count($list['users']) == 0) {
                    throw new Exception('User not found', 404);
                }
            } else {
                if(isset($request->parameters['username'])) {
                    $username = filter_var($request->parameters['username'], FILTER_SANITIZE_STRING);
                    $list = $mapper->getUserByUsername($username, $verbose);
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

    public function postAction($request, $db){
        // check element 3, there's no user associated with the not-logged-in collections
        if(isset($request->url_elements[3])) {
            switch($request->url_elements[3]) {
                case 'verifications':
                            $user_mapper= new UserMapper($db, $request);
                            $token = filter_var($request->getParameter("token"), FILTER_SANITIZE_STRING);
                            if(empty($token)) {
                                throw new Exception("Verification token must be supplied", 400);
                            } else {
                                $success = $user_mapper->verifyUser($token);
                                if($success) {
                                    header("Content-Length: 0", NULL, 204);
                                    exit; // no more content
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
            $user = array();
            $errors = array();

            $user_mapper= new UserMapper($db, $request);

            // Required Fields
            $user['username'] = filter_var(trim($request->getParameter("username")), FILTER_SANITIZE_STRING);
            if(empty($user['username'])) {
                $errors[] = "'username' is a required field";
            } else {
                // does anyone else have this username?
                $existing_user = $user_mapper->getUserByUsername($user['username']);
                if($existing_user['users']) {
                    $errors[] = "That username is already in use. Choose another";
                }
            }

            $user['full_name'] = filter_var(trim($request->getParameter("full_name")), FILTER_SANITIZE_STRING);
            if(empty($user['full_name'])) {
                $errors[] = "'full_name' is a required field";
            }

            $user['email'] = filter_var(trim($request->getParameter("email")), FILTER_VALIDATE_EMAIL);
            if(empty($user['email'])) {
                $errors[] = "A valid entry for 'email' is required";
            } else {
                // does anyone else have this email?
                $existing_user = $user_mapper->getUserByEmail($user['email']);
                if($existing_user['users']) {
                    $errors[] = "That email is already associated with another account";
                }
            }

            $password = $request->getParameter("password");
            if(empty($password)) {
                $errors[] = "'password' is a required field";
            } else {
                // check it's sane
                $validity = $user_mapper->checkPasswordValidity($password);
                if(true === $validity) {
                    // OK good, go ahead
                    $user['password'] = $password;
                } else {
                    // the password wasn't acceptable, tell the user why
                    $errors = array_merge($errors, $validity);
                }
            }

            // Optional Fields
            $user['twitter_username'] = filter_var(trim($request->getParameter("twitter_username")), FILTER_SANITIZE_STRING);

            // How does it look?  With no errors, we can proceed
            if($errors) {
                throw new Exception(implode(". ", $errors), 400);
            } else {
                $user_id = $user_mapper->createUser($user);
                header("Location: " . $request->base . $request->path_info . '/' . $user_id, NULL, 201);

                // Generate a verification token and email it to the user
                $token = $user_mapper->generateEmailVerificationTokenForUserId($user_id);

                $recipients = array($user['email']);
                $emailService = new UserRegistrationEmailService($this->config, $recipients, $token);
                $emailService->sendEmail();
                exit;
            }
        }
    }
}
