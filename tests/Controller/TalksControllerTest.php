<?php

namespace Joindin\Api\Test\Controller;

use Exception;
use Joindin\Api\Controller\TalkLinkController;
use Joindin\Api\Controller\TalksController;
use Joindin\Api\Model\PendingTalkClaimMapper;
use Joindin\Api\Model\TalkMapper;
use Joindin\Api\Model\TalkModelCollection;
use Joindin\Api\Model\UserMapper;
use Joindin\Api\Request;
use Joindin\Api\Service\NullSpamCheckService;
use Joindin\Api\Service\SpamCheckServiceInterface;
use Joindin\Api\Service\TalkCommentEmailService;
use Joindin\Api\Test\Mock\mockPDO;
use Teapot\StatusCode\Http;
use Teapot\StatusCode\WebDAV;

final class TalksControllerTest extends TalkBase
{
    private $config;

    public function setUp(): void
    {
        $this->config = [
            'email' => [
                'from' => 'source@example.com',
                'smtp'           => [
                    'host'     => 'localhost',
                    'port'     => 25,
                    'username' => 'username',
                    'password' => 'ChangeMeSeymourChangeMe',
                    'security' => null
                ],
            ],
            'website_url' => 'http://example.com',
        ];
    }

    /**
     * Ensures that if the setSpeakerForTalk method is called and no user_id is set,
     * an exception is thrown
     *
     * @return void
     *
     * @group uses_pdo
     */
    public function testClaimTalkWithNoUserIdThrowsException()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('You must be logged in to create data');
        $this->expectExceptionCode(Http::UNAUTHORIZED);

        $request = new Request(
            [],
            [
                'REQUEST_URI' => "http://api.dev.joind.in/v2.1/talks/326/speakers",
                'REQUEST_METHOD' => 'POST'
            ]
        );

        $talks_controller = new TalksController(new NullSpamCheckService());

        $db = $this->getMockBuilder(mockPDO::class)->getMock();

