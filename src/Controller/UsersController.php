<?php

namespace Joindin\Api\Controller;

use Exception;
use InvalidArgumentException;
use Joindin\Api\Exception\AuthenticationException;
use Joindin\Api\Exception\AuthorizationException;
use Joindin\Api\Model\EventMapper;
use Joindin\Api\Model\TalkCommentMapper;
use Joindin\Api\Model\TalkMapper;
use Joindin\Api\Model\UserMapper;
use Joindin\Api\Service\UserRegistrationEmailService;
use PDO;
use Joindin\Api\Request;
use Teapot\StatusCode\Http;

class UsersController extends BaseApiController
{
    protected ?UserMapper $userMapper;

    private ?UserRegistrationEmailService $userRegistrationEmailService;

    private TalkCommentMapper $talkCommentMapper;

    /**
     * @throws Exception
     */
    public function getAction(Request $request, PDO $db): array|false
    {
        try {
            $userId = $this->getItemId($request, 'User not found');
        } catch (\Exception) {
            $userId = false;
        }

        // verbosity
        $verbose = $this->getVerbosity($request);

        // pagination settings
        $start          = $this->getStart($request);
        $resultsperpage = $this->getResultsPerPage($request);

        if ($userId && isset($request->url_elements[4])) {
            switch ($request->url_elements[4]) {
                case 'talks':
                    $talkMapper = new TalkMapper($db, $request);
                    $talks       = $talkMapper->getTalksBySpeaker($userId, $resultsperpage, $start, $verbose);

                    return $talks->getOutputView($request, $verbose);
                case 'hosted':
                    $eventMapper = new EventMapper($db, $request);

                    return $eventMapper->getEventsHostedByUser($userId, $resultsperpage, $start, $verbose);
                case 'attended':
                    $eventMapper = new EventMapper($db, $request);

                    return $eventMapper->getEventsAttendedByUser($userId, $resultsperpage, $start, $verbose);
                case 'talk_comments':
                    $talkCommentMapper = new TalkCommentMapper($db, $request);

                    return $talkCommentMapper->getCommentsByUserId(
                        $userId,
                        $resultsperpage,
                        $start,
                        $verbose
                    );

                default:
                    throw new InvalidArgumentException('Unknown Subrequest', Http::NOT_FOUND);
            }
        }

        $mapper = new UserMapper($db, $request);

        if ($userId) {
            return $mapper->getUserById($userId, $verbose)
                ?: throw new Exception('User not found', Http::NOT_FOUND);
        }

        if (isset($request->parameters['username'])) {
            $username = htmlspecialchars(
                $request->parameters['username'],
                ENT_NOQUOTES
            );
            $list = $mapper->getUserByUsername($username, $verbose);

            if ($list === false) {
                throw new Exception('Username not found', Http::NOT_FOUND);
            }

            return $list;
        }

        if (isset($request->parameters['keyword'])) {
            $keyword = htmlspecialchars(
                $request->parameters['keyword'],
                ENT_NOQUOTES
            );

            return $mapper->getUserByKeyword($keyword, $resultsperpage, $start, $verbose);
        }

        return $mapper->getUserList($resultsperpage, $start, $verbose);
    }

