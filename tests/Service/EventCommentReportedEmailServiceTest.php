<?php

declare(strict_types=1);

namespace Joindin\Api\Test\Service;

use Joindin\Api\Service\EventCommentReportedEmailService;
use PHPUnit\Framework\TestCase;

class EventCommentReportedEmailServiceTest extends TestCase
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

    /** @var \array[][] */
    protected $comment = [
        'comments' => [
            [
                'talk_title' => 'talk',
                'comment' => 'foo',
                'rating' => 3,
            ]
        ]
    ];

    /**
     * @dataProvider sendEmailDataProvider
     */
    public function testSendEmailDoesNotThrowExceptions(string $type): void
    {
        $comment = $this->comment;

        if ($type) {
            $comment['comments'][0][$type] = $type;
        } else {
            $comment['comments'][0]['rating'] = 0;
        }

        $commentReportedEmailService = new EventCommentReportedEmailService(
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

    public function sendEmailDataProvider()
    {
        return [
            'no username and display name' => [''],
            'with display name' => ['user_display_name'],
            'with user name' => ['username'],
        ];
    }
}
