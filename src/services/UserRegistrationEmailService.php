<?php

class UserRegistrationEmailService extends EmailBaseService
{
    protected $token;
    protected $website_url;

    public function __construct($config, $recipients, $token)
    {
        // set up the common stuff first
        parent::__construct($config, $recipients);

        $this->token       = $token;
        $this->website_url = $config['website_url'];
    }

    public function sendEmail()
    {
        $this->setSubject('Welcome to joind.in');

        $replacements = array(
            "token"   => $this->token,
            "website_url" => $this->website_url,
        );

        $messageBody = $this->parseEmail("userRegistration.md", $replacements);
        $messageHTML = $this->markdownToHtml($messageBody);

        $this->setBody($this->htmlToPlainText($messageHTML));
        $this->setHtmlBody($messageHTML);

        $this->dispatchEmail();
    }
}

