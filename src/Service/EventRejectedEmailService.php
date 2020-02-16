<?php

namespace Joindin\Api\Service;

use DateTime;

class EventRejectedEmailService extends BaseEmailService
{
    protected $event;
    protected $website_url;

    public function __construct(array $config, array $recipients, array $event)
    {
        // set up the common stuff first
        parent::__construct($config, $recipients);

        $this->event = $event;
        $this->website_url = $config['website_url'];
    }

    public function sendEmail()
    {
        $this->setSubject('Event Rejected');

        $date = new DateTime($this->event['start_date']);
        $replacements = [
            "title" => $this->event['name'],
            "description" => $this->event['description'],
            "reason" => $this->event['rejection_reason'],
            "date" => $date->format('jS M, Y'),
            "contact_name" => $this->event['contact_name'],
            "website_url" => $this->website_url,
            "event_url" => $this->website_url . '/event/' . $this->event['url_friendly_name'] . '/edit',
        ];

        $messageBody = $this->parseEmail("eventRejected.md", $replacements);
        $messageHTML = $this->markdownToHtml($messageBody);

        $this->setBody($this->htmlToPlainText($messageHTML));
        $this->setHtmlBody($messageHTML);

        $this->dispatchEmail();
    }
}
