<?php

class CommentReportedEmailService extends EmailBaseService
{

    protected $comment;

    public function __construct($config, $recipients, $comment)
    {
        // set up the common stuff first
        parent::__construct($config, $recipients);

        // this email needs comment info
        $this->comment = $comment['comments'][0];
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

        $replacements = array(
            "title"   => $this->comment['talk_title'],
            "rating"  => $this->comment['rating'],
            "comment" => $this->comment['comment'],
            "byline"  => $byLine
        );

        $messageBody = $this->parseEmail("commentReported.md", $replacements);
        $messageHTML = $this->markdownToHtml($messageBody);

        $this->setBody($this->htmlToPlainText($messageHTML));
        $this->setHtmlBody($messageHTML);

        $this->dispatchEmail();
    }
}
