<?php

class TalkClaimEmailService extends EmailBaseService
{

    protected $event;
    protected $talk;
    protected $website_url;

    public function __construct($config, $recipients, $event, $talk)
    {
        // set up the common stuff first
        parent::__construct($config, $recipients);

        // this email needs comment info
        $this->talk = $talk;
        $this->website_url = $config['website_url'];
        $this->event = $event['events'][0];

    }

    public function sendEmail()
    {
        $this->setSubject("Joind.in: A talk has been claimed");


        $replacements = array(
            "eventName" => $this->event['name'],
            "talkTitle" => $this->talk->talk_title,
            "link"      => $this->linkToPendingClaimsForEvent()
        );

        $messageBody = $this->parseEmail("talkClaimed.md", $replacements);
        $messageHTML = $this->markdownToHtml($messageBody);

        $this->setBody($this->htmlToPlainText($messageHTML));
        $this->setHtmlBody($messageHTML);

        $this->dispatchEmail();
    }

    private function linkToPendingClaimsForEvent()
    {

        return '[' . $this->website_url
            . '/event/' . $this->event['url_friendly_name']
            . '/claims' . '](' . $this->website_url
            . '/event/' . $this->event['url_friendly_name']
            . '/claims' . ')';
    }
}
