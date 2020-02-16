<?php

namespace Joindin\Api\Service;

use Joindin\Api\Model\TalkModel;

class TalkAssignEmailService extends BaseEmailService
{
    protected $event;
    protected $talk;
    protected $website_url;
    /**
     * @var string
     */
    protected $username;

    /**
     * @param array     $config
     * @param array     $recipients
     * @param array     $event
     * @param TalkModel $talk
     * @param string    $username
     */
    public function __construct(array $config, array $recipients, array $event, TalkModel $talk, $username)
    {
        // set up the common stuff first
        parent::__construct($config, $recipients);

        // this email needs comment info
        $this->talk = $talk;
        $this->website_url = $config['website_url'];
        $this->event = $event['events'][0];
        $this->username = $username;
    }

    public function sendEmail()
    {
        $this->setSubject("Joind.in: A talk has been assigned to you");

        $replacements = [
            "eventName" => $this->event['name'],
            "talkTitle" => $this->talk->talk_title,
            "link" => $this->linkToEditUserPage()
        ];

        $messageBody = $this->parseEmail("talkAssigned.md", $replacements);
        $messageHTML = $this->markdownToHtml($messageBody);

        $this->setBody($this->htmlToPlainText($messageHTML));
        $this->setHtmlBody($messageHTML);

        $this->dispatchEmail();
    }

    /**
     * @return string a link in markdown
     */
    private function linkToEditUserPage()
    {
        return '[' . $this->website_url
               . '/user/' . $this->username
               . '/edit' . '](' . $this->website_url
               . '/user/' . $this->username
               . '/claims' . ')';
    }
}
