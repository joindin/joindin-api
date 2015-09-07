<?php

class TalkCommentEmailService extends EmailBaseService
{

    protected $talk;
    protected $comment;

    public function __construct($config, $recipients, $talk, $comment)
    {
        // set up the common stuff first
        parent::__construct($config, $recipients);

        // this email needs talk and comment info
        $this->talk    = $talk['talks'][0];
        $this->comment = $comment['comments'][0];
    }

    public function sendEmail()
    {
        $this->setSubject("New feedback on " . $this->talk['talk_title']);
        $replacements = array(
            "title"   => $this->talk['talk_title'],
            "rating"  => $this->comment['rating'],
            "comment" => $this->comment['comment'],
        );

        $messageBody = $this->parseEmail("commentTalk.md", $replacements);
        $messageHTML = $this->markdownToHtml($messageBody);

        $this->setBody($this->htmlToPlainText($messageHTML));
        $this->setHtmlBody($messageHTML);

        $this->dispatchEmail();
    }
}
