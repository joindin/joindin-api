<?php

namespace Joindin\Api\Service;

class UserRegistrationEmailService extends BaseEmailService
{
    /** @var string */
    protected $token;

    /** @var string */
    protected $website_url;

    /**
     * @param array  $config
     * @param array  $recipients
     * @param string $token
     */
    public function __construct(array $config, array $recipients, $token)
    {
        // set up the common stuff first
        parent::__construct($config, $recipients);

        $this->token = $token;
        $this->website_url = $config['website_url'];
    }

    public function sendEmail()
    {
        $this->setSubject('Welcome to joind.in');

        $replacements = [
            "token" => $this->token,
            "website_url" => $this->website_url,
        ];

        $messageBody = $this->parseEmail("userRegistration.md", $replacements);
        $messageHTML = $this->markdownToHtml($messageBody);

        $this->setBody($this->htmlToPlainText($messageHTML));
        $this->setHtmlBody($messageHTML);

        $this->dispatchEmail();
    }
}
