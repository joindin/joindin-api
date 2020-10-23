<?php


namespace Joindin\Api\Test\Controller;

use DateInterval;
use DateTime;
use Exception;
use Joindin\Api\Controller\TalkCommentsController;
use Joindin\Api\Model\EventMapper;
use Joindin\Api\Model\TalkCommentMapper;
use Joindin\Api\Model\TalkCommentReportModelCollection;
use Joindin\Api\Request;
use Joindin\Api\Service\CommentReportedEmailService;
use Joindin\Api\Test\EmailServiceFactoryForTests;
use Joindin\Api\Test\MapperFactoryForTests;
use Joindin\Api\View\ApiView;
use PDO;
use PHPUnit\Framework\TestCase;
use Teapot\StatusCode\Http;
use UnexpectedValueException;

/**
 * @property MockObject db
 * @property MockObject request
 * @property TalkCommentsController sut
 * @property MapperFactoryForTests mapperFactory
 * @property EmailServiceFactoryForTests emailServiceFactory
 */
class TalkCommentsControllerTest extends TestCase
{
    public function notLoggedInUserCallableProvider()
    {
        return [
            ["getReported", "You must log in to do that"],
            ["reportComment", "You must log in to report a comment"],
            ["moderateReportedComment", "You must log in to moderate a comment"],
            ["updateComment", "You must log in to edit a comment"],
        ];
    }

    protected function setUp(): void
    {
        $this->db = $this->getMockBuilder(PDO::class)->disableOriginalConstructor()->getMock();
        $this->request = $this->getMockBuilder(Request::class)->disableOriginalConstructor()->getMock();
        $this->mapperFactory = new MapperFactoryForTests();
        $this->mapperFactory->setMapperMocks($this, EventMapper::class, TalkCommentMapper::class);
        $this->emailServiceFactory = new EmailServiceFactoryForTests();
        $this->sut = new TalkCommentsController([], $this->mapperFactory, $this->emailServiceFactory);
    }

    /**
     * @dataProvider notLoggedInUserCallableProvider
     */
    public function testThatNotLoggedInUsersThrowsExceptions($action, $message)
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage($message);
        $this->expectExceptionCode(Http::UNAUTHORIZED);

