<?php

class TalkClaimApprovedEmailService extends BaseEmailService
{

    protected $event;
    /** @var TalkModel */
    protected $talk;
    protected $website_url;

    public function __construct(array $config, array $recipients, array $event, TalkModel $talk)
    {
        // set up the common stuff first
        parent::__construct($config, $recipients);

        $this->talk = $talk;
        $this->event = $event['events'][0];

    }

    public function sendEmail()
    {
        $this->setSubject("Joind.in: Your talk claim has been approved");


        $replacements = array(
            "eventName" => $this->event['name'],
            "talkTitle" => $this->talk->talk_title
        );

        $messageBody = $this->parseEmail("talkClaimApproved.md", $replacements);
        $messageHTML = $this->markdownToHtml($messageBody);

        $this->setBody($this->htmlToPlainText($messageHTML));
        $this->setHtmlBody($messageHTML);

        $this->dispatchEmail();
    }
}
