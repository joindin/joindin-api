<?php


namespace Joindin\Api\Test\Controller;


use Exception;
use Joindin\Api\Controller\EmailsController;
use Joindin\Api\Model\UserMapper;
use Joindin\Api\Request;
use Joindin\Api\Service\UserPasswordResetEmailService;
use Joindin\Api\Service\UserRegistrationEmailService;
use Joindin\Api\Service\UserUsernameReminderEmailService;
use Joindin\Api\Test\EmailServiceFactoryForTests;
use Joindin\Api\Test\MapperFactoryForTests;
use Joindin\Api\View\ApiView;
use PDO;
use PHPUnit\Framework\TestCase;
use Teapot\StatusCode\Http;

/**
 * @property MockObject request
 * @property MockObject db
 * @property MapperFactoryForTests mapperFactory
 * @property EmailServiceFactoryForTests emailServiceFactory
 * @property EmailsController sut
 */
class EmailsControllerTest extends TestCase
{
    private $config = [];

    protected function setUp(): void
    {
        $this->request = $this->getMockBuilder(Request::class)->disableOriginalConstructor()->getMock();
        $this->db = $this->getMockBuilder(PDO::class)->disableOriginalConstructor()->getMock();
        $this->emailServiceFactory = new EmailServiceFactoryForTests();
        $this->mapperFactory = new MapperFactoryForTests();

        $this->sut = new EmailsController($this->config, $this->emailServiceFactory, $this->mapperFactory);
    }

    /**
     * @dataProvider callableProvider
     */
    public function testWithoutEmailThrowsException($methodUnderTest)
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('The email address must be supplied');
        $this->expectExceptionCode(Http::BAD_REQUEST);

        $this->request->expects(self::once())->method("getParameter")->with("email")->willReturn("");

        call_user_func([$this->sut, $methodUnderTest], $this->request, $this->db);
    }

    public function callableProvider()
    {
        return [
            ["usernameReminder"],
            ["verifications"],
        ];
    }

    public function testVerificationsIncorrectEmailThrowsException()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage("Can't find that email address");
        $this->expectExceptionCode(Http::BAD_REQUEST);

        $this->request->expects(self::once())->method("getParameter")->with("email")->willReturn("test@example.com");
        $userMapper = $this->mapperFactory->getMapperMock($this, UserMapper::class);
        $userMapper->expects(self::once())->method("getUserIdFromEmail")->with("test@example.com")->willReturn(false);

        $this->sut->verifications($this->request, $this->db);
    }

    public function testUsernameReminderIncorrectEmailThrowsException()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage("Can't find that email address");
        $this->expectExceptionCode(Http::BAD_REQUEST);

        $this->request->expects(self::once())->method("getParameter")->with("email")->willReturn("test@example.com");
        $userMapper = $this->mapperFactory->getMapperMock($this, UserMapper::class);
        $userMapper->expects(self::once())->method("getUserByEmail")->with("test@example.com")->willReturn(["users" => []]);

        $this->sut->usernameReminder($this->request, $this->db);
    }

    public function testPasswordResetWithoutEmailThrowsException()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('A username must be supplied');
        $this->expectExceptionCode(Http::BAD_REQUEST);

        $this->request->expects(self::once())->method("getParameter")->with("username")->willReturn("");
        $this->sut->passwordReset($this->request, $this->db);
    }

    public function testPasswordResetWithoutUsernameThrowsException()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage("Can't find that user");
        $this->expectExceptionCode(Http::BAD_REQUEST);

        $this->request->expects(self::once())->method("getParameter")->with("username")->willReturn("test");
        $userMapper = $this->mapperFactory->getMapperMock($this, UserMapper::class);
        $userMapper->expects(self::once())->method("getUserByUsername")->with("test")->willReturn(["users" => []]);
        $this->sut->passwordReset($this->request, $this->db);
    }

    public function testPasswordResetResetTokenFailsThrowsException()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage("Unable to generate a reset token");
        $this->expectExceptionCode(Http::BAD_REQUEST);

        $this->request->expects(self::once())->method("getParameter")->with("username")->willReturn("test");
        $userMapper = $this->mapperFactory->getMapperMock($this, UserMapper::class);
        $userMapper->expects(self::once())->method("getUserByUsername")->with("test")->willReturn(["users" => ["tester"]]);
        $userMapper->expects(self::once())->method("getUserIdFromUsername")->with("test")->willReturn(5);
        $userMapper->expects(self::once())->method("getEmailByUserId")->with(5)->willReturn("ignored");
        $userMapper->expects(self::once())->method("generatePasswordResetTokenForUserId")->with(5)->willReturn(false);
        $this->sut->passwordReset($this->request, $this->db);
    }


    public function testVerificationsWorksAsExpected()
    {
        $this->expandedCreate();
        $this->request->expects(self::once())->method("getParameter")->with("email")->willReturn("test@example.com");
        $userMapper = $this->mapperFactory->getMapperMock($this, UserMapper::class);
        $userMapper->expects(self::once())->method("getUserIdFromEmail")->with("test@example.com")->willReturn(5);
        $userMapper->expects(self::once())->method("generateEmailVerificationTokenForUserId")
            ->with(5)->willReturn("someToken");
        $this->emailServiceFactory->getEmailServiceMock($this, UserRegistrationEmailService::class)
            ->expects(self::once())->method("sendEmail");
        $this->sut->verifications($this->request, $this->db);
    }

    public function testUsernameReminderWorksAsExpected()
    {
        $this->expandedCreate();
        $this->request->expects(self::once())->method("getParameter")->with("email")->willReturn("test@example.com");
        $userMapper = $this->mapperFactory->getMapperMock($this, UserMapper::class);
        $userMapper->expects(self::once())->method("getUserByEmail")->with("test@example.com")->willReturn(["users" => ["tester"]]);
        $this->emailServiceFactory->getEmailServiceMock($this, UserUsernameReminderEmailService::class)
            ->expects(self::once())->method("sendEmail");
        $this->sut->usernameReminder($this->request, $this->db);
    }

    public function testPasswordResetWorksAsExpected()
    {
        $this->expandedCreate();
        $this->request->expects(self::once())->method("getParameter")->with("username")->willReturn("test");
        $userMapper = $this->mapperFactory->getMapperMock($this, UserMapper::class);
        $userMapper->expects(self::once())->method("getUserByUsername")->with("test")->willReturn(["users" => ["tester"]]);
        $userMapper->expects(self::once())->method("getUserIdFromUsername")->with("test")->willReturn(5);
        $userMapper->expects(self::once())->method("getEmailByUserId")->with(5)->willReturn("ignored");
        $userMapper->expects(self::once())->method("generatePasswordResetTokenForUserId")->with(5)->willReturn("someToken");
        $this->emailServiceFactory->getEmailServiceMock($this, UserPasswordResetEmailService::class)
            ->expects(self::once())->method("sendEmail");
        $this->view->expects(self::once())->method("setHeader");
        $this->view->expects(self::once())->method("setResponseCode")->with(Http::ACCEPTED);
        $this->sut->passwordReset($this->request, $this->db);
    }

    public function expandedCreate(): void
    {
        $this->view = $this->getMockBuilder(ApiView::class)->disableOriginalConstructor()->getMock();
        $this->view->expects(self::once())->method("setHeader");
        $this->view->expects(self::once())->method("setResponseCode")->with(Http::ACCEPTED);
        $this->request->expects(self::once())->method("getView")->willReturn($this->view);
    }
}