    public function postAction(Request $request, PDO $db): void
    {
        // check element 3, there's no user associated with the not-logged-in collections
        if (isset($request->url_elements[3])) {
            switch ($request->url_elements[3]) {
                case 'verifications':
                    $userMapper = new UserMapper($db, $request);
                    $token       = htmlspecialchars($request->getStringParameter("token"));

                    if (empty($token)) {
                        throw new Exception("Verification token must be supplied", Http::BAD_REQUEST);
                    }

                    $success = $userMapper->verifyUser($token);

                    if ($success) {
                        $view = $request->getView();
                        $view->setHeader('Content-Length', '0');
                        $view->setResponseCode(Http::NO_CONTENT);

                        return;
                    }

                    throw new Exception("Verification failed", Http::BAD_REQUEST);

                default:
                    throw new InvalidArgumentException('Unknown Subrequest', Http::NOT_FOUND);
            }
        } else {
            $user   = [];
            $errors = [];

            $userMapper = $this->getUserMapper($db, $request);

            // Required Fields
            $user['username'] = htmlspecialchars(
                trim($request->getStringParameter("username")),
                ENT_NOQUOTES
            );

            if (empty($user['username'])) {
                $errors[] = "'username' is a required field";
            } else {
                // does anyone else have this username?
                $existingUser = $userMapper->getUserByUsername($user['username']);

                if (is_array($existingUser) && array_key_exists('users', $existingUser)) {
                    if (count($existingUser['users']) > 0) {
                        $errors[] = "That username is already in use. Choose another";
                    }
                }
            }

            $user['full_name'] = htmlspecialchars(
                trim($request->getStringParameter("full_name")),
                ENT_NOQUOTES
            );

            if (empty($user['full_name'])) {
                $errors[] = "'full_name' is a required field";
            }

            $user['email'] = filter_var(
                trim($request->getStringParameter("email")),
                FILTER_VALIDATE_EMAIL,
                FILTER_FLAG_NO_ENCODE_QUOTES
            );

            if (empty($user['email'])) {
                $errors[] = "A valid entry for 'email' is required";
            } else {
                // does anyone else have this email?
                $existingUser = $userMapper->getUserByEmail($user['email']);

                if (is_array($existingUser) && array_key_exists('users', $existingUser)) {
                    if (count($existingUser['users']) > 0) {
                        $errors[] = "That email is already associated with another account";
                    }
                }
            }

            $password = $request->getStringParameter("password");

            if (empty($password)) {
                $errors[] = "'password' is a required field";
            } else {
                // check it's sane
                $validity = $userMapper->checkPasswordValidity($password);

                if (true === $validity) {
                    // OK good, go ahead
                    $user['password'] = $password;
                } else {
                    // the password wasn't acceptable, tell the user why
                    $errors = array_merge($errors, $validity);
                }
            }

            // Optional Fields
            $user['twitter_username'] = htmlspecialchars(
                trim($request->getStringParameter("twitter_username")),
                ENT_NOQUOTES
            );
            $user['biography']        = htmlspecialchars(
                trim($request->getStringParameter("biography")),
                ENT_NOQUOTES
            );

            // How does it look?  With no errors, we can proceed
            if ($errors) {
                throw new Exception(implode(". ", $errors), Http::BAD_REQUEST);
            }

            $userId = $userMapper->createUser($user);
            $view    = $request->getView();
            $view->setHeader('Location', $request->base . $request->path_info . '/' . $userId);
            $view->setResponseCode(Http::CREATED);

            // autoverify for test platforms
            if (
                isset($this->config['features']['allow_auto_verify_users'])
                && $this->config['features']['allow_auto_verify_users']
            ) {
                if ($request->getParameter("auto_verify_user") == "true") {
                    // the test suite sends this extra field, if we got
                    // this far then this platform supports this
                    $userMapper->verifyThisTestUser($userId);
                }
            }

            // Generate a verification token and email it to the user
            $token = $userMapper->generateEmailVerificationTokenForUserId($userId);

            $recipients   = [$user['email']];
            $emailService = $this->getUserRegistrationEmailService($this->config, $recipients, $token);
            $emailService->sendEmail();
        }
    }

