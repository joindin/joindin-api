<?php

/**
 * Actions here deal with endpoints that trigger emails to be sent
 */

class EmailsController extends ApiController {
    public function handle(Request $request, $db) {
        // really need to not require this to be declared
    }

    public function verifications($request, $db){
        $user_mapper= new UserMapper($db, $request);
        $email = filter_var($request->getParameter("email"), FILTER_VALIDATE_EMAIL);
        if(empty($email)) {
            throw new Exception("The email address must be supplied", 400);
        } else {
            // need the user's ID rather than the representation
            $user_id = $user_mapper->getUserIdFromEmail($email);
            if($user_id) {

                // Generate a verification token and email it to the user
                $token = $user_mapper->generateEmailVerificationTokenForUserId($user_id);

                $recipients = array($email);
                $emailService = new UserRegistrationEmailService($this->config, $recipients, $token);
                $emailService->sendEmail();

                header("Content-Length: 0", NULL, 202);
                exit;
            }
            throw new Exception("Can't find that email address", 400);
        }
    }

    public function username_reminder($request, $db) {
        $user_mapper= new UserMapper($db, $request);
        $email = filter_var($request->getParameter("email"), FILTER_VALIDATE_EMAIL);
        if(empty($email)) {
            throw new Exception("The email address must be supplied", 400);
        } else {
            // need the user's ID rather than the representation
            $user_id = $user_mapper->getUserIdFromEmail($email);
            if($user_id) {
                $list = $user_mapper->getUserById($user_id);
                $user = $list['users'][0];

                $recipients = array($email);
                $emailService = new UserUsernameReminderEmailService($this->config, $recipients, $user);
                $emailService->sendEmail();

                header("Content-Length: 0", NULL, 202);
                exit;
            }
            throw new Exception("Can't find that email address", 400);
        }
    }

}
