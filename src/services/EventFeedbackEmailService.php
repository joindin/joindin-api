<?php 

class EventFeedbackEmailService extends EmailBaseService {

    protected $event;
    protected $message;

    public function __construct($config, $recipients, $event, $feedback)
    {
        // set up the common stuff first
        parent::__construct($config, $recipients);

        // this email needs talk and comment info
        $this->event    = $event;
        $this->feedback = $feedback;
    }

    public function sendEmail()
    {
        $this->setSubject("Event feedback on " . $this->event['name']);

        $replacements = array(
            "title"     => $this->event['name'],
            "feedback"  => $this->feedback['feedback'],
        );

        $messageBody = $this->parseEmail("feedbackEvent.md", $replacements);
        $messageHTML = $this->markdownToHtml($messageBody);

        $this->setBody($this->htmlToPlainText($messageHTML));
        $this->setHtmlBody($messageHTML);

        $this->dispatchEmail();
    }


}
