<?php

declare(strict_types=1);

namespace Joindin\Api\Test\Service;

use Joindin\Api\Service\ContactEmailService;
use PHPUnit\Framework\TestCase;

class ContactEmailServiceTest extends TestCase
{
    use ReplaceMailerTrait;

    protected $config = [
        'email' => [
            'contact' => 'noreply@example.com',
            'from' => 'test@joind.in',
            'smtp' => [
                'host' => 'localhost',
                'port' => 25,
                'username' => 'username',
                'password' => 'ChangeMeSeymourChangeMe',
                'security' => null,
            ],
        ],
        'website_url' => 'www.example.org'
    ];

    public function testSendEmailDoesNotThrowExceptions(): void
    {
        $contactEmailService = new ContactEmailService($this->config);
        $this->replaceMailer($contactEmailService);

        $anExceptionWasThrown = false;

        try {
            $contactEmailService->sendEmail(
                [
                    'subject' => 'foo',
                    'email' => 'noreply@example.com',
                ]
            );
        } catch (\Throwable $exception) {
            $anExceptionWasThrown = true;
        }

        $this->assertFalse($anExceptionWasThrown);
    }
}
