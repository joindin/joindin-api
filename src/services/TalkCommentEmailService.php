<?php


class TalkCommentEmailService extends EmailBaseService {

    protected $talk;
    protected $comment;

    public function __construct($recipients, $talk, $comment)
    {
        // set up the common stuff first
        parent::__construct($recipients);

        // this email needs talk and comment info
        $this->talk = $talk['talks'][0];
        $this->comment = $comment['comments'][0];
    }

    public function parseEmail()
    {
        $template = file_get_contents('../views/emails/commentTalk.md');
        $messageBody = Michelf\Markdown::defaultTransform($template);

        $replacements = array(
            "title"   => $this->talk['talk_title'],
            "rating"  => $this->comment['rating'],
            "comment" => $this->comment['comment'],
        );

        // probably reuse this bit?  It could go in the parent
        // also could do it before the markdown/html/plain text conversions
        foreach($replacements as $field => $value) {
            $messageBody = str_replace('['. $field . ']', $value, $messageBody);
        }

        return $messageBody;
    }

    public function sendEmail()
    {
        $messageBody = $this->parseEmail();
        $this->message->setBody($messageBody);

        $this->dispatchEmail();
    }


}
