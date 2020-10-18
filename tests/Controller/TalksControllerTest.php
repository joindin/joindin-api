<?php

namespace Joindin\Api\Test\Controller;

use Exception;
use Joindin\Api\Controller\TalkLinkController;
use Joindin\Api\Controller\TalksController;
use Joindin\Api\Factory\EmailServiceFactory;
use Joindin\Api\Factory\MapperFactory;
use Joindin\Api\Model\EventMapper;
use Joindin\Api\Model\OAuthModel;
use Joindin\Api\Model\PendingTalkClaimMapper;
use Joindin\Api\Model\TalkCommentMapper;
use Joindin\Api\Model\TalkMapper;
use Joindin\Api\Model\TalkModel;
use Joindin\Api\Model\TalkModelCollection;
use Joindin\Api\Model\UserMapper;
use Joindin\Api\Request;
use Joindin\Api\Service\NullSpamCheckService;
use Joindin\Api\Service\SpamCheckServiceInterface;
use Joindin\Api\Service\TalkClaimRejectedEmailService;
use Joindin\Api\Service\TalkCommentEmailService;
use Joindin\Api\Test\EmailServiceFactoryForTests;
use Joindin\Api\Test\MapperFactoryForTests;
use Joindin\Api\Test\Mock\mockPDO;
use Joindin\Api\View\ApiView;
use PDO;
use PHPUnit\Framework\MockObject\MockObject;
use Teapot\StatusCode\Http;
use Teapot\StatusCode\WebDAV;

final class TalksControllerTest extends TalkBase
{
    private $config;
    /**
     * @var MockObject
     */
    private $request;
    /**
     * @var MockObject
     */
    private $db;
    /**
     * @var MapperFactoryForTests
     */
    protected $mapperFactory;
    /**
     * @var MockObject
     */
    private $spawnChecker;
    /**
     * @var TalksController
     */
    private $sut;
    /**
     * @var EmailServiceFactory
     */
    private $emailServiceFactory;

    public function setUp(): void
    {
        parent::setUp();
        $this->config = [
            'email' => [
                'from' => 'source@example.com',
                'smtp' => [
                    'host' => 'localhost',
                    'port' => 25,
                    'username' => 'username',
                    'password' => 'ChangeMeSeymourChangeMe',
                    'security' => null
                ],
            ],
            'website_url' => 'http://example.com',
        ];
        $this->request = $this->getMockBuilder(Request::class)->disableOriginalConstructor()->getMock();
        $this->db = $this->getMockBuilder(PDO::class)->disableOriginalConstructor()->getMock();
        $this->spawnChecker = $this->getMockBuilder(SpamCheckServiceInterface::class)->disableOriginalConstructor()->getMock();

        $this->emailServiceFactory = new EmailServiceFactoryForTests();
        $this->sut = new TalksController($this->spawnChecker, $this->config, $this->mapperFactory,
            $this->emailServiceFactory);
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

        $talks_controller = new TalksController(new NullSpamCheckService(), [], $this->mapperFactory);

        $db = $this->getMockBuilder(mockPDO::class)->getMock();

        $this->talk_mapper = $this->getMockBuilder(TalkMapper::class)
            ->setConstructorArgs([$db, $request])
            ->getMock();

        $this->talk_mapper
            ->expects($this->once())
            ->method('getTalkById')
            ->willReturn(false);

        $this->mapperFactory->setMapperMockAs(TalkMapper::class, $this->talk_mapper);

        $event_mapper = $this->createEventMapper($db, $request);
        $this->mapperFactory->setMapperMockAs(EventMapper::class, $event_mapper);
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
            'display_name' => 'Jane Bloggs'
        ];

        $talks_controller = new TalksController(new NullSpamCheckService(), [], $this->mapperFactory);

        $db = $this->getMockBuilder(mockPDO::class)->getMock();

        $this->talk_mapper = $this->createTalkMapper($db, $request);
        $this->mapperFactory->setMapperMockAs(TalkMapper::class, $this->talk_mapper);

        $user_mapper = $this->createUserMapper($db, $request);
        $this->mapperFactory->setMapperMockAs(UserMapper::class, $user_mapper);

