<?php

class UserPasswordResetEmailService extends BaseEmailService
{
    protected $user;
    protected $website_url;

    public function __construct($config, $recipients, $user, $token)
    {
        // set up the common stuff first
        parent::__construct($config, $recipients);

        $this->user        = $user;
        $this->token       = $token;
        $this->website_url = $config['website_url'];
    }

    public function sendEmail()
    {
        $this->setSubject('Resetting your joind.in password');

        $replacements = array(
            "full_name"   => $this->user['full_name'],
            "username"    => $this->user['username'],
            "website_url" => $this->website_url,
            "token"       => $this->token,
        );

        $messageBody = $this->parseEmail("userPasswordReset.md", $replacements);
        $messageHTML = $this->markdownToHtml($messageBody);

        $this->setBody($this->htmlToPlainText($messageHTML));
        $this->setHtmlBody($messageHTML);

        $this->dispatchEmail();
    }
}
