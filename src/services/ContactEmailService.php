<?php

class ContactEmailService extends BaseEmailService
{
    /** @var string */
    protected $website_url;

    public function __construct(array $config)
    {
        $recipients = $config['email']['contact'];
        if (!is_array($recipients)) {
            $recipients = (array) $recipients;
        }

        // set up the common stuff
        parent::__construct($config, $recipients);

        $this->website_url = $config['website_url'];
    }

    /**
     * @param array $data
     */
    public function sendEmail(array $data)
    {
        $this->setSubject('Joind.in contact: '.$data['subject']);

        $replyTo = $this->recipients;
        array_unshift($replyTo, $data['email']);
        $this->setReplyTo($replyTo);

        $replacements = $data;
        $replacements['website_url'] = $this->website_url;

        $messageBody = $this->parseEmail('contact.md', $replacements);
        $messageHTML = $this->markdownToHtml($messageBody);

        $this->setBody($this->htmlToPlainText($messageHTML));
        $this->setHtmlBody($messageHTML);

        $this->dispatchEmail();
    }
}
