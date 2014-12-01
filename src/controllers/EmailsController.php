<?php

/**
 * Actions here deal with endpoints that trigger emails to be sent
 */

class EmailsController extends ApiController {
    public function handle(Request $request, $db) {
        if ($request->getVerb() == 'POST') {
            return $this->postAction($request, $db);
        }
        return false;
    }

    public function postAction($request, $db){
        if(isset($request->url_elements[3])) {
            switch($request->url_elements[3]) {
                case 'verifications':
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
                            break;
                default:
                            throw new InvalidArgumentException('Unknown Subrequest', 404);
                            break;
            }
        } else {
            throw new InvalidArgumentException('Unknown Request', 404);
            break;
        }
    }
}
