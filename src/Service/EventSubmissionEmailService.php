<?php

namespace Joindin\Api\Service;

use DateTime;

class EventSubmissionEmailService extends BaseEmailService
{
    protected array $event;
    protected array $comment;
    protected string $website_url;
    protected ?int $count;

    /**
     * @param array    $config
     * @param array    $recipients
     * @param array    $event
     * @param int|null $count
     */
    public function __construct(array $config, array $recipients, array $event, ?int $count = null)
    {
        // set up the common stuff first
        parent::__construct($config, $recipients);

        // this email needs event info
        $this->event       = $event;
        $this->website_url = $config['website_url'];

        $this->count = $count;
    }

    public function sendEmail(): void
    {
        $this->setSubject('New event submitted to joind.in');

        $date = new DateTime($this->event['start_date']);

        $replacements = [
            "title"        => $this->event['name'],
            "description"  => $this->event['description'],
            "date"         => $date->format('jS M, Y'),
            "contact_name" => $this->event['contact_name'],
            "website_url"  => $this->website_url,
        ];

        if ($this->count) {
            $replacements["count"] = "(" . $this->count . " events are pending)";
        } else {
            $replacements["count"] = "";
        }

        $messageBody = $this->parseEmail("eventSubmission.md", $replacements);
        $messageHTML = $this->markdownToHtml($messageBody);

        $this->setBody($this->htmlToPlainText($messageHTML));
        $this->setHtmlBody($messageHTML);

        $this->dispatchEmail();
    }
}