        $talks_controller->setSpeakerForTalk($request, $db);
    }

    /**
     * Ensures that if the setSpeakerForTalk method is called on a non-existent talk,
     * an exception is thrown
     *
     * @return void
     */
    public function testClaimNonExistantTalkThrowsException()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Talk not found');
        $this->expectExceptionCode(Http::NOT_FOUND);

        $request = new Request(
            [],
            [
                'REQUEST_URI' => "http://api.dev.joind.in/v2.1/talks/9999/speakers",
                'REQUEST_METHOD' => 'POST'
            ]
        );

        $request->user_id = 2;

        $talks_controller = new TalksController(new NullSpamCheckService());

        $db = $this->getMockBuilder(mockPDO::class)->getMock();

        $this->talk_mapper = $this->getMockBuilder(TalkMapper::class)
            ->setConstructorArgs([$db, $request])
            ->getMock();

        $this->talk_mapper
            ->expects($this->once())
            ->method('getTalkById')
            ->willReturn(false);

        $talks_controller->setTalkMapper($this->talk_mapper);

        $event_mapper = $this->createEventMapper($db, $request);
        $talks_controller->setEventMapper($event_mapper);

        $talks_controller->setSpeakerForTalk($request, $db);
    }

    /**
     * Ensures that if the setSpeakerForTalk method is called without a username,
     * an exception is thrown
     *
     * @return void
     */
    public function testClaimTalkWithoutUsernameThrowsException(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('You must provide a display name and a username');
        $this->expectExceptionCode(Http::BAD_REQUEST);

        $request = new Request(
            [],
            [
                'REQUEST_URI' => "http://api.dev.joind.in/v2.1/talks/9999/speakers",
                'REQUEST_METHOD' => 'POST'
            ]
        );

        $request->user_id = 2;
        $request->parameters = [
            'display_name'  => 'Jane Bloggs'
        ];

        $talks_controller = new TalksController(new NullSpamCheckService());

        $db = $this->getMockBuilder(mockPDO::class)->getMock();

        $this->talk_mapper = $this->createTalkMapper($db, $request);
        $talks_controller->setTalkMapper($this->talk_mapper);

        $user_mapper = $this->createUserMapper($db, $request);
        $talks_controller->setUserMapper($user_mapper);

        $event_mapper = $this->createEventMapper($db, $request);
        $talks_controller->setEventMapper($event_mapper);

        $talks_controller->setSpeakerForTalk($request, $db);
    }

    /**
     * Ensures that if the setSpeakerForTalk method is called without a display_name,
     * an exception is thrown
     *
     * @return void
     */
    public function testClaimTalkWithoutDisplayNameThrowsException()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('You must provide a display name and a username');
        $this->expectExceptionCode(Http::BAD_REQUEST);

        $request = new Request(
            [],
            [
                'REQUEST_URI' => "http://api.dev.joind.in/v2.1/talks/9999/speakers",
                'REQUEST_METHOD' => 'POST'
            ]
        );

        $request->user_id = 2;
        $request->parameters = [
            'username'  => 'janebloggs'
        ];

        $talks_controller = new TalksController(new NullSpamCheckService());

        $db = $this->getMockBuilder(mockPDO::class)->getMock();

        $this->talk_mapper = $this->createTalkMapper($db, $request);
        $talks_controller->setTalkMapper($this->talk_mapper);

        $user_mapper = $this->createUserMapper($db, $request);
        $talks_controller->setUserMapper($user_mapper);

        $event_mapper = $this->createEventMapper($db, $request);
        $talks_controller->setEventMapper($event_mapper);

        $talks_controller->setSpeakerForTalk($request, $db);
    }

    /**
     * Ensures that if the setSpeakerForTalk method is called with a
     * display_name that doesn't match a talk speaker, an exception is thrown
     *
     * @return void
     */
    public function testClaimTalkWithInvalidSpeakerThrowsException()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('No speaker matching that name found');
        $this->expectExceptionCode(WebDAV::UNPROCESSABLE_ENTITY);

        $request = new Request(
            [],
            [
                'REQUEST_URI' => "http://api.dev.joind.in/v2.1/talks/9999/speakers",
                'REQUEST_METHOD' => 'POST'
            ]
        );

        $request->user_id = 2;
        $request->parameters = [
            'username'      => 'janebloggs',
            'display_name'  =>  'P Sherman'
        ];

        $talks_controller = new TalksController(new NullSpamCheckService());

        $db = $this->getMockBuilder(mockPDO::class)->getMock();

        $this->talk_mapper = $this->createTalkMapper($db, $request);
        $this->talk_mapper
            ->expects($this->once())
            ->method('getSpeakerFromTalk')
            ->willReturn(false);
        $talks_controller->setTalkMapper($this->talk_mapper);

        $user_mapper = $this->createUserMapper($db, $request);
        $talks_controller->setUserMapper($user_mapper);

        $event_mapper = $this->createEventMapper($db, $request);
        $talks_controller->setEventMapper($event_mapper);

        $talks_controller->setSpeakerForTalk($request, $db);
    }

    /**
     * Ensures that if the setSpeakerForTalk method is called against a display_name
     * that has been claimed already, and Exception is thrown
     *
     * @return void
     */
    public function testClaimTalkAlreadyClaimedThrowsException()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Talk already claimed');
        $this->expectExceptionCode(WebDAV::UNPROCESSABLE_ENTITY);

        $request = new Request(
            [],
            [
                'REQUEST_URI' => "http://api.dev.joind.in/v2.1/talks/9999/speakers",
                'REQUEST_METHOD' => 'POST'
            ]
        );
        $request->user_id = 2;
        $request->parameters = [
            'username'      => 'janebloggs',
            'display_name'  => 'P Sherman'
        ];

        $talks_controller = new TalksController(new NullSpamCheckService());

        $db = $this->getMockBuilder(mockPDO::class)->getMock();

        $this->talk_mapper = $this->createTalkMapper($db, $request);
        $this->talk_mapper
            ->expects($this->once())
            ->method('getSpeakerFromTalk')
            ->willReturn([
                'speaker_id' => 1,
                'ID' => 1
            ]);
        $talks_controller->setTalkMapper($this->talk_mapper);

        $user_mapper = $this->createUserMapper($db, $request);
        $talks_controller->setUserMapper($user_mapper);

        $event_mapper = $this->createEventMapper($db, $request);
        $talks_controller->setEventMapper($event_mapper);

        $talks_controller->setSpeakerForTalk($request, $db);
    }

    /**
     * Ensures that if the setSpeakerForTalk method is called against a username
     * that is different to the logged in user, an Exception is thrown
     *
     * @return void
     */
    public function testClaimTalkForSomeoneElseThrowsException()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('You must be the speaker or event admin to link a user to a talk');
        $this->expectExceptionCode(Http::UNAUTHORIZED);

        $request = new Request(
            [],
            [
                'REQUEST_URI' => "http://api.dev.joind.in/v2.1/talks/9999/speakers",
                'REQUEST_METHOD' => 'POST'
            ]
        );
        $request->user_id = 2;
        $request->parameters = [
            'username'      => 'psherman',
            'display_name'  => 'P Sherman'
        ];

        $talks_controller = new TalksController(new NullSpamCheckService());

        $db = $this->getMockBuilder(mockPDO::class)->getMock();

        $this->talk_mapper = $this->createTalkMapper($db, $request);
        $this->talk_mapper
            ->expects($this->once())
            ->method('getSpeakerFromTalk')
            ->willReturn([
                'speaker_id' => null,
                'ID' => 1
            ]);
        $this->talk_mapper
            ->expects($this->once())
            ->method('thisUserHasAdminOn')
            ->willReturn(false);
        $talks_controller->setTalkMapper($this->talk_mapper);

        $user_mapper = $this->createUserMapper($db, $request);
        $user_mapper
            ->expects($this->once())
            ->method('getUserIdFromUsername')
            ->willReturn(6);
        $talks_controller->setUserMapper($user_mapper);

        $pending_talk_claim_mapper = $this->getMockBuilder(PendingTalkClaimMapper::class)
            ->setConstructorArgs([$db, $request])
            ->getMock();
        $pending_talk_claim_mapper
            ->expects($this->once())
            ->method('claimExists')
            ->willReturn(false);

        $talks_controller->setPendingTalkClaimMapper($pending_talk_claim_mapper);

        $event_mapper = $this->createEventMapper($db, $request);
        $talks_controller->setEventMapper($event_mapper);

        $talks_controller->setSpeakerForTalk($request, $db);
    }

    /**
     * Ensures that if the setSpeakerForTalk method is called as a host against a username
     * that doesn't exist, an Exception is thrown
     *
     * @return void
     */
    public function testAssignTalkAsHostToNonExistentUserThrowsException()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Specified user not found');
        $this->expectExceptionCode(Http::NOT_FOUND);

        $request = new Request(
            [],
            [
                'REQUEST_URI' => "http://api.dev.joind.in/v2.1/talks/9999/speakers",
                'REQUEST_METHOD' => 'POST'
            ]
        );
        $request->user_id = 2;
        $request->parameters = [
            'username'      => 'psherman',
            'display_name'  => 'P Sherman'
        ];

        $talks_controller = new TalksController(new NullSpamCheckService());

        $db = $this->getMockBuilder(mockPDO::class)->getMock();

        $this->talk_mapper = $this->createTalkMapper($db, $request);
        $this->talk_mapper
            ->expects($this->once())
            ->method('getSpeakerFromTalk')
            ->willReturn([
                'speaker_id' => null,
                'ID' => 1
            ]);
        $talks_controller->setTalkMapper($this->talk_mapper);

        $user_mapper = $this->createUserMapper($db, $request);
        $user_mapper
            ->expects($this->once())
            ->method('getUserIdFromUsername')
            ->willReturn(false);
        $talks_controller->setUserMapper($user_mapper);

        $event_mapper = $this->createEventMapper($db, $request);
        $talks_controller->setEventMapper($event_mapper);

        $talks_controller->setSpeakerForTalk($request, $db);
    }

    /**
     * Ensures that if the setSpeakerForTalk method is called and user_id is not an admin
     * then as long as they are claiming for themselves, the method succeeds
     *
     * @return void
     */
    public function testClaimTalkAsUserIsSuccessful()
    {
        $request = new Request(
            [],
            [
                'REQUEST_URI' => "http://api.dev.joind.in/v2.1/talks/9999/speakers",
                'REQUEST_METHOD' => 'POST'
            ]
        );
        $request->user_id = 2;
        $request->parameters = [
            'username'      => 'janebloggs',
            'display_name'  => 'Jane Bloggs'
        ];

        $talks_controller = new TalksController(
            new NullSpamCheckService(),
            $this->config
        );

        $db = $this->getMockBuilder(mockPDO::class)->getMock();

        $this->talk_mapper = $this->createTalkMapper($db, $request);
        $this->talk_mapper
            ->expects($this->once())
            ->method('getSpeakerFromTalk')
            ->willReturn([
                'speaker_id' => null,
                'ID' => 1
            ]);
        $talks_controller->setTalkMapper($this->talk_mapper);

        $user_mapper = $this->createUserMapper($db, $request);
        $user_mapper
            ->expects($this->once())
            ->method('getUserIdFromUsername')
            ->willReturn(2);
        $talks_controller->setUserMapper($user_mapper);

        $pending_talk_claim_mapper = $this->getMockBuilder(PendingTalkClaimMapper::class)
            ->setConstructorArgs([$db, $request])
            ->getMock();
        $pending_talk_claim_mapper
            ->expects($this->once())
            ->method('claimExists')
            ->willReturn(false);

        $pending_talk_claim_mapper
            ->expects($this->once())
            ->method('claimTalkAsSpeaker')
            ->willReturn(true);
        $talks_controller->setPendingTalkClaimMapper($pending_talk_claim_mapper);

        $event_mapper = $this->createEventMapper($db, $request);
        $talks_controller->setEventMapper($event_mapper);

        $this->assertNull($talks_controller->setSpeakerForTalk($request, $db));
    }

    /**
     * Ensures that if the setSpeakerForTalk method is called and user_id is a host
     * then as long as the username exists, the method succeeds
     *
     * @return void
     */
    public function testAssignTalkAsHostIsSuccessful()
    {
        $request = new Request(
            [],
            [
                'REQUEST_URI' => "http://api.dev.joind.in/v2.1/talks/9999/speakers",
                'REQUEST_METHOD' => 'POST'
            ]
        );
        $request->user_id = 2;
        $request->parameters = [
            'username'      => 'psherman',
            'display_name'  => 'P Sherman'
        ];

        $talks_controller = new TalksController(
            new NullSpamCheckService(),
            $this->config
        );

        $db = $this->getMockBuilder(mockPDO::class)->getMock();

        $this->talk_mapper = $this->createTalkMapper($db, $request);
        $this->talk_mapper
            ->expects($this->once())
            ->method('getSpeakerFromTalk')
            ->willReturn([
                'speaker_id' => null,
                'ID' => 1
            ]);
        $this->talk_mapper
            ->expects($this->once())
            ->method('thisUserHasAdminOn')
            ->willReturn(true);
        $talks_controller->setTalkMapper($this->talk_mapper);

        $user_mapper = $this->createUserMapper($db, $request);
        $user_mapper
            ->expects($this->once())
            ->method('getUserIdFromUsername')
            ->willReturn(1);
        $talks_controller->setUserMapper($user_mapper);

        $pending_talk_claim_mapper = $this->getMockBuilder(PendingTalkClaimMapper::class)
            ->setConstructorArgs([$db, $request])
            ->getMock();
        $pending_talk_claim_mapper
            ->expects($this->once())
            ->method('claimExists')
            ->willReturn(false);
        $pending_talk_claim_mapper
            ->expects($this->once())
            ->method('assignTalkAsHost')
            ->willReturn(true);
        $talks_controller->setPendingTalkClaimMapper($pending_talk_claim_mapper);

        $event_mapper = $this->createEventMapper($db, $request);
        $talks_controller->setEventMapper($event_mapper);

        $this->assertNull($talks_controller->setSpeakerForTalk($request, $db));
    }

    /**
     * Ensures that if the setSpeakerForTalk method is called by the same user who made the claim
     * then an exception is thrown
     */
    public function testApproveAssignmentAsUserWhoClaimedThrowsException()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('You already have a pending claim for this talk. Please wait for an event admin to approve your claim.');
        $this->expectExceptionCode(Http::UNAUTHORIZED);

        $request = new Request(
            [],
            [
                'REQUEST_URI' => "http://api.dev.joind.in/v2.1/talks/9999/speakers",
                'REQUEST_METHOD' => 'POST'
            ]
        );
        $request->user_id = 2;
        $request->parameters = [
            'username'      => 'janebloggs',
            'display_name'  => 'Jane Bloggs'
        ];

        $talks_controller = new TalksController(new NullSpamCheckService());

        $db = $this->getMockBuilder(mockPDO::class)->getMock();

        $this->talk_mapper = $this->createTalkMapper($db, $request);
        $this->talk_mapper
            ->expects($this->once())
            ->method('getSpeakerFromTalk')
            ->willReturn([
                'speaker_id' => null,
                'ID' => 1
            ]);
        $talks_controller->setTalkMapper($this->talk_mapper);

        $user_mapper = $this->createUserMapper($db, $request);
        $user_mapper
            ->expects($this->once())
            ->method('getUserIdFromUsername')
            ->willReturn(2);
        $this->talk_mapper
            ->expects($this->once())
            ->method('thisUserHasAdminOn')
            ->willReturn(false);
        $talks_controller->setUserMapper($user_mapper);

        $pending_talk_claim_mapper = $this->getMockBuilder(PendingTalkClaimMapper::class)
            ->setConstructorArgs([$db, $request])
            ->getMock();
        $pending_talk_claim_mapper
            ->expects($this->once())
            ->method('claimExists')
            ->willReturn(PendingTalkClaimMapper::SPEAKER_CLAIM);

        $talks_controller->setPendingTalkClaimMapper($pending_talk_claim_mapper);

        $event_mapper = $this->createEventMapper($db, $request);
        $talks_controller->setEventMapper($event_mapper);

        $talks_controller->setSpeakerForTalk($request, $db);
    }

    /**
     * Ensures that if the setSpeakerForTalk method is called by a non-admin user
     * then an exception is thrown
     */
    public function testApproveAssignmentAsNonAdminUserThrowsException()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('You must be an event admin to approve this claim');
        $this->expectExceptionCode(Http::UNAUTHORIZED);

        $request = new Request(
            [],
            [
                'REQUEST_URI' => "http://api.dev.joind.in/v2.1/talks/9999/speakers",
                'REQUEST_METHOD' => 'POST'
            ]
        );
        $request->user_id = 2;
        $request->parameters = [
            'username'      => 'janebloggs',
            'display_name'  => 'Jane Bloggs'
        ];

        $talks_controller = new TalksController(new NullSpamCheckService());

        $db = $this->getMockBuilder(mockPDO::class)->getMock();

        $this->talk_mapper = $this->createTalkMapper($db, $request);
        $this->talk_mapper
            ->expects($this->once())
            ->method('getSpeakerFromTalk')
            ->willReturn([
                'speaker_id' => null,
                'ID' => 1
            ]);
        $talks_controller->setTalkMapper($this->talk_mapper);

        $user_mapper = $this->createUserMapper($db, $request);
        $user_mapper
            ->expects($this->once())
            ->method('getUserIdFromUsername')
            ->willReturn(3);
        $this->talk_mapper
            ->expects($this->once())
            ->method('thisUserHasAdminOn')
            ->willReturn(false);
        $talks_controller->setUserMapper($user_mapper);

        $pending_talk_claim_mapper = $this->getMockBuilder(PendingTalkClaimMapper::class)
            ->setConstructorArgs([$db, $request])
            ->getMock();
        $pending_talk_claim_mapper
            ->expects($this->once())
            ->method('claimExists')
            ->willReturn(PendingTalkClaimMapper::SPEAKER_CLAIM);

        $talks_controller->setPendingTalkClaimMapper($pending_talk_claim_mapper);

        $event_mapper = $this->createEventMapper($db, $request);
        $talks_controller->setEventMapper($event_mapper);

        $talks_controller->setSpeakerForTalk($request, $db);
    }

    /**
     * Ensures that if the setSpeakerForTalk method is called by the host who assigned the talk
     * then an exception is thrown
     */
    public function testApproveClaimAsHostWhoAssignedThrowsException()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('You must be the talk speaker to approve this assignment');
        $this->expectExceptionCode(Http::UNAUTHORIZED);

        $request = new Request(
            [],
            [
                'REQUEST_URI' => "http://api.dev.joind.in/v2.1/talks/9999/speakers",
                'REQUEST_METHOD' => 'POST'
            ]
        );
        $request->user_id = 2;
        $request->parameters = [
            'username'      => 'psherman',
            'display_name'  => 'P Sherman'
        ];

        $talks_controller = new TalksController(new NullSpamCheckService());

        $db = $this->getMockBuilder(mockPDO::class)->getMock();

        $this->talk_mapper = $this->createTalkMapper($db, $request);
        $this->talk_mapper
            ->expects($this->once())
            ->method('getSpeakerFromTalk')
            ->willReturn([
                'speaker_id' => null,
                'ID' => 1
            ]);
        $talks_controller->setTalkMapper($this->talk_mapper);

        $user_mapper = $this->createUserMapper($db, $request);
        $user_mapper
            ->expects($this->once())
            ->method('getUserIdFromUsername')
            ->willReturn(1);
        $talks_controller->setUserMapper($user_mapper);

        $pending_talk_claim_mapper = $this->getMockBuilder(PendingTalkClaimMapper::class)
            ->setConstructorArgs([$db, $request])
            ->getMock();
        $pending_talk_claim_mapper
            ->expects($this->once())
            ->method('claimExists')
            ->willReturn(PendingTalkClaimMapper::HOST_ASSIGN);

        $talks_controller->setPendingTalkClaimMapper($pending_talk_claim_mapper);

        $event_mapper = $this->createEventMapper($db, $request);
        $talks_controller->setEventMapper($event_mapper);
        $talks_controller->setSpeakerForTalk($request, $db);
    }

    /**
     * Ensures that if the setSpeakerForTalk method is called by a user who has had a talk
     * assigned to them then it succeeds
     */
    public function testApproveAssignmentAsUserSucceeds()
    {
        $request = new Request(
            [],
            [
                'REQUEST_URI' => "http://api.dev.joind.in/v2.1/talks/9999/speakers",
                'REQUEST_METHOD' => 'POST'
            ]
        );
        $request->user_id = 2;
        $request->parameters = [
            'username'      => 'janebloggs',
            'display_name'  => 'Jane Bloggs',
        ];

        $talks_controller = new TalksController(new NullSpamCheckService());

        $db = $this->getMockBuilder(mockPDO::class)->getMock();

        $this->talk_mapper = $this->createTalkMapper($db, $request);
        $this->talk_mapper
            ->expects($this->once())
            ->method('getSpeakerFromTalk')
            ->willReturn([
                'speaker_id' => null,
                'ID' => 1
            ]);
        $this->talk_mapper
            ->expects($this->once())
            ->method('assignTalkToSpeaker')
            ->willReturn(true);
        $talks_controller->setTalkMapper($this->talk_mapper);

        $user_mapper = $this->createUserMapper($db, $request);
        $user_mapper
            ->expects($this->once())
            ->method('getUserIdFromUsername')
            ->willReturn(2);
        $talks_controller->setUserMapper($user_mapper);

        $pending_talk_claim_mapper = $this->getMockBuilder(PendingTalkClaimMapper::class)
            ->setConstructorArgs([$db, $request])
            ->getMock();
        $pending_talk_claim_mapper
            ->expects($this->once())
            ->method('claimExists')
            ->willReturn(PendingTalkClaimMapper::HOST_ASSIGN);
        $pending_talk_claim_mapper
            ->expects($this->once())
            ->method('approveAssignmentAsSpeaker')
            ->willReturn(true);

        $talks_controller->setPendingTalkClaimMapper($pending_talk_claim_mapper);

        $event_mapper = $this->createEventMapper($db, $request);
        $talks_controller->setEventMapper($event_mapper);

        $this->assertNull($talks_controller->setSpeakerForTalk($request, $db));
    }

    /**
     * Ensures that if the setSpeakerForTalk method is called by a host
     * in response to a claimed talk then it succeeds
     */
    public function testApproveClaimAsHostSucceeds()
    {
        $request = new Request(
            [],
            [
                'REQUEST_URI' => "http://api.dev.joind.in/v2.1/talks/9999/speakers",
                'REQUEST_METHOD' => 'POST'
            ]
        );
        $request->user_id = 2;
        $request->parameters = [
            'username'      => 'psherman',
            'display_name'  => 'P Sherman',
        ];

        $talks_controller = new TalksController(
            new NullSpamCheckService(),
            $this->config
        );

        $db = $this->getMockBuilder(mockPDO::class)->getMock();

        $this->talk_mapper = $this->createTalkMapper($db, $request);
        $this->talk_mapper
            ->expects($this->once())
            ->method('getSpeakerFromTalk')
            ->willReturn([
                'speaker_id' => null,
                'ID' => 1
            ]);
        $this->talk_mapper
            ->expects($this->once())
            ->method('thisUserHasAdminOn')
            ->willReturn(true);
        $this->talk_mapper
            ->expects($this->once())
            ->method('assignTalkToSpeaker')
            ->willReturn(true);

        $talks_controller->setTalkMapper($this->talk_mapper);

        $user_mapper = $this->createUserMapper($db, $request);
        $user_mapper
            ->expects($this->once())
            ->method('getUserIdFromUsername')
            ->willReturn(1);
        $talks_controller->setUserMapper($user_mapper);

        $pending_talk_claim_mapper = $this->getMockBuilder(PendingTalkClaimMapper::class)
            ->setConstructorArgs([$db, $request])
            ->getMock();
        $pending_talk_claim_mapper
            ->expects($this->once())
            ->method('claimExists')
            ->willReturn(PendingTalkClaimMapper::SPEAKER_CLAIM);
        $pending_talk_claim_mapper
            ->expects($this->once())
            ->method('approveClaimAsHost')
            ->willReturn(true);

        $talks_controller->setPendingTalkClaimMapper($pending_talk_claim_mapper);

        $event_mapper = $this->createEventMapper($db, $request);
        $talks_controller->setEventMapper($event_mapper);

        $this->assertNull($talks_controller->setSpeakerForTalk($request, $db));
    }

    /**
     * @dataProvider getComments
     */
    public function testCommentOnTalk(
        $commenterId,
        $speakerEmails,
        $expectedEmailsSent
    ) {
        $request = new Request(
            [],
            [
                'REQUEST_URI' => 'http://api.dev.joind.in/v2.1/talks/9999/comments',
                'REQUEST_METHOD' => 'POST'
            ]
        );

        $request->user_id = $commenterId;
        $request->parameters = [
            'username'      => 'psherman',
            'display_name'  => 'P Sherman',
            'comment' => 'Test Comment',
            'rating' => '3',
        ];

        $talks_comment_email =
            $this->getMockBuilder(TalkCommentEmailService::class)
                ->disableOriginalConstructor()
                ->getMock();

        $talks_comment_email->method('sendEmail');

        $talks_controller = new class(new NullSpamCheckService(), $talks_comment_email) extends TalksController {
            private $talkCommentEmailService;

            public function __construct(
                SpamCheckServiceInterface $spamCheckService,
                TalkCommentEmailService $talkCommentEmailService,
                array $config = []
            ) {
                parent::__construct($spamCheckService, $config);

                $this->talkCommentEmailService = $talkCommentEmailService;
            }

            public function getTalkCommentEmailService($config, $recipients, $talk, $comment)
            {
                return $this->talkCommentEmailService;
            }
        };

        $db = $this->getMockBuilder(mockPDO::class)->getMock();

        $this->talk_mapper = $this->createTalkMapper($db, $request);

        $this->talk_mapper
            ->method('getSpeakerEmailsByTalkId')
            ->willReturn($speakerEmails);

        $talks_controller->setTalkMapper(
            $this->talk_mapper
        );

        $talk_comment = $this->createTalkCommentMapper($db, $request);
        $talk_comment
            ->method('hasUserRatedThisTalk')
            ->willReturn(
                true
            );

        $talk_comment
            ->method('save')
            ->willReturn(
                true
            );
        $talk_comment
            ->method('getCommentById')
            ->willReturn(
                ['comments' => []]
            );

        $talks_controller->setMapper(
            'talkcomment',
            $talk_comment
        );

        $request->setOauthModel(
            $this->createOathModel($db, $request, "test")
        );

        $this->assertNull($talks_controller->postAction($request, $db));
    }

    public function getComments()
    {
        return [
            'commentOnTalk' => [
                'commenterId' => 3,
                'speakerEmails' => [
                    ['email' => 'test@speaker1.com', 'ID' => 1],
                    ['email' => 'test@speaker2.com', 'ID' => 2],
                ],
                'expectedEmailsSent' => [
                    'test@speaker1.com',
                    'test@speaker2.com'
                ]
            ],
            'commentOnOwnTalk' => [
                'commenterId' => 1,
                'speakerEmails' => [
                    ['email' => 'test@speaker1.com', 'ID' => 1],
                    ['email' => 'test@speaker2.com', 'ID' => 2],
                ],
                'expectedEmailsSent' => [
                    'test@speaker2.com'
                ]
            ],
        ];
    }

    /**
     * @group uses_pdo
     */
    public function testNotLoggedInPostAction()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('You must be logged in to create data');
        $this->expectExceptionCode(Http::UNAUTHORIZED);

        $request = new Request(
            [],
            [
                'REQUEST_URI' => 'http://api.dev.joind.in/v2.1/talks/9999/comments',
                'REQUEST_METHOD' => 'POST'
            ]
        );
        $db = $this->getMockBuilder(mockPDO::class)->getMock();

        $talks_controller = new TalksController(new NullSpamCheckService());

        $talks_controller->postAction($request, $db);
    }

    public function testNotSendingMessage()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('The field "comment" is required');
        $this->expectExceptionCode(Http::BAD_REQUEST);

        $request = new Request(
            [],
            [
                'REQUEST_URI' => 'http://api.dev.joind.in/v2.1/talks/9999/comments',
                'REQUEST_METHOD' => 'POST'
            ]
        );
        $request->user_id = 1;
        $request->parameters = [
            'username'      => 'psherman',
            'display_name'  => 'P Sherman',
            'rating' => '3',
        ];
        $db = $this->getMockBuilder(mockPDO::class)->getMock();

        $this->talk_mapper = $this->createTalkMapper($db, $request);

        $talks_controller = new TalksController(new NullSpamCheckService());

        $talks_controller->setTalkMapper($this->talk_mapper);

        $talks_controller->postAction($request, $db);
    }

    public function testNotSendingRating()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('The field "rating" is required');
        $this->expectExceptionCode(Http::BAD_REQUEST);

        $request = new Request(
            [],
            [
                'REQUEST_URI' => 'http://api.dev.joind.in/v2.1/talks/9999/comments',
                'REQUEST_METHOD' => 'POST'
            ]
        );
        $request->user_id = 1;
        $request->parameters = [
            'username'      => 'psherman',
            'display_name'  => 'P Sherman',
            'comment' => 'Test Comment',
        ];
        $db = $this->getMockBuilder(mockPDO::class)->getMock();

        $this->talk_mapper = $this->createTalkMapper($db, $request);

        $talks_controller = new TalksController(new NullSpamCheckService());

        $talks_controller->setTalkMapper($this->talk_mapper);

        $talks_controller->postAction($request, $db);
    }

    /**
     * Ensures that if the setSpeakerForTalk method is called by a host who rejects the speaker
     * in response to a claimed talk then it succeeds
     */
    public function testRejectClaimAsHostSucceeds()
    {
        $request = new Request(
            [],
            [
                'REQUEST_URI' => "http://api.dev.joind.in/v2.1/talks/9999/speakers",
                'REQUEST_METHOD' => 'DELETE'
            ]
        );

        $request->user_id = 2;
        $request->parameters = [
            'username'      => 'psherman',
            'display_name'  => 'P Sherman',
        ];

        $talks_controller = new TalksController(
            new NullSpamCheckService(),
            $this->config
        );

        $db = $this->getMockBuilder(mockPDO::class)->getMock();

        $this->talk_mapper = $this->createTalkMapper($db, $request);
        $this->talk_mapper
            ->expects($this->once())
            ->method('getSpeakerFromTalk')
            ->willReturn(
                [
                    'speaker_id' => null,
                    'ID' => 1
                ]
            );
        $this->talk_mapper
            ->expects($this->once())
            ->method('thisUserHasAdminOn')
            ->willReturn(true);

        $talks_controller->setTalkMapper($this->talk_mapper);

        $user_mapper = $this->getMockBuilder(UserMapper::class)
            ->setConstructorArgs([$db, $request])
            ->getMock();

        $user_mapper
            ->expects($this->once())
            ->method('getUserIdFromUsername')
            ->willReturn(1);
        $talks_controller->setUserMapper($user_mapper);

        $pending_talk_claim_mapper = $this->getMockBuilder(PendingTalkClaimMapper::class)
            ->setConstructorArgs([$db, $request])
            ->getMock();
        $pending_talk_claim_mapper
            ->expects($this->once())
            ->method('claimExists')
            ->willReturn(PendingTalkClaimMapper::SPEAKER_CLAIM);
        $pending_talk_claim_mapper
            ->expects($this->once())
            ->method('rejectClaimAsHost')
            ->willReturn(true);

        $talks_controller->setPendingTalkClaimMapper($pending_talk_claim_mapper);

        $event_mapper = $this->createEventMapper($db, $request);
        $talks_controller->setEventMapper($event_mapper);

        $this->assertTrue($talks_controller->removeSpeakerForTalk($request, $db));
    }

    public function testDifferentTalkMedia()
    {
        $request = new Request(
            [],
            [
                'REQUEST_URI' => 'http://api.dev.joind.in/v2.1/talks/3links',
                'REQUEST_METHOD' => 'GET'
            ]
        );

        $db = $this->getMockBuilder(mockPDO::class)->getMock();

        $this->talk_mapper = $this->createTalkMapper($db, $request);

        $expected = [
            ['slides_link' => 'http://slideshare.net'],
            ['code_link' => 'https://github.com/link/to/repo'],
        ];

        $this->talk_mapper
            ->method('getTalkMediaLinks')
            ->willReturn($expected);

        $talks_controller = new TalkLinkController();
        $talks_controller->setTalkMapper($this->talk_mapper);

        $output = $talks_controller->getTalkLinks($request, $db);
        $this->assertSame(
            $expected,
            $output['talk_links']
        );
    }

    public function testVerboseTalkOutput()
    {
        $request = new Request(
            [],
            [
                'REQUEST_URI' => 'http://api.dev.joind.in/v2.1/talks/3?verbose=yes',
                'REQUEST_METHOD' => 'GET'
            ]
        );

        $request->parameters = [
            'verbose'      => 'yes',
        ];

        $db = $this->getMockBuilder(mockPDO::class)->getMock();

        $this->talk_mapper = $this->createVerboseTalkMapper($db, $request);

        $expected = [
            ['slides_link' => 'http://slideshare.net'],
            ['code_link' => 'https://github.com'],
        ];

        $talks_controller = new TalksController(new NullSpamCheckService());

        $talks_controller->setTalkMapper($this->talk_mapper);

        $output = $talks_controller->getAction($request, $db);

        $this->assertSame(
            $expected,
            $output['talks'][0]['talk_media']
        );
        $this->assertSame(
            'http://slideshare.net',
            $output['talks'][0]['slides_link']
        );
    }

    public function testGetComments()
    {
        $request = new Request(
            [],
            [
                'REQUEST_URI' => 'http://api.dev.joind.in/v2.1/talks/79/comments',
                'REQUEST_METHOD' => 'GET'
            ]
        );

        $db = $this->getMockBuilder(mockPDO::class)->getMock();

        $expected = [
            'comments' => [],
            'meta' => [
                'count' => 0,
                'total' => 0,
                'this_page' => 'http://api.dev.joind.in/v2.1' .
                    '/talks/79/comments?resultsperpage=20',
            ]
        ];

        $talkComment = $this->createTalkCommentMapper($db, $request);
        $talkComment->method('getCommentsByTalkId')
            ->willReturn($expected);

        $talks_controller = new TalksController(new NullSpamCheckService());

        $talks_controller->setMapper(
            'talkcomment',
            $talkComment
        );

        $output = $talks_controller->getTalkComments($request, $db);
        $this->assertSame($expected, $output);
    }

    public function testGetStarred()
    {
        $request = new Request(
            [],
            [
                'REQUEST_URI' => 'http://api.dev.joind.in/v2.1/talks/79/starred',
                'REQUEST_METHOD' => 'GET'
            ]
        );

        $db = $this->getMockBuilder(mockPDO::class)->getMock();

        $expected = [
            'has_starred' => false,
        ];

        $talkMapper = $this->createTalkMapper($db, $request, 0);
        $talkMapper->method('getUserStarred')
            ->willReturn($expected);

        $talks_controller = new TalksController(new NullSpamCheckService());

        $talks_controller->setMapper(
            'talk',
            $talkMapper
        );

        $output = $talks_controller->getTalkStarred($request, $db);
        $this->assertSame($expected, $output);
    }

    public function testSearchByTitle()
    {
        $request = new Request(
            [],
            [
                'REQUEST_URI' => 'http://api.dev.joind.in/v2.1/talks',
                'REQUEST_METHOD' => 'GET'
            ]
        );

        $request->parameters = [
            'title'      => 'linux',
        ];

        $db = $this->getMockBuilder(mockPDO::class)->getMock();

        $expected = [
            'talks' => [
                [
                    'talk_title' => "Maintaining second generation clouds through Linux",
                ],
            ],
            'meta' => [
                'count' => 1,
                'total' => 1,
                'this_page' => "http://api.dev.joind.in/v2.1/talks/?title=linux&resultsperpage=20",
            ]
        ];

        $collection = $this->getMockBuilder(TalkModelCollection::class)
            ->disableOriginalConstructor()
            ->getMock();
        $collection->method('getOutputView')
            ->willReturn($expected);

        $talkMapper = $this->createTalkMapper($db, $request, 0);
        $talkMapper->method('getTalksByTitleSearch')
            ->willReturn($collection);

        $talks_controller = new TalksController(new NullSpamCheckService());

        $talks_controller->setMapper(
            'talk',
            $talkMapper
        );

        $output = $talks_controller->getTalkByKeyWord($request, $db);
        $this->assertSame($expected, $output);
    }

    public function testGenericTalkList()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Generic talks listing not supported');
        $this->expectExceptionCode(Http::METHOD_NOT_ALLOWED);

        $request = new Request(
            [],
            [
                'REQUEST_URI' => 'http://api.dev.joind.in/v2.1/talks',
                'REQUEST_METHOD' => 'GET'
            ]
        );

        $db = $this->getMockBuilder(mockPDO::class)->getMock();

        $talks_controller = new TalksController(new NullSpamCheckService());

        $talks_controller->getTalkByKeyWord($request, $db);
    }
}
