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

        $byLine = '';

        if(isset($this->comment['user_display_name'])) {
            $byLine = ' by ' . $this->comment['user_display_name'];

            if(isset($this->comment['username'])) {
                $byLine .= ' (' . $this->comment['username'] . ')';
            }
        }

        $replacements = array(
            "title"   => $this->talk['talk_title'],
            "rating"  => $this->comment['rating'],
            "comment" => $this->comment['comment'],
            "url"     => $this->talk['website_uri'],
            "byline"  => $byLine
        );

        $messageBody = $this->parseEmail("commentTalk.md", $replacements);
        $messageHTML = $this->markdownToHtml($messageBody);

        $this->setBody($this->htmlToPlainText($messageHTML));
        $this->setHtmlBody($messageHTML);

        $this->dispatchEmail();
    }
}