    /**
     * Allow a user to edit their own record
     *
     * @param Request $request the request.
     * @param PDO     $db      the database.
     *
     * @throws Exception
     * @return void
     */
    public function updateUser(Request $request, PDO $db)
    {
        if (null === ($request->getUserId())) {
            throw new Exception("You must be logged in to change a user account", Http::UNAUTHORIZED);
        }

        $userId = $this->getItemId($request, 'User not found');

        $userMapper = $this->getUserMapper($db, $request);

        if (!$userMapper->thisUserHasAdminOn($userId)) {
            throw new Exception("Could not update user", Http::BAD_REQUEST);
        }
        $oauthModel  = $request->getOauthModel($db);
        $accessToken = $request->getAccessToken();

        // only trusted clients can change account details
        if (!$accessToken || !$oauthModel->isAccessTokenPermittedPasswordGrant($accessToken)) {
            throw new Exception("This client does not have permission to perform this operation", Http::FORBIDDEN);
        }

        // start building up a representation of the user
        $user   = ["user_id" => $userId];
        $errors = [];

        // start with passwords
        $password = $request->getStringParameter('password');

        if (!empty($password)) {
            // they must supply their old password to be allowed to set a new one
            $oldPassword = $request->getStringParameter('old_password');

            if (empty($oldPassword)) {
                throw new Exception(
                    'The field "old_password" is needed to update a user password',
                    Http::BAD_REQUEST
                );
            }

            // is the old password correct before we proceed?
            if (!$oauthModel->reverifyUserPassword($userId, $oldPassword)) {
                throw new Exception("The credentials could not be verified", Http::FORBIDDEN);
            }

            $validity = $userMapper->checkPasswordValidity($password);

            if (true === $validity) {
                // OK good, go ahead
                $user['password'] = $password;
            } else {
                // the password wasn't acceptable, tell the user why
                $errors = array_merge($errors, $validity);
            }
        }

        $user['full_name'] = htmlspecialchars(
            trim($request->getStringParameter("full_name")),
            ENT_NOQUOTES
        );

        if (empty($user['full_name'])) {
            $errors[] = "'full_name' is a required field";
        }

        $user['email'] = filter_var(
            trim($request->getStringParameter("email")),
            FILTER_VALIDATE_EMAIL,
            FILTER_FLAG_NO_ENCODE_QUOTES
        );

        if (empty($user['email'])) {
            $errors[] = "A valid entry for 'email' is required";
        } else {
            // does anyone else have this email?
            $existingUser = $userMapper->getUserByEmail($user['email']);

            if (is_array($existingUser) && array_key_exists('users', $existingUser)) {
                // yes but is that our existing user being found?
                $oldUser = $userMapper->getUserById($userId);

                if ($oldUser && $oldUser['users'][0]['uri'] != $existingUser['users'][0]['uri']) {
                    // the email address exists and not on this user's account
                    $errors[] = "That email is already associated with another account";
                }
            }
        }

        $username = $request->getStringParameter("username");

        if ($username) {
            $user['username'] = htmlspecialchars(
                trim($username),
                ENT_NOQUOTES
            );
            // does anyone else have this username?
            $existingUser = $userMapper->getUserByUsername($user['username']);

            if (is_array($existingUser) && array_key_exists('users', $existingUser) && $existingUser['users'] !== []) {
                // yes but is that our existing user being found?
                $oldUser = $userMapper->getUserById($userId);

                if ($oldUser && $oldUser['users'][0]['uri'] != $existingUser['users'][0]['uri']) {
                    // the username exists and not on this user's account
                    $errors[] = "That username is already associated with another account";
                }
            }
        }

        // Optional Fields
        $twitterUsername = $request->getStringParameter("twitter_username");

        if ($twitterUsername) {
            $user['twitter_username'] = htmlspecialchars(
                trim($twitterUsername),
                ENT_NOQUOTES
            );
        }
        $biography = $request->getStringParameter("biography");

        if ($biography) {
            $user['biography'] = htmlspecialchars(
                trim($biography),
                ENT_NOQUOTES
            );
        }

        if ($errors) {
            throw new Exception(implode(". ", $errors), Http::BAD_REQUEST);
        }

        // now update the user
        if (!$userMapper->editUser($user, $userId)) {
            throw new Exception("User not updated", Http::BAD_REQUEST);
        }

        // we're good!
        $view = $request->getView();
        $view->setHeader('Content-Length', '0');
        $view->setResponseCode(Http::NO_CONTENT);
    }

    public function passwordReset(Request $request, PDO $db): void
    {
        $token = htmlspecialchars($request->getStringParameter("token"));

        if (empty($token)) {
            throw new Exception("Reset token must be supplied", Http::BAD_REQUEST);
        }

        $password = $request->getStringParameter("password");

        if (empty($password)) {
            throw new Exception("New password must be supplied", Http::BAD_REQUEST);
        }
        // now check the password complies with our rules
        $userMapper = new UserMapper($db, $request);
        $validity    = $userMapper->checkPasswordValidity($password);

        if (true !== $validity) {
            // the password wasn't acceptable, tell the user why
            throw new Exception(implode(". ", $validity), Http::BAD_REQUEST);
        }

        // OK, go ahead
        $success = $userMapper->resetPassword($token, $password);

        if (!$success) {
            throw new Exception("Password could not be reset", Http::BAD_REQUEST);
        }

        $view = $request->getView();
        $view->setHeader('Content-Length', '0');
        $view->setResponseCode(Http::NO_CONTENT);
    }