        call_user_func([$this->sut, $action], $this->request, $this->db);
    }

    public function testGetCommentsIsFalseWhenNoCommentsId()
    {
        self::assertFalse($this->sut->getComments($this->request, $this->db));
    }

    public function testGetCommentsThrowsWhenNoCommentsFound()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Comment not found');
        $this->expectExceptionCode(Http::NOT_FOUND);

        $this->request->url_elements[3] = 5;
        $talkCommentMapper = $this->mapperFactory->getMapperMock($this, TalkCommentMapper::class);
        $talkCommentMapper->expects(self::once())->method("getCommentById")->with(5, false)->willReturn(false);

        $this->sut->getComments($this->request, $this->db);
    }

    public function testGetCommentsWorksAsExpected()
    {
        $this->request->url_elements[3] = 5;
        $talkCommentMapper = $this->mapperFactory->getMapperMock($this, TalkCommentMapper::class);
        $talkCommentMapper->expects(self::once())->method("getCommentById")->with(5, false)->willReturn([]);

        self::assertEquals([], $this->sut->getComments($this->request, $this->db));
    }

    public function testGetReportedThrowsIfNoEventId()
    {
        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage('Event not found');
        $this->expectExceptionCode(Http::NOT_FOUND);

        $this->request->user_id = [3];

        $this->sut->getReported($this->request, $this->db);
    }

    public function testGetReportedThrowsWhenUserHasNoAdmin()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage("You don't have permission to do that");
        $this->expectExceptionCode(Http::FORBIDDEN);

        $this->request->url_elements[3] = 5;
        $this->request->user_id = [3];

        $this->mapperFactory->getMapperMock($this, EventMapper::class)
            ->expects(self::once())->method("thisUserHasAdminOn")->with(5)->willReturn(false);

        $this->sut->getReported($this->request, $this->db);
    }

    public function testGetReportedWorksAsExpected()
    {
        $this->request->url_elements[3] = 5;
        $this->request->user_id = 3;

        $this->mapperFactory->getMapperMock($this, EventMapper::class)
            ->expects(self::once())->method("thisUserHasAdminOn")->with(5)->willReturn(true);
        $talkCommentReportModelCollection = $this->getMockBuilder(TalkCommentReportModelCollection::class)->disableOriginalConstructor()->getMock();
        $this->mapperFactory->getMapperMock($this, TalkCommentMapper::class)
            ->expects(self::once())->method("getReportedCommentsByEventId")->with(5)->willReturn($talkCommentReportModelCollection);
        $talkCommentReportModelCollection->expects(self::once())->method("getOutputView")->with($this->request)->willReturn("hi");

        self::assertEquals("hi", $this->sut->getReported($this->request, $this->db));
    }

    public function testReportCommentThrowsWhenCommentNotFound()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Comment not found');
        $this->expectExceptionCode(Http::NOT_FOUND);

        $this->request->url_elements[3] = 5;
        $this->request->user_id = 3;

        $commentMapper = $this->mapperFactory->getMapperMock($this, TalkCommentMapper::class);
        $commentMapper->expects(self::once())->method("getCommentInfo")->with(5)->willReturn(false);

        $this->sut->reportComment($this->request, $this->db);
    }

    public function testReportCommentWorksAsExpected()
    {
        $this->request->url_elements[3] = 5;
        $this->request->user_id = 3;
        $this->request->base = "hi";
        $this->request->version = 10;

        $commentMapper = $this->mapperFactory->getMapperMock($this, TalkCommentMapper::class);
        $commentMapper->expects(self::once())->method("getCommentInfo")->with(5)->willReturn([
            'talk_id' => 10,
            'event_id' => 6
        ]);
        $commentMapper->expects(self::once())->method("userReportedComment")->with(5, 3);
        $commentMapper->expects(self::once())->method("getCommentById")->with(5, true, true)->willReturn("commment",
            true, true);
        $eventmapper = $this->mapperFactory->getMapperMock($this, EventMapper::class);
        $eventmapper->expects(self::once())->method("getHostsEmailAddresses")->with(6)->willReturn(["oneMailToRuleThemAll"]);
        $eventmapper->expects(self::once())->method("getEventById")->with(6, true, true)->willReturn("event");
        $this->emailServiceFactory->getEmailServiceMock($this, CommentReportedEmailService::class)
            ->expects(self::once())->method("sendEmail");
        $view = $this->getMockBuilder(ApiView::class)->disableOriginalConstructor()->getMock();
        $view->expects(self::once())->method("setHeader")->with("Location", "hi/10/talks/10/comments");
        $view->expects(self::once())->method("setResponseCode")->with(Http::ACCEPTED);
        $this->request->expects(self::once())->method("getView")->willReturn($view);

        $this->sut->reportComment($this->request, $this->db);
    }

    public function testModerateReportedCommentThrowsWhenCommentNotFound()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Comment not found');
        $this->expectExceptionCode(Http::NOT_FOUND);

        $this->request->url_elements[3] = 5;
        $this->request->user_id = 3;

        $talkCommentMapper = $this->mapperFactory->getMapperMock($this, TalkCommentMapper::class);
        $talkCommentMapper->expects(self::once())->method("getCommentInfo")->with(5)->willReturn(false);

        $this->sut->moderateReportedComment($this->request, $this->db);
    }

    public function testModerateReportedCommentThrowsWhenUserDoesntHavePermission()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage("You don't have permission to do that");
        $this->expectExceptionCode(Http::FORBIDDEN);

        $this->request->url_elements[3] = 5;
        $this->request->user_id = 3;

        $talkCommentMapper = $this->mapperFactory->getMapperMock($this, TalkCommentMapper::class);
        $talkCommentMapper->expects(self::once())->method("getCommentInfo")->with(5)->willReturn(['event_id' => 6]);
        $eventMapper = $this->mapperFactory->getMapperMock($this, EventMapper::class);
        $eventMapper->expects(self::once())->method("thisUserHasAdminOn")->with(6)->willReturn(false);

        $this->sut->moderateReportedComment($this->request, $this->db);
    }

    public function testModerateReportedCommentThrowsWhenUnexpectedDecision()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage("Unexpected decision");
        $this->expectExceptionCode(Http::BAD_REQUEST);

        $this->request->url_elements[3] = 5;
        $this->request->user_id = 3;

        $talkCommentMapper = $this->mapperFactory->getMapperMock($this, TalkCommentMapper::class);
        $talkCommentMapper->expects(self::once())->method("getCommentInfo")->with(5)->willReturn(['event_id' => 6]);
        $eventMapper = $this->mapperFactory->getMapperMock($this, EventMapper::class);
        $eventMapper->expects(self::once())->method("thisUserHasAdminOn")->with(6)->willReturn(true);
        $this->request->expects(self::once())->method("getParameter")->with("decision")->willReturn("unexpected");

        $this->sut->moderateReportedComment($this->request, $this->db);
    }

    public function testModerateReportedCommentWorksAsExpected()
    {
        $this->request->url_elements[3] = 5;
        $this->request->user_id = 3;
        $this->request->base = "hi";
        $this->request->version = 10;

        $talkCommentMapper = $this->mapperFactory->getMapperMock($this, TalkCommentMapper::class);
        $talkCommentMapper->expects(self::once())->method("getCommentInfo")->with(5)->willReturn([
            'event_id' => 6,
            "talk_id" => 9
        ]);
        $eventMapper = $this->mapperFactory->getMapperMock($this, EventMapper::class);
        $eventMapper->expects(self::once())->method("thisUserHasAdminOn")->with(6)->willReturn(true);
        $this->request->expects(self::once())->method("getParameter")->with("decision")->willReturn("approved");
        $talkCommentMapper->expects(self::once())->method("moderateReportedComment")->with("approved", 5, 3);
        $view = $this->getMockBuilder(ApiView::class)->disableOriginalConstructor()->getMock();
        $view->expects(self::once())->method("setHeader")->with("Location", "hi/10/talks/9/comments");
        $view->expects(self::once())->method("setResponseCode")->with(Http::NO_CONTENT);
        $this->request->expects(self::once())->method("getView")->willReturn($view);

        $this->sut->moderateReportedComment($this->request, $this->db);
    }

    public function testUpdateCommentThrowsWhenCommentNotSet()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('The field "comment" is required');
        $this->expectExceptionCode(Http::BAD_REQUEST);

        $this->request->user_id = 3;
        $this->request->expects(self::once())->method("getParameter")->with("comment")->willReturn([]);

        $this->sut->updateComment($this->request, $this->db);
    }

    public function testUpdateCommentThrowsWhenCommentNotFound()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Comment not found');
        $this->expectExceptionCode(Http::NOT_FOUND);

        $this->request->user_id = 3;
        $this->request->url_elements[3] = 5;
        $this->request->expects(self::once())->method("getParameter")->with("comment")->willReturn(["hi"]);
        $talkCommentMapper = $this->mapperFactory->getMapperMock($this, TalkCommentMapper::class);
        $talkCommentMapper->expects(self::once())->method("getRawComment")->with(5)->willReturn(false);

        $this->sut->updateComment($this->request, $this->db);
    }

    public function testUpdateCommentThrowsWhenNotAuthor()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('You are not the comment author');
        $this->expectExceptionCode(Http::FORBIDDEN);

        $this->request->user_id = 3;
        $this->request->url_elements[3] = 5;
        $this->request->expects(self::once())->method("getParameter")->with("comment")->willReturn(["hi"]);
        $talkCommentMapper = $this->mapperFactory->getMapperMock($this, TalkCommentMapper::class);
        $talkCommentMapper->expects(self::once())->method("getRawComment")->with(5)->willReturn(['user_id' => 6]);

        $this->sut->updateComment($this->request, $this->db);
    }

    public function testUpdateCommentThrowsWhenTimeLimitPassed()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Cannot edit the comment after 1 minutes');
        $this->expectExceptionCode(Http::BAD_REQUEST);

        $this->request->user_id = 3;
        $this->request->url_elements[3] = 5;
        $this->request->expects(self::once())->method("getParameter")->with("comment")->willReturn(["hi"]);
        $talkCommentMapper = $this->mapperFactory->getMapperMock($this, TalkCommentMapper::class);
        $createdAt = new DateTime();
        $createdAt->sub(new DateInterval("PT2M"));
        $talkCommentMapper->expects(self::once())->method("getRawComment")->with(5)->willReturn([
            'user_id' => 3,
            'created_at' => $createdAt
        ]);

        $this->sut = new TalkCommentsController(['limits' => ['max_comment_edit_minutes' => 1]], $this->mapperFactory,
            $this->emailServiceFactory);
        $this->sut->updateComment($this->request, $this->db);
    }

    public function testUpdateCommentThrowsWhenCommentCantBeUpdated()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Comment update failed');
        $this->expectExceptionCode(Http::INTERNAL_SERVER_ERROR);

        $this->request->user_id = 3;
        $this->request->url_elements[3] = 5;
        $this->request->expects(self::once())->method("getParameter")->with("comment")->willReturn("hi");
        $talkCommentMapper = $this->mapperFactory->getMapperMock($this, TalkCommentMapper::class);
        $createdAt = new DateTime();
        $talkCommentMapper->expects(self::once())->method("getRawComment")->with(5)->willReturn([
            'user_id' => 3,
            'created_at' => $createdAt
        ]);
        $talkCommentMapper->expects(self::once())->method("updateCommentBody")->with(5, "hi")->willReturn(false);

        $this->sut->updateComment($this->request, $this->db);
    }

    public function testUpdateCommentWorksAsExpected()
    {
        $this->request->user_id = 3;
        $this->request->url_elements[3] = 5;
        $this->request->expects(self::once())->method("getParameter")->with("comment")->willReturn("hi");
        $talkCommentMapper = $this->mapperFactory->getMapperMock($this, TalkCommentMapper::class);
        $createdAt = new DateTime();
        $talkCommentMapper->expects(self::once())->method("getRawComment")->with(5)->willReturn([
            'user_id' => 3,
            'created_at' => $createdAt
        ]);
        $talkCommentMapper->expects(self::once())->method("updateCommentBody")->with(5, "hi")->willReturn(true);
        $talkCommentMapper->expects(self::once())->method("getCommentById")->with(5)->willReturn(6);

        self::assertEquals(6, $this->sut->updateComment($this->request, $this->db));
    }
}
