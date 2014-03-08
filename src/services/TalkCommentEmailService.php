<?php


class TalkCommentEmailService extends EmailBaseService {

    protected $talk;
    protected $comment;

    public function __construct($config, $recipients, $talk, $comment)
    {
        // set up the common stuff first
        parent::__construct($config, $recipients);

        // this email needs talk and comment info
        $this->talk = $talk['talks'][0];
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
        $messageHTML = Michelf\Markdown::defaultTransform($messageBody);

        $this->setBody(strip_tags($messageHTML));
        $this->setHtmlBody($messageHTML);

        $this->dispatchEmail();
    }


}
