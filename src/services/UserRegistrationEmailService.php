<?php

class UserRegistrationEmailService extends EmailBaseService
{
    protected $token;

    public function __construct($config, $recipients, $token)
    {
        // set up the common stuff first
        parent::__construct($config, $recipients);

        $this->token = $token;
    }

    public function sendEmail()
    {
        $this->setSubject('Welcome to joind.in');

        $replacements = array(
            "token"       => $this->token,
        );

        $messageBody = $this->parseEmail("userRegistration.md", $replacements);
        $messageHTML = $this->markdownToHtml($messageBody);

        $this->setBody($this->htmlToPlainText($messageHTML));
        $this->setHtmlBody($messageHTML);

        $this->dispatchEmail();
    }
}

