<?php

/**
 * Actions here deal with endpoints that trigger emails to be sent
 */
class EmailsController extends ApiController
{
    public function verifications($request, $db)
    {
        $user_mapper = new UserMapper($db, $request, $this->config['website_url']);
        $email       = filter_var($request->getParameter("email"), FILTER_VALIDATE_EMAIL);
        if (empty($email)) {
            throw new Exception("The email address must be supplied", 400);
        } else {
            // need the user's ID rather than the representation
            $user_id = $user_mapper->getUserIdFromEmail($email);
            if ($user_id) {
                // Generate a verification token and email it to the user
                $token = $user_mapper->generateEmailVerificationTokenForUserId($user_id);

                $recipients   = array($email);
                $emailService = new UserRegistrationEmailService($this->config, $recipients, $token);
                $emailService->sendEmail();

                header("Content-Length: 0", null, 202);
                exit;
            }
            throw new Exception("Can't find that email address", 400);
        }
    }

    public function usernameReminder($request, $db)
    {
        $user_mapper = new UserMapper($db, $request, $this->config['website_url']);
        $email       = filter_var($request->getParameter("email"), FILTER_VALIDATE_EMAIL);
        if (empty($email)) {
            throw new Exception("The email address must be supplied", 400);
        } else {
            $list = $user_mapper->getUserByEmail($email);
            if (is_array($list['users']) && count($list['users'])) {
                $user = $list['users'][0];

                $recipients   = array($email);
                $emailService = new UserUsernameReminderEmailService($this->config, $recipients, $user);
                $emailService->sendEmail();

                header("Content-Length: 0", null, 202);
                exit;
            }
            throw new Exception("Can't find that email address", 400);
        }
    }

    public function passwordReset($request, $db)
    {
        $user_mapper = new UserMapper($db, $request, $this->config['website_url']);
        $username    = filter_var($request->getParameter("username"), FILTER_SANITIZE_STRING);
        if (empty($username)) {
            throw new Exception("A username must be supplied", 400);
        } else {
            $list = $user_mapper->getUserByUsername($username);
            if (is_array($list['users']) && count($list['users'])) {
                $user = $list['users'][0];

                // neither user_id nor email are in the user resource returned by the mapper
                $user_id    = $user_mapper->getUserIdFromUsername($username);
                $email      = $user_mapper->getEmailByUserId($user_id);
                $recipients = array($email);

                // we need a token to send so we know it is a valid reset
                $token = $user_mapper->generatePasswordResetTokenForUserId($user_id);
                if (! $token) {
                    throw new Exception("Unable to generate a reset token", 400);
                }

                $emailService = new UserPasswordResetEmailService($this->config, $recipients, $user, $token);
                $emailService->sendEmail();

                header("Content-Length: 0", null, 202);
                exit;
            }
            throw new Exception("Can't find that user", 400);
        }
    }
}
