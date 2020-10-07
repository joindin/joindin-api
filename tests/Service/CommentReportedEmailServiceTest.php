<?php

declare(strict_types=1);

namespace Joindin\Api\Test\Service;

use Joindin\Api\Service\CommentReportedEmailService;
use PHPUnit\Framework\TestCase;

class CommentReportedEmailServiceTest extends TestCase
{
    use ReplaceMailerTrait;

    /** @var array */
    protected $config = [
        'email' => [
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

    /** @var array */
    protected $recipients = ["test@joind.in"];

    /** @var array */
    protected $comment = [
        'comments' => [
            [
                'talk_title' => 'talk',
                'comment' => 'foo',
                'rating' => 3,
            ]
        ]
    ];

    /** @var \string[][][] */
    protected $event = [
        'events' => [
            [
                'name' => 'name',
                'description' => 'description',
                'contact_name' => 'contactName',
                'url_friendly_name' => 'urlFriendlyName',
                'start_date' => '2000-01-01',
            ],
        ],
    ];

    /**
     * @dataProvider sendEmailDataProvider
     */
    public function testSendEmailDoesNotThrowExceptions(string $type): void
    {
        $comment = $this->comment;
        if ($type) {
            $comment['comments'][0][$type] = $type;
        }

        $commentReportedEmailService = new CommentReportedEmailService(
            $this->config,
            $this->recipients,
            $comment,
            $this->event
        );

        $this->replaceMailer($commentReportedEmailService);

        $anExceptionWasThrown = false;

        try {
            $commentReportedEmailService->sendEmail();
        } catch (\Throwable $exception) {
            $anExceptionWasThrown = true;
        }

        $this->assertFalse($anExceptionWasThrown);
    }

    public function sendEmailDataProvider(): array
    {
        return [
            'no username and display name' => [''],
            'with display name' => ['user_display_name'],
            'with user name' => ['username'],
        ];
    }
}