    /**
     * @throws Exception
     */
    public function deleteTalkComments(Request $request, PDO $db): void
    {
        if (null === ($request->getUserId())) {
            throw AuthenticationException::forUnauthenticatedUser();
        }

        $userId = $request->getUserId();

        $userMapper = $this->getUserMapper($db, $request);

        if (!$userMapper->isSiteAdmin($userId)) {
            throw AuthorizationException::forNonAdministrator();
        }

        $this->initializeTalkCommentMapper($db, $request);
        $this->talkCommentMapper->deleteCommentsForUser($this->getItemId($request, 'User not found'));

        $view = $request->getView();
        $view->setHeader('Content-Length', '0');
        $view->setResponseCode(Http::NO_CONTENT);
    }

    public function deleteUser(Request $request, PDO $db): void
    {
        if (!isset($request->user_id)) {
            throw new Exception("You must be logged in to delete data", Http::UNAUTHORIZED);
        }

        $userMapper = $this->getUserMapper($db, $request);

        $isAdmin = $userMapper->isSiteAdmin($request->user_id);

        if (!$isAdmin) {
            throw new Exception("You do not have permission to do that", Http::FORBIDDEN);
        }

        if (!$userMapper->delete($this->getItemId($request, 'User not found'))) {
            throw new Exception("There was a problem trying to delete the user", Http::BAD_REQUEST);
        }

        $view = $request->getView();
        $view->setHeader('Content-Length', '0');
        $view->setResponseCode(Http::NO_CONTENT);
    }

    /**
     * Allow users to be set as trusted
     *
     * @param Request $request
     * @param PDO $db
     *
     * @throws Exception
     */
    public function setTrusted(Request $request, PDO $db): void
    {
        if (null === ($request->getUserId())) {
            throw new Exception("You must be logged in to change a user account", Http::UNAUTHORIZED);
        }

        $userMapper = $this->getUserMapper($db, $request);

        if (!$userMapper->isSiteAdmin($request->getUserId())) {
            throw new Exception("You must be an admin to change a user's trusted state", Http::FORBIDDEN);
        }

        $userId = $this->getItemId($request, 'User not found');

        if (!is_bool($trustedStatus = $request->getParameter("trusted", null))) {
            throw new Exception("You must provide a trusted state", Http::BAD_REQUEST);
        }

        if (!$userMapper->setTrustedStatus($trustedStatus, $userId)) {
            throw new Exception("Unable to update status", Http::INTERNAL_SERVER_ERROR);
        }
        $view = $request->getView();
        $view->setHeader('Content-Length', '0');
        $view->setResponseCode(Http::NO_CONTENT);
    }

    public function setUserMapper(UserMapper $userMapper): void
    {
        $this->userMapper = $userMapper;
    }

    public function getUserMapper(PDO $db, Request $request): UserMapper
    {
        if (!isset($this->userMapper)) {
            $this->userMapper = new UserMapper($db, $request);
        }

        return $this->userMapper;
    }

    public function setTalkCommentMapper(TalkCommentMapper $talkCommentMapper): void
    {
        $this->talkCommentMapper = $talkCommentMapper;
    }

    public function initializeTalkCommentMapper(PDO $db, Request $request): void
    {
        if (!isset($this->talkCommentMapper)) {
            $this->talkCommentMapper = new TalkCommentMapper($db, $request);
        }
    }

    public function setUserRegistrationEmailService(UserRegistrationEmailService $mailService): void
    {
        $this->userRegistrationEmailService = $mailService;
    }

    public function getUserRegistrationEmailService(array $config, array $recipient, string $token): UserRegistrationEmailService
    {
        if (!isset($this->userRegistrationEmailService)) {
            $this->userRegistrationEmailService = new UserRegistrationEmailService(
                $config,
                $recipient,
                $token
            );
        }

        return $this->userRegistrationEmailService;
    }
}
