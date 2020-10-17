<?php


namespace Joindin\Api\Test\Controller;


use Exception;
use Hoa\Event\Event;
use Joindin\Api\Controller\EventCommentsController;
use Joindin\Api\Factory\EmailServiceFactory;
use Joindin\Api\Factory\MapperFactory;
use Joindin\Api\Model\EventCommentMapper;
use Joindin\Api\Model\EventCommentReportModelCollection;
use Joindin\Api\Model\EventMapper;
use Joindin\Api\Model\OAuthModel;
use Joindin\Api\Model\UserMapper;
use Joindin\Api\Request;
use Joindin\Api\Service\EventCommentReportedEmailService;
use Joindin\Api\Service\SpamCheckServiceInterface;
use Joindin\Api\Test\EmailServiceFactoryForTests;
use Joindin\Api\Test\MapperFactoryForTests;
use Joindin\Api\View\ApiView;
use PDO;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Teapot\StatusCode\Http;
use UnexpectedValueException;

/**
 * @property MockObject request
 * @property MockObject apiView
 * @property MockObject db
 * @property MapperFactoryForTests mapperFactory
 * @property EventCommentsController sut
 * @property EmailServiceFactoryForTests emailServiceFactory
 * @property MockObject spawnCheckServiceInterface
 */
class EventCommentsControllerTest extends TestCase
{
    private $eventCommentReportModelCollection;

    public function setUp(): void
    {
        $this->request = $this->getMockBuilder(Request::class)->disableOriginalConstructor()->getMock();
        $this->request->paginationParameters['start'] = "hi";
        $this->request->paginationParameters['resultsperpage'] = 5;

        $this->db = $this->getMockBuilder(PDO::class)->disableOriginalConstructor()->getMock();
        $this->apiView = $this->getMockBuilder(ApiView::class)->disableOriginalConstructor()->getMock();

        $this->mapperFactory = new MapperFactoryForTests();
        $this->mapperFactory->setMapperMocks($this, EventCommentMapper::class, EventMapper::class, UserMapper::class);
        $this->emailServiceFactory = new EmailServiceFactoryForTests();
        $this->emailServiceFactory->setEmailServiceMocks($this, EventCommentReportedEmailService::class);

        $this->spawnCheckServiceInterface = $this->getMockBuilder(SpamCheckServiceInterface::class)->getMock();
        $this->sut = new EventCommentsController($this->spawnCheckServiceInterface, [], $this->mapperFactory, $this->emailServiceFactory);
    }

    public function testGetCommentsThrowsIfNoCommentsFound()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Comment not found');
        $this->expectExceptionCode(Http::NOT_FOUND);

        $this->request->url_elements[3] = 5;

        $this->mapperFactory->getMapperMock($this, EventCommentMapper::class)
            ->expects(self::once())->method("getCommentById")->with(5)->willReturn(false);

