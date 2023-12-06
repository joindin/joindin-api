<?php

namespace Joindin\Api\Service;

use Joindin\Api\Model\TalkModel;

class TalkClaimApprovedEmailService extends BaseEmailService
{
    protected array $event;
    protected TalkModel $talk;
    protected string $website_url;

    public function __construct(array $config, array $recipients, array $event, TalkModel $talk)
    {
        // set up the common stuff first
        parent::__construct($config, $recipients);

        $this->website_url = $config['website_url'];
        $this->talk        = $talk;
        $this->event       = $event['events'][0];
    }

    public function sendEmail(): void
    {
        $this->setSubject("Joind.in: Your talk claim has been approved");

        $replacements = [
            "eventName" => $this->event['name'],
            "talkTitle" => $this->talk->talk_title,
            "talkUri"   => $this->talk->getWebsiteUrl($this->website_url),
        ];

        $messageBody = $this->parseEmail("talkClaimApproved.md", $replacements);
        $messageHTML = $this->markdownToHtml($messageBody);

        $this->setBody($this->htmlToPlainText($messageHTML));
        $this->setHtmlBody($messageHTML);

        $this->dispatchEmail();
    }
}
