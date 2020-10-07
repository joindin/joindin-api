<?php

namespace Joindin\Api\Test\Service;

use Joindin\Api\Service\EventSubmissionEmailService;
use PHPUnit\Framework\TestCase;

class EventSubmissionEmailServiceTest extends TestCase
{
    use ReplaceMailerTrait;

    /** @var array */
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

    /** @var string[] */
    protected $recipients = ["test@joind.in"];

    /** @var string[] */
    protected $event = [
        'name' => 'name',
        'description' => 'description',
        'contact_name' => 'contactName',
        'url_friendly_name' => 'urlFriendlyName',
        'start_date' => '2000-01-01',
        'rejection_reason' => 'rejected',
    ];

    /**
     * @dataProvider sendEmailDataProvider
     */
    public function testSendEmailDoesNotThrowExceptions(?int $count): void
    {
        $eventApprovedEmailService = new EventSubmissionEmailService(
            $this->config,
            $this->recipients,
            $this->event,
            $count
        );

        $this->replaceMailer($eventApprovedEmailService);

        $anExceptionWasThrown = false;

        try {
            $eventApprovedEmailService->sendEmail();
        } catch (\Throwable $exception) {
            $anExceptionWasThrown = true;
        }

        $this->assertFalse($anExceptionWasThrown);
    }

    public function sendEmailDataProvider()
    {
        return [
            'withoutCount' => [null],
            'withCount' => [5],
        ];
    }
}
