<?php

/**
 * Contact us end point
 */

class ContactController extends ApiController {
    public function handle(Request $request, $db) {
        // really need to not require this to be declared
    }

    /**
     * Send an email to feedback email address
     *
     * Expected fields:
     *  - name
     *  - email
     *  - comments
     *
     * @param  Request $request
     * @param  PDO $db
     * @return void
     */
    public function contact($request, $db){
        // only trusted clients can contact us to save on spam
        $clientId = $request->getParameter('client_id');
        $clientSecret = $request->getParameter('client_secret');
        $this->oauthModel = $request->getOauthModel($db);
        if (!$this->oauthModel->isClientPermittedPasswordGrant($clientId, $clientSecret)) {
            throw new Exception("This client cannot perform this action", 403);
        }

        $fields = ['name', 'email', 'comment'];
        $error = [];
        foreach ($fields as $name) {
            $value = $request->getParameter($name);
            if(empty($value)) {
                $error[] = "'$name'";
            }
            $data[$name] = $value;
        }
        if (!empty($error)) {
            $message = 'The field';
            $message .= count($error) == 1 ? ' ' : 's ';
            $message .= implode(', ', $error);
            $message .= count($error) == 1 ? ' is ' : ' are ';
            $message .= 'required.';
            throw new Exception($message, 400);
        }

        $emailService = new ContactEmailService($this->config);
        $emailService->sendEmail($data);

        header("Content-Length: 0", NULL, 202);
        exit;
    }
}
