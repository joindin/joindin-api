<?php

class EventbasedEmailService extends EmailBaseService
{
    protected $website_url;

    public function __construct($config)
    {
        // set up the common stuff first
        parent::__construct($config);

        $this->website_url = $config['website_url'];
    }

    public function setRecipients($recipients)
    {
        $this->recipients = $recipients;
    }

    public function send($subject, $html)
    {
        $this->setSubject($subject);
        $this->setBody($this->htmlToPlainText($html));
        $this->setHtmlBody($html);

        $this->dispatchEmail();
    }
}
