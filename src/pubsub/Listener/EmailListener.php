<?php

namespace Joindin\Pubsub\Listener;

use Joindin\Pubsub\Event\AutoApprovedEventCreated;
use Joindin\Pubsub\Event\PendingEventCreated;
use Joindin\Pubsub\ListenerInterface;

class EmailListener implements ListenerInterface
{
    protected $emailservice;

    protected $userMapper;

    public function __construct(\EventbasedEmailService $emailservice, \UserMapper $userMapper)
    {
        $this->emailservice = $emailservice;
        $this->userMapper   = $userMapper;
    }

    public function getCallbacks()
    {
        return [
            AutoApprovedEventCreated::getEventName() => [$this, 'autoApprovedEventCreated'],
            PendingEventCreated::getEventName() => [$this, 'pendingEventCreated'],
        ];
    }

    public function autoApprovedEventCreated(AutoApprovedEventCreated $autoApprovedEvent)
    {
        // Do something here with the event
    }

    public function pendingEventCreated(PendingEventCreated $pendingEvent)
    {
        $event = $pendingEvent->getEvent();
        $subject = 'New event submitted to joind.in';

        $date = new \DateTime('@' . $event['start_date']);
        $replacements = array(
            "title"        => $event['name'],
            "description"  => $event['description'],
            "date"         => $date->format('jS M, Y'),
            "contact_name" => $event['contact_name'],
            "website_url"  => $this->emailservice->getWebsiteUrl()
        );

        if (isset($event['count']) && $event['count']) {
            $replacements["count"] = "(" . $event['count'] . " events are pending)";
        } else {
            $replacements["count"] = "";
        }
        $this->emailservice->setRecipients($this->userMapper->getSiteAdminEmails());
        $this->emailservice->send($subject, $this->getMailContent('eventSubmission.md', $replacements));
    }

    protected function getMailContent($template, $replacements)
    {
        $messageBody = $this->emailservice->parseEmail($template, $replacements);
        return $this->emailservice->markdownToHtml($messageBody);

    }
}
