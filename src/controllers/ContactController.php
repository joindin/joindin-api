<?php

/**
 * Contact us end point
 */
class ContactController extends BaseApiController
{
    /**
     * @var ContactEmailService
     */
    private $emailService;

    /**
     * @var SpamCheckService
     */
    private $spamCheckService;

    public function handle(Request $request, PDO $db)
    {
        // really need to not require this to be declared
    }

    /**
     * @param ContactEmailService $emailService
     *
     * @return $this
     */
    public function setEmailService(ContactEmailService $emailService) {
        $this->emailService = $emailService;

        return $this;
    }

    /**
     * @param SpamCheckService $spamCheckService
     *
     * @return $this
     */
    public function setSpamCheckService(SpamCheckService $spamCheckService) {
        $this->spamCheckService = $spamCheckService;

        return $this;
    }

    /**
     * Send an email to feedback email address
     *
     * Expected fields:
     *  - client_id
     *  - client_secret
     *  - name
     *  - email
     *  - subject
     *  - comment
     *
     * @param Request $request
     * @param PDO $db
     *
     * @throws Exception
     * @return void
     */
    public function contact(Request $request, PDO $db)
    {
        // only trusted clients can contact us to save on spam
        $clientId         = $request->getParameter('client_id');
        $clientSecret     = $request->getParameter('client_secret');
        $this->oauthModel = $request->getOauthModel($db);
        if (! $this->oauthModel->isClientPermittedPasswordGrant($clientId, $clientSecret)) {
            throw new Exception("This client cannot perform this action", 403);
        }

        $fields = ['name', 'email', 'subject', 'comment'];
        $error  = [];
        foreach ($fields as $name) {
            $value = $request->getParameter($name);
            if (empty($value)) {
                $error[] = "'$name'";
            }
            $data[$name] = $value;
        }

        if (! empty($error)) {
            $message = 'The field';
            $message .= count($error) == 1 ? ' ' : 's ';
            $message .= implode(', ', $error);
            $message .= count($error) == 1 ? ' is ' : ' are ';
            $message .= 'required.';
            throw new Exception($message, 400);
        }

        // run it by akismet if we have it
        if (isset($this->spamCheckService)) {
            $isValid = $this->spamCheckService->isCommentAcceptable(
                $data['comment'],
                $request->getClientIP(),
                $request->getClientUserAgent()
            );

            if (!$isValid) {
                throw new Exception("Comment failed spam check", 400);
            }
        }

        $this->emailService->sendEmail($data);

        $view = $request->getView();

        $view->setResponseCode(202);
        $view->setHeader('Content-Length', 0);
    }
}
