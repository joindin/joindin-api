<?php


class TalkCommentEmailService extends EmailBaseService {

    /**
     * @var
     */
    public $talk;

    /**
     * @var
     */
    public $comment;

    /**
     * @var Array $toRecipients
     */
    protected $toRecipients;

    /**
     * @var Array $bccRrecipients
     */
    protected $bccRecipients;


    public function __construct($talk_id, $comment)
    {
        $this->talk = $talk_id;
        $this->comment = $comment;
    }

    public function parseEmail()
    {
        $template = file_get_contents('../views/emails/commentTalk.md');

        $messageBody = Michelf\Markdown::defaultTransform($template);

        var_dump($messageBody);die;

        return $messageBody;
    }

    public function sendEmail()
    {
        $messageBody = $this->parseEmail();

        $this->dispatchEmail($messageBody);
    }


}