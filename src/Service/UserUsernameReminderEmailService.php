<?php

namespace Joindin\Api\Service;

class UserUsernameReminderEmailService extends BaseEmailService
{
    protected $user;
    protected $website_url;

    /**
     * @param array $config
     * @param array $recipients
     * @param array $user
     */
    public function __construct(array $config, array $recipients, array $user)
    {
        // set up the common stuff first
        parent::__construct($config, $recipients);

        $this->user        = $user;
        $this->website_url = $config['website_url'];
    }

    public function sendEmail()
    {
        $this->setSubject('Your joind.in username');

        $replacements = array(
            "full_name"   => $this->user['full_name'],
            "username"    => $this->user['username'],
            "website_url" => $this->website_url,
        );

        $messageBody = $this->parseEmail("userUsernameReminder.md", $replacements);
        $messageHTML = $this->markdownToHtml($messageBody);

        $this->setBody($this->htmlToPlainText($messageHTML));
        $this->setHtmlBody($messageHTML);

        $this->dispatchEmail();
    }
}
