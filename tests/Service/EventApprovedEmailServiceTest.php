<?php

namespace Joindin\Api\Test\Service;

use Joindin\Api\Service\EventApprovedEmailService;
use PHPUnit\Framework\TestCase;

class EventApprovedEmailServiceTest extends TestCase
{
    use ReplaceMailerTrait;

    protected $config = [
        'email' => [
            'from' => 'test@joind.in',
            'smtp' => [
                'host'     => 'localhost',
                'port'     => 25,
                'username' => 'username',
                'password' => 'ChangeMeSeymourChangeMe',
                'security' => null,
            ],
        ],
        'website_url' => 'www.example.org'
    ];

    protected $recipients = ["test@joind.in"];

    protected $event = [
        'name' => 'name',
        'description' => 'description',
        'contact_name' => 'contactName',
        'url_friendly_name' => 'urlFriendlyName',
        'start_date' => '2000-01-01',
    ];

    public function testSendEmailDoesNotThrowExceptions(): void
    {
        $eventApprovedEmailService = new EventApprovedEmailService($this->config, $this->recipients, $this->event);

        $this->replaceMailer($eventApprovedEmailService);

        $anExceptionWasThrown = false;

        try {
            $eventApprovedEmailService->sendEmail();
        } catch (\Throwable $exception) {
            $anExceptionWasThrown = true;
        }

        $this->assertFalse($anExceptionWasThrown);
    }
}
