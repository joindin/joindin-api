<?php


namespace Joindin\Api\Test\Controller;


use Exception;
use Joindin\Api\Controller\EmailsController;
use Joindin\Api\Factory\EmailServiceFactory;
use Joindin\Api\Model\UserMapper;
use Joindin\Api\Request;
use Joindin\Api\Service\UserPasswordResetEmailService;
use Joindin\Api\Service\UserRegistrationEmailService;
use Joindin\Api\Service\UserUsernameReminderEmailService;
use Joindin\Api\View\ApiView;
use PDO;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Teapot\StatusCode\Http;

class EmailsControllerTest extends TestCase
{
    /**
     * @var MockObject $userMapper
     * */
    private $userMapper;
    /**
     * @var EmailsController $sut
     */
    private $sut;
    /**
     * @var MockObject $request
     */
    private $request;
    /**
     * @var MockObject $db
     */
    private $db;

    private $config = [];

    /**
     * @var MockObject $emailServiceFactory
     */
    private $emailServiceFactory;
    /**
     * @var MockObject
     */
    private $userRegistrationEmailService;
    /**
     * @var MockObject
     */
    private $userPasswordResetEmailService;
    /**
     * @var MockObject
     */
    private $userUsernameReminderEmailService;

    /**
     * @var MockObject
     */
    private $view;

    protected function setUp() :void
    {
        $this->request = $this->getMockBuilder(Request::class)->disableOriginalConstructor()->getMock();
        $this->db = $this->getMockBuilder(PDO::class)->disableOriginalConstructor()->getMock();
        $this->userMapper = $this->getMockBuilder(UserMapper::class)->disableOriginalConstructor()->getMock();
        $this->emailServiceFactory = $this->getMockBuilder(EmailServiceFactory::class)->disableOriginalConstructor()->getMock();
        $this->emailServiceFactory->expects(self::atLeast(1))->method("getUserMapper")->with($this->request,
            $this->db)->willReturn($this->userMapper);

        $this->sut = new EmailsController($this->config, $this->emailServiceFactory);
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
        $this->userMapper->expects(self::once())->method("getUserIdFromEmail")->with("test@example.com")->willReturn(false);

        $this->sut->verifications($this->request, $this->db);
    }

    public function testUsernameReminderIncorrectEmailThrowsException()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage("Can't find that email address");
        $this->expectExceptionCode(Http::BAD_REQUEST);

        $this->request->expects(self::once())->method("getParameter")->with("email")->willReturn("test@example.com");
        $this->userMapper->expects(self::once())->method("getUserByEmail")->with("test@example.com")->willReturn(["users" => []]);

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
        $this->userMapper->expects(self::once())->method("getUserByUsername")->with("test")->willReturn(["users" => []]);
        $this->sut->passwordReset($this->request, $this->db);
    }

    public function testPasswordResetResetTokenFailsThrowsException()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage("Unable to generate a reset token");
        $this->expectExceptionCode(Http::BAD_REQUEST);

        $this->request->expects(self::once())->method("getParameter")->with("username")->willReturn("test");
        $this->userMapper->expects(self::once())->method("getUserByUsername")->with("test")->willReturn(["users" => ["tester"]]);
        $this->userMapper->expects(self::once())->method("getUserIdFromUsername")->with("test")->willReturn(5);
        $this->userMapper->expects(self::once())->method("getEmailByUserId")->with(5)->willReturn("ignored");
        $this->userMapper->expects(self::once())->method("generatePasswordResetTokenForUserId")->with(5)->willReturn(false);
        $this->sut->passwordReset($this->request, $this->db);
    }


    public function testVerificationsWorksAsExpected() {
        $this->expandedCreate();
        $this->request->expects(self::once())->method("getParameter")->with("email")->willReturn("test@example.com");
        $this->userMapper->expects(self::once())->method("getUserIdFromEmail")->with("test@example.com")->willReturn(5);
        $this->userMapper->expects(self::once())->method("generateEmailVerificationTokenForUserId")->with(5)->willReturn("someToken");
        $this->userRegistrationEmailService->expects(self::once())->method("sendEmail");
        $this->sut->verifications($this->request, $this->db);
    }

    public function testUsernameReminderWorksAsExpected() {
        $this->expandedCreate();
        $this->request->expects(self::once())->method("getParameter")->with("email")->willReturn("test@example.com");
        $this->userMapper->expects(self::once())->method("getUserByEmail")->with("test@example.com")->willReturn(["users" => ["tester"]]);
        $this->userUsernameReminderEmailService->expects(self::once())->method("sendEmail");
        $this->sut->usernameReminder($this->request, $this->db);
    }

    public function testPasswordResetWorksAsExpected()
    {
        $this->expandedCreate();
        $this->request->expects(self::once())->method("getParameter")->with("username")->willReturn("test");
        $this->userMapper->expects(self::once())->method("getUserByUsername")->with("test")->willReturn(["users" => ["tester"]]);
        $this->userMapper->expects(self::once())->method("getUserIdFromUsername")->with("test")->willReturn(5);
        $this->userMapper->expects(self::once())->method("getEmailByUserId")->with(5)->willReturn("ignored");
        $this->userMapper->expects(self::once())->method("generatePasswordResetTokenForUserId")->with(5)->willReturn("someToken");
        $this->userPasswordResetEmailService->expects(self::once())->method("sendEmail");
        $this->view->expects(self::once())->method("setHeader");
        $this->view->expects(self::once())->method("setResponseCode")->with(Http::ACCEPTED);
        $this->sut->passwordReset($this->request, $this->db);
    }

    public function expandedCreate(): void
    {
        $this->userUsernameReminderEmailService = $this->getMockBuilder(UserUsernameReminderEmailService::class)->disableOriginalConstructor()->getMock();
        $this->userPasswordResetEmailService = $this->getMockBuilder(UserPasswordResetEmailService::class)->disableOriginalConstructor()->getMock();
        $this->userRegistrationEmailService = $this->getMockBuilder(UserRegistrationEmailService::class)->disableOriginalConstructor()->getMock();
        $this->emailServiceFactory->expects(self::any())->method("getUserUsernameReminderEmailService")->willReturn($this->userUsernameReminderEmailService);
        $this->emailServiceFactory->expects(self::any())->method("getUserPasswordResetEmailService")->willReturn($this->userPasswordResetEmailService);
        $this->emailServiceFactory->expects(self::any())->method("getUserRegistrationEmailService")->willReturn($this->userRegistrationEmailService);
        $this->view = $this->getMockBuilder(ApiView::class)->disableOriginalConstructor()->getMock();
        $this->view->expects(self::once())->method("setHeader");
        $this->view->expects(self::once())->method("setResponseCode")->with(Http::ACCEPTED);
        $this->request->expects(self::once())->method("getView")->willReturn($this->view);
    }
}
