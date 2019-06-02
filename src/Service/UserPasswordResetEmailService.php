<?php

namespace Joindin\Api\Service;

class UserPasswordResetEmailService extends BaseEmailService
{
    /** @var array */
    protected $user;

    /** @var string */
    protected $website_url;

    /**
     * @param array  $config
     * @param array  $recipients
     * @param array  $user
     * @param string $token
     */
    public function __construct(array $config, array $recipients, array $user, $token)
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

        $replacements = [
            "full_name"   => $this->user['full_name'],
            "username"    => $this->user['username'],
            "website_url" => $this->website_url,
            "token"       => $this->token,
        ];

        $messageBody = $this->parseEmail("userPasswordReset.md", $replacements);
        $messageHTML = $this->markdownToHtml($messageBody);

        $this->setBody($this->htmlToPlainText($messageHTML));
        $this->setHtmlBody($messageHTML);

        $this->dispatchEmail();
    }
}
