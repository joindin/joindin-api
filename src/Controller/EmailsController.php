<?php

namespace Joindin\Api\Controller;

use Exception;
use Joindin\Api\Model\UserMapper;
use Joindin\Api\Service\UserPasswordResetEmailService;
use Joindin\Api\Service\UserRegistrationEmailService;
use Joindin\Api\Service\UserUsernameReminderEmailService;
use PDO;
use Joindin\Api\Request;
use Teapot\StatusCode\Http;

/**
 * Actions here deal with endpoints that trigger emails to be sent
 */
class EmailsController extends BaseApiController
{
    public function verifications(Request $request, PDO $db): void
    {
        $user_mapper = new UserMapper($db, $request);
        $email       = filter_var($request->getParameter("email"), FILTER_VALIDATE_EMAIL);

        if (empty($email)) {
            throw new Exception("The email address must be supplied", Http::BAD_REQUEST);
        }

        // need the user's ID rather than the representation
        $user_id = $user_mapper->getUserIdFromEmail($email);

        if (!$user_id) {
            throw new Exception("Can't find that email address", Http::BAD_REQUEST);
        }

        // Generate a verification token and email it to the user
        $token = $user_mapper->generateEmailVerificationTokenForUserId($user_id);

        $recipients   = [$email];
        $emailService = new UserRegistrationEmailService($this->config, $recipients, $token);
        $emailService->sendEmail();

        $view = $request->getView();
        $view->setHeader('Content-Length', '0');
        $view->setResponseCode(Http::ACCEPTED);
    }

    public function usernameReminder(Request $request, PDO $db): void
    {
        $user_mapper = new UserMapper($db, $request);
        $email       = filter_var($request->getParameter("email"), FILTER_VALIDATE_EMAIL);

        if (empty($email)) {
            throw new Exception("The email address must be supplied", Http::BAD_REQUEST);
        }

        $list = $user_mapper->getUserByEmail($email);

        if (!is_array($list) || !count($list['users'])) {
            throw new Exception("Can't find that email address", Http::BAD_REQUEST);
        }

        $user = $list['users'][0];

        $recipients   = [$email];
        $emailService = new UserUsernameReminderEmailService($this->config, $recipients, $user);
        $emailService->sendEmail();

        $view = $request->getView();
        $view->setHeader('Content-Length', '0');
        $view->setResponseCode(Http::ACCEPTED);
    }

    public function passwordReset(Request $request, PDO $db): void
    {
        $user_mapper = new UserMapper($db, $request);
        $username    = htmlspecialchars($request->getStringParameter("username"));

        if (empty($username)) {
            throw new Exception("A username must be supplied", Http::BAD_REQUEST);
        }

        $list = $user_mapper->getUserByUsername($username);

        if (!is_array($list) || !count($list['users'])) {
            throw new Exception("Can't find that user", Http::BAD_REQUEST);
        }

        $user = $list['users'][0];

        // neither user_id nor email are in the user resource returned by the mapper
        $user_id    = $user_mapper->getUserIdFromUsername($username);

        if (!$user_id) {
            throw new Exception("Unable retrieve user id", Http::BAD_REQUEST);
        }
        $email      = $user_mapper->getEmailByUserId($user_id);
        $recipients = [$email];

        // we need a token to send so we know it is a valid reset
        $token = $user_mapper->generatePasswordResetTokenForUserId($user_id);

        if (!$token) {
            throw new Exception("Unable to generate a reset token", Http::BAD_REQUEST);
        }

        $emailService = new UserPasswordResetEmailService($this->config, $recipients, $user, $token);
        $emailService->sendEmail();

        $view = $request->getView();
        $view->setHeader('Content-Length', '0');
        $view->setResponseCode(Http::ACCEPTED);
    }
}