        $this->assertNull($this->sut->getComments($this->request, $this->db));
    }

    public function testGetCommentsReturnsFalseIfNoCommentId()
    {
        $this->assertFalse($this->sut->getComments($this->request, $this->db));
    }

    public function testGetCommentsWorksAsExpected()
    {
        $this->request->url_elements[3] = 5;
        $this->mapperFactory->getMapperMock($this, EventCommentMapper::class)
            ->expects(self::once())->method("getCommentById")->with(5)->willReturn(["something", "another"]);

        $this->assertEquals(["something", "another"], $this->sut->getComments($this->request, $this->db));
    }

    public function testGetReportedThrowsExceptionIfNoEventId()
    {
        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage('Event not found');
        $this->expectExceptionCode(Http::NOT_FOUND);

        $this->sut->getReported($this->request, $this->db);
    }

    public function testGetReportedThrowsExceptionIfNoUserId()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('You must log in to do that');
        $this->expectExceptionCode(Http::UNAUTHORIZED);

        $this->request->url_elements[3] = 5;

        $this->sut->getReported($this->request, $this->db);
    }

    public function testGetReportedThrowsExceptionIfNoPermission()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage("You don't have permission to do that");
        $this->expectExceptionCode(Http::FORBIDDEN);

        $this->request->url_elements[3] = 5;
        $this->request->user_id = 2;

        $this->mapperFactory->getMapperMock($this, EventMapper::class)
            ->expects(self::once())->method("thisUserHasAdminOn")->with(5)->willReturn(false);

        $this->sut->getReported($this->request, $this->db);
    }

    public function testGetReportedWorksAsExpected()
    {
        $this->request->url_elements[3] = 5;
        $this->request->user_id = 2;

        $this->mapperFactory->getMapperMock($this, EventMapper::class)
            ->expects(self::once())->method("thisUserHasAdminOn")->with(5)->willReturn(true);

        $eventCommentReportModelCollection = $this->getMockBuilder(EventCommentReportModelCollection::class)->disableOriginalConstructor()->getMock();
        $eventCommentReportModelCollection->expects(self::once())->method("getOutputView")->willReturn("Some output");
        $this->mapperFactory->getMapperMock($this, EventMapper::class)
            ->expects(self::once())->method("thisUserHasAdminOn")->with(5)->willReturn(true);
        $this->mapperFactory->getMapperMock($this, EventCommentMapper::class)
            ->expects(self::once())->method("getReportedCommentsByEventId")->willReturn($eventCommentReportModelCollection);

        self::assertEquals("Some output", $this->sut->getReported($this->request, $this->db));
    }

    public function testCreateCommentThrowsExceptionIfCommentEventIdNotSet()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage("POST expects a comment representation sent to a specific event URL");
        $this->expectExceptionCode(Http::BAD_REQUEST);

        $this->sut->createComment($this->request, $this->db);
    }

    public function testCreateCommentThrowsExceptionIfUserIdNotSet()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage("You must log in to comment");

        $this->request->url_elements[3] = 5;

        $this->sut->createComment($this->request, $this->db);
    }

    public function testCreateCommentThrowsExceptionIfRatingFalse()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('The field "rating" is required');
        $this->expectExceptionCode(Http::BAD_REQUEST);

        $this->request->url_elements[3] = 5;
        $this->request->user_id = 2;

        $this->mapperFactory->getMapperMock($this, UserMapper::class)
            ->expects(self::once())->method("getUserById")->with(2)->willReturn(["users" => [0 => ""]]);
        $this->request->expects(self::once())->method("getParameter")->with("rating", false)->willReturn(false);

        $this->sut->createComment($this->request, $this->db);
    }

    public function testCreateCommentThrowsExceptionIfRatingNotNumeric()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('The field "rating" must be a number');
        $this->expectExceptionCode(Http::BAD_REQUEST);

        $this->request->url_elements[3] = 5;
        $this->request->user_id = 2;

        $this->mapperFactory->getMapperMock($this, UserMapper::class)
            ->expects(self::once())->method("getUserById")->with(2)->willReturn(["users" => [0 => ""]]);
        $this->request->expects(self::once())->method("getParameter")->with("rating", false)->willReturn("something");

        $this->sut->createComment($this->request, $this->db);
    }

    public function testCreateCommentThrowsExceptionIfCommentNotSet()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('The field "comment" is required');
        $this->expectExceptionCode(Http::BAD_REQUEST);

        $this->request->url_elements[3] = 5;
        $this->request->user_id = 2;

        $this->mapperFactory->getMapperMock($this, UserMapper::class)
            ->expects(self::once())->method("getUserById")->with(2)->willReturn(["users" => [0 => ""]]);

        $map = [
            ["rating", false, 10],
            ["comment", ""]
        ];

        $this->request->expects(self::exactly(2))->method("getParameter")->will(self::returnValueMap($map));

        $this->sut->createComment($this->request, $this->db);
    }

    public function testCreateCommentThrowsExceptionIfCommentNotAcceptable()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Comment failed spam check');
        $this->expectExceptionCode(Http::BAD_REQUEST);

        $this->request->url_elements[3] = 5;
        $this->request->user_id = 2;

        $this->mapperFactory->getMapperMock($this, UserMapper::class)
            ->expects(self::once())->method("getUserById")->with(2)->willReturn(["users" => [0 => ["full_name" => ""]]]);

        $this->request->expects(self::exactly(2))->method("getParameter")->withConsecutive(["rating", false],
            ['comment'])->will(self::onConsecutiveCalls(10, "comment"));
        $oauthModel = $this->getMockBuilder(OAuthModel::class)->disableOriginalConstructor()->getMock();
        $this->request->expects(self::once())->method("getAccessToken")->willReturn("token");
        $this->request->expects(self::once())->method("getOauthModel")->with($this->db)->willReturn($oauthModel);
        $oauthModel->expects(self::once())->method("getConsumerName")->with("token")->willReturn("consumerName");
        $this->request->expects(self::once())->method("getClientIp")->willReturn("clientIp");
        $this->request->expects(self::once())->method("getClientUserAgent")->willReturn("userAgent");
        $this->spawnCheckServiceInterface->expects(self::once())->method("isCommentAcceptable")->with("comment",
            "clientIp", "userAgent")->willReturn(false);

        $this->sut->createComment($this->request, $this->db);
    }

    public function testCreateCommentThrowsExceptionIfCommentRatingToSmall()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('The field "rating" must be a number (1-5)');
        $this->expectExceptionCode(Http::BAD_REQUEST);

        $this->request->url_elements[3] = 5;
        $this->request->user_id = 2;

        $this->mapperFactory->getMapperMock($this, UserMapper::class)
            ->expects(self::once())->method("getUserById")->with(2)->willReturn(["users" => [0 => ["full_name" => ""]]]);

        $this->request->expects(self::exactly(2))->method("getParameter")->withConsecutive(["rating", false],
            ['comment'])->will(self::onConsecutiveCalls(0, "comment"));
        $oauthModel = $this->getMockBuilder(OAuthModel::class)->disableOriginalConstructor()->getMock();
        $this->request->expects(self::once())->method("getAccessToken")->willReturn("token");
        $this->request->expects(self::once())->method("getOauthModel")->with($this->db)->willReturn($oauthModel);
        $oauthModel->expects(self::once())->method("getConsumerName")->with("token")->willReturn("consumerName");
        $this->request->expects(self::once())->method("getClientIp")->willReturn("clientIp");
        $this->request->expects(self::once())->method("getClientUserAgent")->willReturn("userAgent");
        $this->spawnCheckServiceInterface->expects(self::once())->method("isCommentAcceptable")->with("comment",
            "clientIp", "userAgent")->willReturn(true);
        $this->mapperFactory->getMapperMock($this, EventCommentMapper::class)
            ->expects(self::any())->method("hasUserRatedThisEvent")->with(2, 5)->willReturn(false);
        $this->mapperFactory->getMapperMock($this, EventMapper::class)
            ->expects(self::any())->method("isUserAHostOn")->with(2, 5)->willReturn(false);

        $this->sut->createComment($this->request, $this->db);
    }

    public function testCreateCommentThrowsExceptionIfSaveThrowsException()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('hi');
        $this->expectExceptionCode(Http::BAD_REQUEST);

        $this->request->url_elements[3] = 5;
        $this->request->user_id = 2;

        $this->mapperFactory->getMapperMock($this, UserMapper::class)
            ->expects(self::once())->method("getUserById")->with(2)->willReturn(["users" => [0 => ["full_name" => ""]]]);

        $this->request->expects(self::exactly(2))->method("getParameter")->withConsecutive(["rating", false],
            ['comment'])->will(self::onConsecutiveCalls(4, "comment"));
        $oauthModel = $this->getMockBuilder(OAuthModel::class)->disableOriginalConstructor()->getMock();
        $this->request->expects(self::once())->method("getAccessToken")->willReturn("token");
        $this->request->expects(self::once())->method("getOauthModel")->with($this->db)->willReturn($oauthModel);
        $oauthModel->expects(self::once())->method("getConsumerName")->with("token")->willReturn("consumerName");
        $this->request->expects(self::once())->method("getClientIp")->willReturn("clientIp");
        $this->request->expects(self::once())->method("getClientUserAgent")->willReturn("userAgent");
        $this->spawnCheckServiceInterface->expects(self::once())->method("isCommentAcceptable")->with("comment",
            "clientIp", "userAgent")->willReturn(true);
        $this->mapperFactory->getMapperMock($this, EventCommentMapper::class)
            ->expects(self::any())->method("hasUserRatedThisEvent")->with(2, 5)->willReturn(true);
        $this->mapperFactory->getMapperMock($this, EventMapper::class)
            ->expects(self::any())->method("isUserAHostOn")->with(2, 5)->willReturn(true);
        $this->mapperFactory->getMapperMock($this, EventCommentMapper::class)
            ->expects(self::any())->method("save")->with(self::anything())->willThrowException(new Exception("hi"));

        $this->sut->createComment($this->request, $this->db);
    }

    public function testCreateCommentWorksAsExpected()
    {
        $this->request->url_elements[3] = 5;
        $this->request->user_id = 2;
        $this->request->base = 'hi';
        $this->request->version = '10';

        $this->mapperFactory->getMapperMock($this, UserMapper::class)
            ->expects(self::once())->method("getUserById")->with(2)->willReturn(["users" => [0 => ["full_name" => ""]]]);

        $this->request->expects(self::exactly(2))->method("getParameter")->withConsecutive(["rating", false],
            ['comment'])->will(self::onConsecutiveCalls(4, "comment"));
        $oauthModel = $this->getMockBuilder(OAuthModel::class)->disableOriginalConstructor()->getMock();
        $this->request->expects(self::once())->method("getAccessToken")->willReturn("token");
        $this->request->expects(self::once())->method("getOauthModel")->with($this->db)->willReturn($oauthModel);
        $oauthModel->expects(self::once())->method("getConsumerName")->with("token")->willReturn("consumerName");
        $this->request->expects(self::once())->method("getClientIp")->willReturn("clientIp");
        $this->request->expects(self::once())->method("getClientUserAgent")->willReturn("userAgent");
        $this->spawnCheckServiceInterface->expects(self::once())->method("isCommentAcceptable")->with("comment",
            "clientIp", "userAgent")->willReturn(true);
        $eventCommentMapper = $this->mapperFactory->getMapperMock($this, EventCommentMapper::class);
        $eventMapper = $this->mapperFactory->getMapperMock($this, EventMapper::class);
        $eventCommentMapper->expects(self::once())->method("hasUserRatedThisEvent")->with(2, 5)->willReturn(false);
        $eventMapper->expects(self::any())->method("isUserAHostOn")->with(2, 5)->willReturn(true);
        $eventCommentMapper->expects(self::once())->method("save")->willReturn(3);
        $eventMapper->expects(self::once())->method("cacheCommentCount")->with(5);
        $this->request->expects(self::once())->method("getView")->willReturn($this->apiView);
        $this->apiView->expects(self::once())->method("setHeader")->with("Location", "hi/10/event_comments/3");
        $this->apiView->expects(self::once())->method("setResponseCode")->with(Http::CREATED);

        $this->sut->createComment($this->request, $this->db);
    }

    public function testReportCommentThrowsExceptionWhenNotLoggedIn()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('You must log in to report a comment');
        $this->expectExceptionCode(Http::UNAUTHORIZED);

        $this->sut->reportComment($this->request, $this->db);
    }

    public function testReportCommentThrowsExceptionWhenCommentNotFound()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Comment not found');
        $this->expectExceptionCode(Http::NOT_FOUND);

        $this->request->user_id = 2;
        $this->request->url_elements[3] = 5;

        $this->mapperFactory->getMapperMock($this, EventCommentMapper::class)
            ->expects(self::once())->method("getCommentInfo")->with(5)->willReturn(false);

        $this->sut->reportComment($this->request, $this->db);
    }

    public function testReportCommentWorksAsExpected()
    {
        $this->request->user_id = 2;
        $this->request->url_elements[3] = 5;
        $this->request->base = 'hi';
        $this->request->version = '10';

        $eventCommentMapper = $this->mapperFactory->getMapperMock($this, EventCommentMapper::class);
        $eventCommentMapper->expects(self::once())->method("getCommentInfo")->with(5)->willReturn(["event_id" => "eventId"]);
        $eventCommentMapper->expects(self::once())->method("userReportedComment")->with(5, 2);
        $eventCommentMapper->expects(self::once())->method("getCommentById")
            ->with(5, true, true)->willReturn(["comment"]);
        $eventMapper = $this->mapperFactory->getMapperMock($this, EventMapper::class);
        $eventMapper->expects(self::once())->method("getHostsEmailAddresses")->with("eventId")->willReturn(["recipients"]);
        $eventMapper->expects(self::once())->method("getEventById")->with("eventId", true, true)->willReturn(["event"]);

        $eventCommentReporterEmailService = $this->emailServiceFactory->getEmailServiceMock($this, EventCommentReportedEmailService::class);
        $eventCommentReporterEmailService->expects(self::once())->method("sendEmail");
        $this->request->expects(self::once())->method("getView")->willReturn($this->apiView);
        $this->apiView->expects(self::once())->method("setHeader")->with("Location", "hi/10/events/eventId/comments");
        $this->apiView->expects(self::once())->method("setResponseCode")->with(Http::ACCEPTED);

        $this->sut->reportComment($this->request, $this->db);
    }

    public function testModerateReportedCommentThrowsExceptionWhenNotLoggedIn()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('You must log in to moderate a comment');
        $this->expectExceptionCode(Http::UNAUTHORIZED);

        $this->sut->moderateReportedComment($this->request, $this->db);
    }

    public function testModerateReportedCommentThrowsExceptionWhenCommentNotFound()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Comment not found');
        $this->expectExceptionCode(Http::NOT_FOUND);

        $this->request->user_id = 2;
        $this->request->url_elements[3] = 5;

        $this->mapperFactory->getMapperMock($this, EventCommentMapper::class)
            ->expects(self::once())->method("getCommentInfo")->with(5)->willReturn(false);

        $this->sut->moderateReportedComment($this->request, $this->db);
    }

    public function testModerateReportedCommentThrowsExceptionWhenNotPermitted()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage("You don't have permission to do that");
        $this->expectExceptionCode(Http::FORBIDDEN);

        $this->request->user_id = 2;
        $this->request->url_elements[3] = 5;

        $this->mapperFactory->getMapperMock($this, EventCommentMapper::class)
            ->expects(self::once())->method("getCommentInfo")->with(5)->willReturn(["event_id" => "eventId"]);
        $this->mapperFactory->getMapperMock($this, EventMapper::class)
            ->expects(self::once())->method("thisUserHasAdminOn")->with("eventId")->willReturn(false);

        $this->sut->moderateReportedComment($this->request, $this->db);
    }

    public function testModerateReportedCommentThrowsExceptionWhenUnexpectedDecision()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage("Unexpected decision");
        $this->expectExceptionCode(Http::BAD_REQUEST);

        $this->request->user_id = 2;
        $this->request->url_elements[3] = 5;

        $this->mapperFactory->getMapperMock($this, EventCommentMapper::class)
            ->expects(self::once())->method("getCommentInfo")->with(5)->willReturn(["event_id" => "eventId"]);
        $this->mapperFactory->getMapperMock($this, EventMapper::class)
            ->expects(self::once())->method("thisUserHasAdminOn")->with("eventId")->willReturn(true);
        $this->request->expects(self::once())->method("getParameter")->with("decision")->willReturn("different decision");

        $this->sut->moderateReportedComment($this->request, $this->db);
    }

    public function testModerateReportedCommentWorksAsExpected()
    {
        $this->request->user_id = 2;
        $this->request->url_elements[3] = 5;
        $this->request->base = 'hi';
        $this->request->version = '10';

        $eventCommentMapper = $this->mapperFactory->getMapperMock($this, EventCommentMapper::class);
        $eventCommentMapper
            ->expects(self::once())->method("getCommentInfo")->with(5)->willReturn(["event_id" => "eventId"]);
        $this->mapperFactory->getMapperMock($this, EventMapper::class)
            ->expects(self::once())->method("thisUserHasAdminOn")->with("eventId")->willReturn(true);
        $this->request->expects(self::once())->method("getParameter")->with("decision")->willReturn("approved");
        $eventCommentMapper->expects(self::once())->method("moderateReportedComment")->with("approved", 5, 2);

        $this->request->expects(self::once())->method("getView")->willReturn($this->apiView);
        $this->apiView->expects(self::once())->method("setHeader")->with("Location", "hi/10/events/eventId/comments");
        $this->apiView->expects(self::once())->method("setResponseCode")->with(Http::NO_CONTENT);

        $this->sut->moderateReportedComment($this->request, $this->db);
    }
}
