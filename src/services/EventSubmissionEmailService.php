<?php

class EventSubmissionEmailService extends EmailBaseService
{
    protected $event;
    protected $comment;

    public function __construct($config, $recipients, $event)
    {
        // set up the common stuff first
        parent::__construct($config, $recipients);

        // this email needs event info
        $this->event = $event;
    }

    public function sendEmail()
    {
        $this->setSubject('New event submitted to joind.in');
        $replacements = array(
            "title"       => $this->event['name'],
            "description" => $this->event['description'],
            "date"        => $this->event['start_date'],
            "host_name"   => $this->event['hosts'][0]['host_name'],
        );

        $messageBody = $this->parseEmail("eventSubmission.md", $replacements);
        $messageHTML = $this->markdownToHtml($messageBody);

        $this->setBody($this->htmlToPlainText($messageHTML));
        $this->setHtmlBody($messageHTML);

        $this->dispatchEmail();
    }
}
