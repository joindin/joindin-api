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

    protected function parseEmail()
    {
        $template = file_get_contents('../views/emails/commentTalk.md');

        $replacements = array(
            "title"   => $this->talk['talk_title'],
            "rating"  => $this->comment['rating'],
            "comment" => $this->comment['comment'],
        );

        $message = $template;
        foreach($replacements as $field => $value) {
            $message = str_replace('['. $field . ']', $value, $message);
        }

        return $message;
    }

    public function sendEmail()
    {
        $this->setSubject("New feedback on " . $this->talk['talk_title']);
        $messageBody = $this->parseEmail();
        $messageHTML = Michelf\Markdown::defaultTransform($messageBody);

        $this->setBody(strip_tags($messageHTML));
        $this->setHtmlBody($messageHTML);

        $this->dispatchEmail();
    }


}
