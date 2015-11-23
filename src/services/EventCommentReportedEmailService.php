<?php

class EventCommentReportedEmailService extends EmailBaseService
{

    protected $comment;
    protected $event;

    public function __construct($config, $recipients, $comment, $event)
    {
        // set up the common stuff first
        parent::__construct($config, $recipients);

        // this email needs comment info
        $this->comment = $comment['comments'][0];
        $this->event = $event['events'][0];
    }

    public function sendEmail()
    {
        $this->setSubject("Joind.in: A comment was reported");

        $byLine = '';

        if (isset($this->comment['user_display_name'])) {
            $byLine = ' by ' . $this->comment['user_display_name'];
        }

        if (empty($byLine) && isset($this->comment['username'])) {
            $byLine = ' by' . $this->comment['username'];
        }

        if (empty($byLine)) {
            $byLine = ' by (anonymous)';
        }

        $rating = $this->comment['rating'];
        if ($rating == 0) {
            $rating = 'Not rated';
        }

        $replacements = array(
            "name"   => $this->event['name'],
            "rating"  => $rating,
            "comment" => $this->comment['comment'],
            "byline"  => $byLine
        );

        $messageBody = $this->parseEmail("eventCommentReported.md", $replacements);
        $messageHTML = $this->markdownToHtml($messageBody);

        $this->setBody($this->htmlToPlainText($messageHTML));
        $this->setHtmlBody($messageHTML);

        $this->dispatchEmail();
    }
}