        $event_mapper = $this->createEventMapper($db, $request);
        $this->mapperFactory->setMapperMockAs(EventMapper::class, $event_mapper);

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
            'username' => 'janebloggs'
        ];

        $talks_controller = new TalksController(new NullSpamCheckService(), [], $this->mapperFactory);

        $db = $this->getMockBuilder(mockPDO::class)->getMock();

        $this->talk_mapper = $this->createTalkMapper($db, $request);
        $this->mapperFactory->setMapperMockAs(TalkMapper::class, $this->talk_mapper);

        $user_mapper = $this->createUserMapper($db, $request);
        $this->mapperFactory->setMapperMockAs(UserMapper::class, $user_mapper);

        $event_mapper = $this->createEventMapper($db, $request);
        $this->mapperFactory->setMapperMockAs(EventMapper::class, $event_mapper);

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
            'username' => 'janebloggs',
            'display_name' => 'P Sherman'
        ];

        $talks_controller = new TalksController(new NullSpamCheckService(), [], $this->mapperFactory);

        $db = $this->getMockBuilder(mockPDO::class)->getMock();

        $this->talk_mapper = $this->createTalkMapper($db, $request);
        $this->talk_mapper
            ->expects($this->once())
            ->method('getSpeakerFromTalk')
            ->willReturn(false);
        $this->mapperFactory->setMapperMockAs(TalkMapper::class, $this->talk_mapper);

        $user_mapper = $this->createUserMapper($db, $request);
        $this->mapperFactory->setMapperMockAs(UserMapper::class, $user_mapper);

        $event_mapper = $this->createEventMapper($db, $request);
        $this->mapperFactory->setMapperMockAs(EventMapper::class, $event_mapper);

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
            'username' => 'janebloggs',
            'display_name' => 'P Sherman'
        ];

        $talks_controller = new TalksController(new NullSpamCheckService(), [], $this->mapperFactory);

        $db = $this->getMockBuilder(mockPDO::class)->getMock();

        $this->talk_mapper = $this->createTalkMapper($db, $request);
        $this->talk_mapper
            ->expects($this->once())
            ->method('getSpeakerFromTalk')
            ->willReturn([
                'speaker_id' => 1,
                'ID' => 1
            ]);
        $this->mapperFactory->setMapperMockAs(TalkMapper::class, $this->talk_mapper);

        $user_mapper = $this->createUserMapper($db, $request);
        $this->mapperFactory->setMapperMockAs(UserMapper::class, $user_mapper);

        $event_mapper = $this->createEventMapper($db, $request);
        $this->mapperFactory->setMapperMockAs(EventMapper::class, $event_mapper);

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
            'username' => 'psherman',
            'display_name' => 'P Sherman'
        ];

        $talks_controller = new TalksController(new NullSpamCheckService(), [], $this->mapperFactory);

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
        $this->mapperFactory->setMapperMockAs(TalkMapper::class, $this->talk_mapper);

        $user_mapper = $this->createUserMapper($db, $request);
        $user_mapper
            ->expects($this->once())
            ->method('getUserIdFromUsername')
            ->willReturn(6);
        $this->mapperFactory->setMapperMockAs(UserMapper::class, $user_mapper);

        $pending_talk_claim_mapper = $this->mapperFactory->getMapperMock($this, PendingTalkClaimMapper::class);
        $pending_talk_claim_mapper
            ->expects($this->once())
            ->method('claimExists')
            ->willReturn(false);

        $event_mapper = $this->createEventMapper($db, $request);
        $this->mapperFactory->setMapperMockAs(EventMapper::class, $event_mapper);

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
            'username' => 'psherman',
            'display_name' => 'P Sherman'
        ];

        $talks_controller = new TalksController(new NullSpamCheckService(), [], $this->mapperFactory);

        $db = $this->getMockBuilder(mockPDO::class)->getMock();

        $this->talk_mapper = $this->createTalkMapper($db, $request);
        $this->talk_mapper
            ->expects($this->once())
            ->method('getSpeakerFromTalk')
            ->willReturn([
                'speaker_id' => null,
                'ID' => 1
            ]);
        $this->mapperFactory->setMapperMockAs(TalkMapper::class, $this->talk_mapper);

        $user_mapper = $this->createUserMapper($db, $request);
        $user_mapper
            ->expects($this->once())
            ->method('getUserIdFromUsername')
            ->willReturn(false);
        $this->mapperFactory->setMapperMockAs(UserMapper::class, $user_mapper);

        $event_mapper = $this->createEventMapper($db, $request);
        $this->mapperFactory->setMapperMockAs(EventMapper::class, $event_mapper);

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
            'username' => 'janebloggs',
            'display_name' => 'Jane Bloggs'
        ];

        $talks_controller = new TalksController(
            new NullSpamCheckService(),
            $this->config,
            $this->mapperFactory
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
        $this->mapperFactory->setMapperMockAs(TalkMapper::class, $this->talk_mapper);

        $user_mapper = $this->createUserMapper($db, $request);
        $user_mapper
            ->expects($this->once())
            ->method('getUserIdFromUsername')
            ->willReturn(2);
        $this->mapperFactory->setMapperMockAs(UserMapper::class, $user_mapper);

        $pending_talk_claim_mapper = $this->mapperFactory->getMapperMock($this, PendingTalkClaimMapper::class);
        $pending_talk_claim_mapper
            ->expects($this->once())
            ->method('claimExists')
            ->willReturn(false);

        $pending_talk_claim_mapper
            ->expects($this->once())
            ->method('claimTalkAsSpeaker')
            ->willReturn(true);

        $event_mapper = $this->createEventMapper($db, $request);
        $this->mapperFactory->setMapperMockAs(EventMapper::class, $event_mapper);

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
            'username' => 'psherman',
            'display_name' => 'P Sherman'
        ];

        $talks_controller = new TalksController(
            new NullSpamCheckService(),
            $this->config,
            $this->mapperFactory
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
        $this->mapperFactory->setMapperMockAs(TalkMapper::class, $this->talk_mapper);

        $user_mapper = $this->createUserMapper($db, $request);
        $user_mapper
            ->expects($this->once())
            ->method('getUserIdFromUsername')
            ->willReturn(1);
        $this->mapperFactory->setMapperMockAs(UserMapper::class, $user_mapper);

        $pending_talk_claim_mapper = $this->mapperFactory->getMapperMock($this, PendingTalkClaimMapper::class);
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
        $this->mapperFactory->setMapperMockAs(EventMapper::class, $event_mapper);

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
            'username' => 'janebloggs',
            'display_name' => 'Jane Bloggs'
        ];

        $talks_controller = new TalksController(new NullSpamCheckService(), [], $this->mapperFactory);

        $db = $this->getMockBuilder(mockPDO::class)->getMock();

        $this->talk_mapper = $this->createTalkMapper($db, $request);
        $this->talk_mapper
            ->expects($this->once())
            ->method('getSpeakerFromTalk')
            ->willReturn([
                'speaker_id' => null,
                'ID' => 1
            ]);
        $this->mapperFactory->setMapperMockAs(TalkMapper::class, $this->talk_mapper);

        $user_mapper = $this->createUserMapper($db, $request);
        $user_mapper
            ->expects($this->once())
            ->method('getUserIdFromUsername')
            ->willReturn(2);
        $this->talk_mapper
            ->expects($this->once())
            ->method('thisUserHasAdminOn')
            ->willReturn(false);
        $this->mapperFactory->setMapperMockAs(UserMapper::class, $user_mapper);

        $pending_talk_claim_mapper = $this->mapperFactory->getMapperMock($this, PendingTalkClaimMapper::class);
        $pending_talk_claim_mapper
            ->expects($this->once())
            ->method('claimExists')
            ->willReturn(PendingTalkClaimMapper::SPEAKER_CLAIM);

        $event_mapper = $this->createEventMapper($db, $request);
        $this->mapperFactory->setMapperMockAs(EventMapper::class, $event_mapper);

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
            'username' => 'janebloggs',
            'display_name' => 'Jane Bloggs'
        ];

        $talks_controller = new TalksController(new NullSpamCheckService(), [], $this->mapperFactory);

        $db = $this->getMockBuilder(mockPDO::class)->getMock();

        $this->talk_mapper = $this->createTalkMapper($db, $request);
        $this->talk_mapper
            ->expects($this->once())
            ->method('getSpeakerFromTalk')
            ->willReturn([
                'speaker_id' => null,
                'ID' => 1
            ]);
        $this->mapperFactory->setMapperMockAs(TalkMapper::class, $this->talk_mapper);

        $user_mapper = $this->createUserMapper($db, $request);
        $user_mapper
            ->expects($this->once())
            ->method('getUserIdFromUsername')
            ->willReturn(3);
        $this->talk_mapper
            ->expects($this->once())
            ->method('thisUserHasAdminOn')
            ->willReturn(false);
        $this->mapperFactory->setMapperMockAs(UserMapper::class, $user_mapper);

        $pending_talk_claim_mapper = $this->mapperFactory->getMapperMock($this, PendingTalkClaimMapper::class);
        $pending_talk_claim_mapper
            ->expects($this->once())
            ->method('claimExists')
            ->willReturn(PendingTalkClaimMapper::SPEAKER_CLAIM);

        $event_mapper = $this->createEventMapper($db, $request);
        $this->mapperFactory->setMapperMockAs(EventMapper::class, $event_mapper);

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
            'username' => 'psherman',
            'display_name' => 'P Sherman'
        ];

        $talks_controller = new TalksController(new NullSpamCheckService(), [], $this->mapperFactory);

        $db = $this->getMockBuilder(mockPDO::class)->getMock();

        $this->talk_mapper = $this->createTalkMapper($db, $request);
        $this->talk_mapper
            ->expects($this->once())
            ->method('getSpeakerFromTalk')
            ->willReturn([
                'speaker_id' => null,
                'ID' => 1
            ]);
        $this->mapperFactory->setMapperMockAs(TalkMapper::class, $this->talk_mapper);

        $user_mapper = $this->createUserMapper($db, $request);
        $user_mapper
            ->expects($this->once())
            ->method('getUserIdFromUsername')
            ->willReturn(1);
        $this->mapperFactory->setMapperMockAs(UserMapper::class, $user_mapper);

        $pending_talk_claim_mapper = $this->mapperFactory->getMapperMock($this, PendingTalkClaimMapper::class);
        $pending_talk_claim_mapper
            ->expects($this->once())
            ->method('claimExists')
            ->willReturn(PendingTalkClaimMapper::HOST_ASSIGN);

        $event_mapper = $this->createEventMapper($db, $request);
        $this->mapperFactory->setMapperMockAs(EventMapper::class, $event_mapper);
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
            'username' => 'janebloggs',
            'display_name' => 'Jane Bloggs',
        ];

        $talks_controller = new TalksController(new NullSpamCheckService(), [], $this->mapperFactory);

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
        $this->mapperFactory->setMapperMockAs(TalkMapper::class, $this->talk_mapper);

        $user_mapper = $this->createUserMapper($db, $request);
        $user_mapper
            ->expects($this->once())
            ->method('getUserIdFromUsername')
            ->willReturn(2);
        $this->mapperFactory->setMapperMockAs(UserMapper::class, $user_mapper);

        $pending_talk_claim_mapper = $this->mapperFactory->getMapperMock($this, PendingTalkClaimMapper::class);
        $pending_talk_claim_mapper
            ->expects($this->once())
            ->method('claimExists')
            ->willReturn(PendingTalkClaimMapper::HOST_ASSIGN);
        $pending_talk_claim_mapper
            ->expects($this->once())
            ->method('approveAssignmentAsSpeaker')
            ->willReturn(true);

        $event_mapper = $this->createEventMapper($db, $request);
        $this->mapperFactory->setMapperMockAs(EventMapper::class, $event_mapper);

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
            'username' => 'psherman',
            'display_name' => 'P Sherman',
        ];

        $talks_controller = new TalksController(
            new NullSpamCheckService(),
            $this->config,
            $this->mapperFactory
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

        $this->mapperFactory->setMapperMockAs(TalkMapper::class, $this->talk_mapper);

        $user_mapper = $this->createUserMapper($db, $request);
        $user_mapper
            ->expects($this->once())
            ->method('getUserIdFromUsername')
            ->willReturn(1);
        $this->mapperFactory->setMapperMockAs(UserMapper::class, $user_mapper);

        $pending_talk_claim_mapper = $this->mapperFactory->getMapperMock($this, PendingTalkClaimMapper::class);
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
        $this->mapperFactory->setMapperMockAs(EventMapper::class, $event_mapper);

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
            'username' => 'psherman',
            'display_name' => 'P Sherman',
            'comment' => 'Test Comment',
            'rating' => '3',
        ];

        /** @var TalkCommentEmailService&MockObject $talks_comment_email */
        $talks_comment_email =
            $this->getMockBuilder(TalkCommentEmailService::class)
                ->disableOriginalConstructor()
                ->getMock();

        $talks_comment_email->method('sendEmail');
        $emailServiceFactory = new EmailServiceFactoryForTests();
        $emailServiceFactory->setEmailServiceMockAs(TalkCommentEmailService::class, $talks_comment_email);
        $talks_controller = new TalksController(new NullSpamCheckService(), [], $this->mapperFactory,
            $emailServiceFactory);

        $db = $this->getMockBuilder(mockPDO::class)->getMock();

        $this->talk_mapper = $this->createTalkMapper($db, $request);

        $this->talk_mapper
            ->method('getSpeakerEmailsByTalkId')
            ->willReturn($speakerEmails);

        $this->mapperFactory->setMapperMockAs(TalkMapper::class, $this->talk_mapper);

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

        $this->mapperFactory->setMapperMockAs(TalkCommentMapper::class, $talk_comment);

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
            'username' => 'psherman',
            'display_name' => 'P Sherman',
            'rating' => '3',
        ];
        $db = $this->getMockBuilder(mockPDO::class)->getMock();

        $this->talk_mapper = $this->createTalkMapper($db, $request);

        $talks_controller = new TalksController(new NullSpamCheckService(), [], $this->mapperFactory);

        $this->mapperFactory->setMapperMockAs(TalkMapper::class, $this->talk_mapper);

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
            'username' => 'psherman',
            'display_name' => 'P Sherman',
            'comment' => 'Test Comment',
        ];
        $db = $this->getMockBuilder(mockPDO::class)->getMock();

        $this->talk_mapper = $this->createTalkMapper($db, $request);

        $talks_controller = new TalksController(new NullSpamCheckService(), [], $this->mapperFactory);

        $this->mapperFactory->setMapperMockAs(TalkMapper::class, $this->talk_mapper);

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
            'username' => 'psherman',
            'display_name' => 'P Sherman',
        ];

        $talks_controller = new TalksController(
            new NullSpamCheckService(),
            $this->config,
            $this->mapperFactory,
            $this->emailServiceFactory
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

        $this->mapperFactory->setMapperMockAs(TalkMapper::class, $this->talk_mapper);

        $user_mapper = $this->getMockBuilder(UserMapper::class)
            ->setConstructorArgs([$db, $request])
            ->getMock();

        $user_mapper
            ->expects($this->once())
            ->method('getUserIdFromUsername')
            ->willReturn(1);
        $this->mapperFactory->setMapperMockAs(UserMapper::class, $user_mapper);

        $pending_talk_claim_mapper = $this->mapperFactory->getMapperMock($this, PendingTalkClaimMapper::class);
        $pending_talk_claim_mapper
            ->expects($this->once())
            ->method('claimExists')
            ->willReturn(PendingTalkClaimMapper::SPEAKER_CLAIM);
        $pending_talk_claim_mapper
            ->expects($this->once())
            ->method('rejectClaimAsHost')
            ->willReturn(true);

        $emailService = $this->emailServiceFactory->getEmailServiceMock($this, TalkClaimRejectedEmailService::class);
        $emailService->method("sendEmail");

        $event_mapper = $this->createEventMapper($db, $request);
        $this->mapperFactory->setMapperMockAs(EventMapper::class, $event_mapper);

        $this->assertTrue($talks_controller->removeSpeakerForTalk($request, $db));
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
            'verbose' => 'yes',
        ];

        $db = $this->getMockBuilder(mockPDO::class)->getMock();

        $this->talk_mapper = $this->createVerboseTalkMapper($db, $request);

        $expected = [
            ['slides_link' => 'http://slideshare.net'],
            ['code_link' => 'https://github.com'],
        ];

        $talks_controller = new TalksController(new NullSpamCheckService(), [], $this->mapperFactory);
        $this->mapperFactory->setMapperMockAs(TalkMapper::class, $this->talk_mapper);

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

        $talks_controller = new TalksController(new NullSpamCheckService(), [], $this->mapperFactory);
        $this->mapperFactory->setMapperMockAs(TalkCommentMapper::class, $talkComment);

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

        $talks_controller = new TalksController(new NullSpamCheckService(), [], $this->mapperFactory);
        $this->mapperFactory->setMapperMockAs(TalkMapper::class, $talkMapper);

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
            'title' => 'linux',
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

        $talks_controller = new TalksController(new NullSpamCheckService(), [], $this->mapperFactory);

        $this->mapperFactory->setMapperMockAs(TalkMapper::class, $talkMapper);

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

    public function testPostActionThrowsExceptionWhenCommentInvalid()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage("Comment failed spam check");
        $this->expectExceptionCode(Http::BAD_REQUEST);

        $this->request->user_id = 3;
        $this->request->url_elements = [3 => 5, 4 => 'comments'];
        $this->request->expects(self::exactly(3))->method("getParameter")
            ->withConsecutive(["comment"], ["rating"], ["private"])
            ->willReturnOnConsecutiveCalls("comment", "rating", 1);
        $oauthModel = $this->createMock(OAuthModel::class);
        $this->request->expects(self::once())->method("getOauthModel")->with($this->db)->willReturn($oauthModel);
        $this->request->expects(self::once())->method("getAccessToken")->willReturn("accessToken");
        $oauthModel->expects(self::once())->method("getConsumerName")->with("accessToken")->willReturn("consumerName");
        $commentMapper = $this->mapperFactory->getMapperMock($this, TalkCommentMapper::class);
        $commentMapper->expects(self::once())->method("checkRateLimit");
        $this->request->expects(self::once())->method("getClientIP")->willReturn(10);
        $this->request->expects(self::once())->method("getClientUserAgent")->willReturn("test");
        $this->spawnChecker->expects(self::once())->method("isCommentAcceptable")->with("comment", 10,
            "test")->willReturn(false);

        $this->sut->postAction($this->request, $this->db);
    }

    public function testPostActionThrowsExceptionWhenCommentNotNewId()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage("The comment could not be stored");
        $this->expectExceptionCode(Http::BAD_REQUEST);

        $this->request->user_id = 3;
        $this->request->url_elements = [3 => 5, 4 => 'comments'];
        $this->request->expects(self::exactly(3))->method("getParameter")
            ->withConsecutive(["comment"], ["rating"], ["private"])
            ->willReturnOnConsecutiveCalls("comment", "rating", 1);
        $oauthModel = $this->createMock(OAuthModel::class);
        $this->request->expects(self::once())->method("getOauthModel")->with($this->db)->willReturn($oauthModel);
        $this->request->expects(self::once())->method("getAccessToken")->willReturn("accessToken");
        $oauthModel->expects(self::once())->method("getConsumerName")->with("accessToken")->willReturn("consumerName");
        $commentMapper = $this->mapperFactory->getMapperMock($this, TalkCommentMapper::class);
        $commentMapper->expects(self::once())->method("checkRateLimit");
        $this->request->expects(self::once())->method("getClientIP")->willReturn(10);
        $this->request->expects(self::once())->method("getClientUserAgent")->willReturn("test");
        $this->spawnChecker->expects(self::once())->method("isCommentAcceptable")->with("comment", 10,
            "test")->willReturn(true);
        $commentMapper->expects(self::once())->method("hasUserRatedThisTalk")->with(3, 5)->willReturn(false);
        $this->talk_mapper->expects(self::once())->method("isUserASpeakerOnTalk")->with(5, 3)->willReturn(true);
        $commentMapper->expects(self::once())->method("save")->willReturn(false);

        $this->sut->postAction($this->request, $this->db);
    }

    public function testPostActionStarredWorksAsExpected()
    {
        $this->request->user_id = 3;
        $this->request->url_elements = [3 => 5, 4 => 'starred'];
        $this->request->base = "hi";
        $this->request->path_info = "path";

        $this->talk_mapper->expects(self::once())->method("setUserStarred")->with(5, 3);
        $view = $this->getMockBuilder(ApiView::class)->disableOriginalConstructor()->getMock();
        $this->request->expects(self::once())->method("getView")->willReturn($view);
        $view->expects(self::once())->method("setHeader")->with("Location", "hipath");
        $view->expects(self::once())->method("setResponseCode")->with(Http::CREATED);

        $this->sut->postAction($this->request, $this->db);
    }

    public function testPostActionThrowsExceptionWhenUnsupportedOperation()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage("Operation not supported, sorry");
        $this->expectExceptionCode(Http::NOT_FOUND);

        $this->request->user_id = 3;
        $this->request->url_elements = [3 => 5, 4 => 'notSupported'];

        $this->sut->postAction($this->request, $this->db);
    }

    public function testPostActionThrowsExceptionWhenUnsupportedMethod()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage("method not supported - sorry");

        $this->request->user_id = 3;
        $this->request->url_elements = [3 => 5];

        $this->sut->postAction($this->request, $this->db);
    }

    public function testRemoveSpeakerForTalkThrowsExceptionWhenNotAdmin()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage("You do not have permission to reject the speaker claim on this talk");
        $this->expectExceptionCode(Http::FORBIDDEN);

        $this->request->user_id = 3;
        $this->request->url_elements = [3 => 5];

        $talkModel = new TalkModel([]);
        $talkModel->ID = 7;
        $talkModel->event_id = 1;

        $eventMapper = $this->mapperFactory->getMapperMock($this, EventMapper::class);
        $eventMapper->expects(self::once())->method("getEventById")->with(1);
        $this->talk_mapper->expects(self::once())->method("getTalkById")->with(5)->willReturn($talkModel);
        $this->talk_mapper->expects(self::once())->method("thisUserHasAdminOn")->with(7)->willReturn(false);

        $this->sut->removeSpeakerForTalk($this->request, $this->db);
    }

    public function testRemoveSpeakerForTalkThrowsExceptionWhenUserNotFound()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage("Specified user not found");
        $this->expectExceptionCode(Http::NOT_FOUND);

        $this->request->user_id = 3;
        $this->request->url_elements = [3 => 5];

        $talkModel = new TalkModel([]);
        $talkModel->ID = 7;
        $talkModel->event_id = 1;

        $eventMapper = $this->mapperFactory->getMapperMock($this, EventMapper::class);
        $eventMapper->expects(self::once())->method("getEventById")->with(1);
        $this->talk_mapper->expects(self::once())->method("getTalkById")->with(5)->willReturn($talkModel);
        $this->talk_mapper->expects(self::once())->method("thisUserHasAdminOn")->with(7)->willReturn(true);
        $this->request->expects(self::exactly(2))->method("getParameter")
            ->withConsecutive(["display_name", ''], ['username', ''])->willReturnOnConsecutiveCalls("displayName",
                'username');
        $userMapper = $this->mapperFactory->getMapperMock($this, UserMapper::class);
        $userMapper->expects(self::once())->method("getUserIdFromUsername")->with("username")->willReturn(false);

        $this->sut->removeSpeakerForTalk($this->request, $this->db);
    }

    public function testRemoveSpeakerForTalkThrowsExceptionWhenNoSpeakerMatching()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage("No speaker matching that name found");
        $this->expectExceptionCode(WebDAV::UNPROCESSABLE_ENTITY);

        $this->request->user_id = 3;
        $this->request->url_elements = [3 => 5];

        $talkModel = new TalkModel([]);
        $talkModel->ID = 7;
        $talkModel->event_id = 1;

        $eventMapper = $this->mapperFactory->getMapperMock($this, EventMapper::class);
        $eventMapper->expects(self::once())->method("getEventById")->with(1);
        $this->talk_mapper->expects(self::once())->method("getTalkById")->with(5)->willReturn($talkModel);
        $this->talk_mapper->expects(self::once())->method("thisUserHasAdminOn")->with(7)->willReturn(true);
        $this->request->expects(self::exactly(2))->method("getParameter")
            ->withConsecutive(["display_name", ''], ['username', ''])->willReturnOnConsecutiveCalls("displayName",
                'username');
        $userMapper = $this->mapperFactory->getMapperMock($this, UserMapper::class);
        $userMapper->expects(self::once())->method("getUserIdFromUsername")->with("username")->willReturn(true);
        $this->talk_mapper->expects(self::once())->method("getSpeakerFromTalk")->with(7,
            "displayName")->willReturn(false);

        $this->sut->removeSpeakerForTalk($this->request, $this->db);
    }

    public function testRemoveSpeakerForTalkThrowsExceptionWhenTalkClaimed()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage("Talk already claimed");
        $this->expectExceptionCode(WebDAV::UNPROCESSABLE_ENTITY);

        $this->request->user_id = 3;
        $this->request->url_elements = [3 => 5];

        $talkModel = new TalkModel([]);
        $talkModel->ID = 7;
        $talkModel->event_id = 1;

        $eventMapper = $this->mapperFactory->getMapperMock($this, EventMapper::class);
        $eventMapper->expects(self::once())->method("getEventById")->with(1);
        $this->talk_mapper->expects(self::once())->method("getTalkById")->with(5)->willReturn($talkModel);
        $this->talk_mapper->expects(self::once())->method("thisUserHasAdminOn")->with(7)->willReturn(true);
        $this->request->expects(self::exactly(2))->method("getParameter")
            ->withConsecutive(["display_name", ''], ['username', ''])->willReturnOnConsecutiveCalls("displayName",
                'username');
        $userMapper = $this->mapperFactory->getMapperMock($this, UserMapper::class);
        $userMapper->expects(self::once())->method("getUserIdFromUsername")->with("username")->willReturn(true);
        $this->talk_mapper->expects(self::once())->method("getSpeakerFromTalk")->with(7,
            "displayName")->willReturn(['speaker_id' => 10]);

        $this->sut->removeSpeakerForTalk($this->request, $this->db);
    }

    public function testRemoveSpeakerForTalkThrowsExceptionWhenNoDisplayName()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage("You must provide a display name and a username");
        $this->expectExceptionCode(Http::BAD_REQUEST);

        $this->request->user_id = 3;
        $this->request->url_elements = [3 => 5];

        $talkModel = new TalkModel([]);
        $talkModel->ID = 7;
        $talkModel->event_id = 1;

        $eventMapper = $this->mapperFactory->getMapperMock($this, EventMapper::class);
        $eventMapper->expects(self::once())->method("getEventById")->with(1);
        $this->talk_mapper->expects(self::once())->method("getTalkById")->with(5)->willReturn($talkModel);
        $this->talk_mapper->expects(self::once())->method("thisUserHasAdminOn")->with(7)->willReturn(true);
        $this->request->expects(self::exactly(2))->method("getParameter")
            ->withConsecutive(["display_name", ''], ['username', ''])->willReturnOnConsecutiveCalls("", 'username');
        $userMapper = $this->mapperFactory->getMapperMock($this, UserMapper::class);
        $userMapper->expects(self::once())->method("getUserIdFromUsername")->with("username")->willReturn(true);
        $this->talk_mapper->expects(self::once())->method("getSpeakerFromTalk")->with(7,
            "")->willReturn(['speaker_id' => 0]);

        $this->sut->removeSpeakerForTalk($this->request, $this->db);
    }

    public function testRemoveSpeakerForTalkThrowsExceptionWhenClaimHasProblem()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage("There was a problem with the claim");
        $this->expectExceptionCode(Http::INTERNAL_SERVER_ERROR);

        $this->request->user_id = 3;
        $this->request->url_elements = [3 => 5];

        $talkModel = new TalkModel([]);
        $talkModel->ID = 7;
        $talkModel->event_id = 1;

        $eventMapper = $this->mapperFactory->getMapperMock($this, EventMapper::class);
        $eventMapper->expects(self::once())->method("getEventById")->with(1);
        $this->talk_mapper->expects(self::once())->method("getTalkById")->with(5)->willReturn($talkModel);
        $this->talk_mapper->expects(self::once())->method("thisUserHasAdminOn")->with(7)->willReturn(true);
        $this->request->expects(self::exactly(2))->method("getParameter")
            ->withConsecutive(["display_name", ''], ['username', ''])->willReturnOnConsecutiveCalls("displayName",
                'username');
        $userMapper = $this->mapperFactory->getMapperMock($this, UserMapper::class);
        $userMapper->expects(self::once())->method("getUserIdFromUsername")->with("username")->willReturn(true);
        $this->talk_mapper->expects(self::once())->method("getSpeakerFromTalk")->with(7,
            "displayName")->willReturn(['speaker_id' => 0, "ID" => 10]);
        $pendingTalk = $this->mapperFactory->getMapperMock($this, PendingTalkClaimMapper::class);
        $pendingTalk->expects(self::once())->method("claimExists")->with(7, 1,
            10)->willReturn(PendingTalkClaimMapper::HOST_ASSIGN);

        $this->sut->removeSpeakerForTalk($this->request, $this->db);
    }

    public function testRemoveSpeakerForTalkThrowsExceptionWhenProblemWithAssigning()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage("There was a problem assigning the talk");
        $this->expectExceptionCode(Http::INTERNAL_SERVER_ERROR);

        $this->request->user_id = 3;
        $this->request->url_elements = [3 => 5];

        $talkModel = new TalkModel([]);
        $talkModel->ID = 7;
        $talkModel->event_id = 1;

        $eventMapper = $this->mapperFactory->getMapperMock($this, EventMapper::class);
        $eventMapper->expects(self::once())->method("getEventById")->with(1);
        $this->talk_mapper->expects(self::once())->method("getTalkById")->with(5)->willReturn($talkModel);
        $this->talk_mapper->expects(self::once())->method("thisUserHasAdminOn")->with(7)->willReturn(true);
        $this->request->expects(self::exactly(2))->method("getParameter")
            ->withConsecutive(["display_name", ''], ['username', ''])->willReturnOnConsecutiveCalls("displayName",
                'username');
        $userMapper = $this->mapperFactory->getMapperMock($this, UserMapper::class);
        $userMapper->expects(self::once())->method("getUserIdFromUsername")->with("username")->willReturn(true);
        $this->talk_mapper->expects(self::once())->method("getSpeakerFromTalk")->with(7,
            "displayName")->willReturn(['speaker_id' => 0, "ID" => 10]);
        $pendingTalk = $this->mapperFactory->getMapperMock($this, PendingTalkClaimMapper::class);
        $pendingTalk->expects(self::once())->method("claimExists")->with(7, 1,
            10)->willReturn(PendingTalkClaimMapper::SPEAKER_CLAIM);
        $pendingTalk->expects(self::once())->method("rejectClaimAsHost")->with(7, 1, 10)->willReturn(false);

        $this->sut->removeSpeakerForTalk($this->request, $this->db);
    }
}
