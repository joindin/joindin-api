<?php

namespace Joindin\Api\Test\Controller;

use Exception;
use Joindin\Api\Controller\UsersController;
use Joindin\Api\Exception\AuthenticationException;
use Joindin\Api\Exception\AuthorizationException;
use Joindin\Api\Model\OAuthModel;
use Joindin\Api\Model\TalkCommentMapper;
use Joindin\Api\Model\UserMapper;
use Joindin\Api\Request;
use Joindin\Api\Service\UserRegistrationEmailService;
use Joindin\Api\View\ApiView;
use Joindin\Api\View\JsonView;
use Joindin\Api\Test\Mock\mockPDO;
use PDO;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Teapot\StatusCode\Http;

final class UsersControllerTest extends TestCase
{
    /**
     * Ensures that if the deleteUser method is called and no user_id is set,
     * an exception is thrown
     *
     * @return void
     *
     * @group uses_pdo
     */
    public function testDeleteUserWithoutBeingLoggedInThrowsException()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('You must be logged in to delete data');
        $this->expectExceptionCode(Http::UNAUTHORIZED);

        $request = new Request([], ['REQUEST_URI' => "http://api.dev.joind.in/v2.1/users/3", 'REQUEST_METHOD' => 'DELETE']);

        $usersController = new UsersController();

        /** @var PDO&MockObject $db */
        $db = $this->getMockBuilder(mockPDO::class)->getMock();

        $usersController->deleteUser($request, $db);
    }

    /**
     * Ensures that if the deleteUser method is called and user_id is a,
     * non-admin, an exception is thrown
     *
     * @return void
     */
    public function testDeleteUserWithNonAdminIdThrowsException()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('You do not have permission to do that');
        $this->expectExceptionCode(Http::FORBIDDEN);

        $request = new Request([], ['REQUEST_URI' => "http://api.dev.joind.in/v2.1/users/3", 'REQUEST_METHOD' => 'DELETE']);
        $request->user_id = 2;
        $usersController = new UsersController();

        $db = $this->getMockBuilder(PDO::class)->disableOriginalConstructor()->getMock();

        $userMapper = $this->getMockBuilder(UserMapper::class)
            ->setConstructorArgs([$db, $request])
            ->getMock();

        $userMapper
            ->expects($this->once())
            ->method('isSiteAdmin')
            ->with(2)
            ->willReturn(false);

        $usersController->setUserMapper($userMapper);
        $usersController->deleteUser($request, $db);
    }

    /**
     * Ensures that if the deleteUser method is called and user_id is an
     * admin, but the delete fails, then an exception is thrown
     *
     * @return void
     */
    public function testDeleteUserWithAdminAccessThrowsExceptionOnFailedDelete()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('There was a problem trying to delete the user');
        $this->expectExceptionCode(Http::BAD_REQUEST);

        $request = new Request([], ['REQUEST_URI' => "http://api.dev.joind.in/v2.1/users/3", 'REQUEST_METHOD' => 'DELETE']);
        $request->user_id = 1;
        $usersController = new \Joindin\Api\Controller\UsersController();
        // Please see below for explanation of why we're mocking a "mock" PDO
        // class
        $db = $this->getMockBuilder(mockPDO::class)->getMock();

        $userMapper = $this->getMockBuilder(UserMapper::class)
            ->setConstructorArgs([$db, $request])
            ->getMock();

        $userMapper
            ->expects($this->once())
            ->method('isSiteAdmin')
            ->with(1)
            ->willReturn(true);

        $userMapper
            ->expects($this->once())
            ->method('delete')
            ->with(3)
            ->willReturn(false);

        $usersController->setUserMapper($userMapper);
        $usersController->deleteUser($request, $db);
    }

    /**
     * Ensures that if the deleteUser method is called and user_id is an
     * admin, but the delete fails, then an exception is thrown
     *
     * @return void
     */
    public function testDeleteUserWithAdminAccessDeletesSuccessfully()
    {
        $request = new Request([], ['REQUEST_URI' => "http://api.dev.joind.in/v2.1/users/3", 'REQUEST_METHOD' => 'DELETE']);
        $request->user_id = 1;
        $usersController = new UsersController();
        // Please see below for explanation of why we're mocking a "mock" PDO
        // class
        $db = $this->getMockBuilder(mockPDO::class)->getMock();

        $userMapper = $this->getMockBuilder(UserMapper::class)
            ->setConstructorArgs([$db, $request])
            ->getMock();

        $userMapper
            ->expects($this->once())
            ->method('isSiteAdmin')
            ->with(1)
            ->willReturn(true);

        $userMapper
            ->expects($this->once())
            ->method('delete')
            ->with(3)
            ->willReturn(true);

        $usersController->setUserMapper($userMapper);
        $this->assertNull($usersController->deleteUser($request, $db));
    }

    public function testDeleteTalkCommentsWithoutBeingLoggedInThrowsException(): void
    {
        $request = new Request(
            [],
            ['REQUEST_URI' => "http://api.dev.joind.in/v2.1/users/3/talk-comments", 'REQUEST_METHOD' => 'DELETE']
        );

        $usersController = new UsersController();
        $db = $this->getMockBuilder(PDO::class)->disableOriginalConstructor()->getMock();

        $this->expectException(AuthenticationException::class);
        $this->expectExceptionMessage('You must be logged in to perform this operation.');
        $this->expectExceptionCode(Http::UNAUTHORIZED);

        $usersController->deleteTalkComments($request, $db);
    }

    public function testDeleteTalkCommentsWithNonAdminThrowsException(): void
    {
        $this->expectException(AuthorizationException::class);
        $this->expectExceptionMessage('This operation requires admin privileges.');
        $this->expectExceptionCode(Http::FORBIDDEN);

        $request = new Request(
            [],
            ['REQUEST_URI' => "http://api.dev.joind.in/v2.1/users/3/talk-comments", 'REQUEST_METHOD' => 'DELETE']
        );
        $request->user_id = 2;

        $usersController = new UsersController();

        $db = $this->getMockBuilder(PDO::class)->disableOriginalConstructor()->getMock();

        $userMapper = $this->getMockBuilder(UserMapper::class)
            ->setConstructorArgs([$db, $request])
            ->getMock();

        $userMapper
            ->expects($this->once())
            ->method('isSiteAdmin')
            ->with(2)
            ->willReturn(false);

        $usersController->setUserMapper($userMapper);
        $usersController->deleteTalkComments($request, $db);
    }

    public function testDeleteTalkCommentsDeletesUsersTalkComments(): void
    {
        $usersController = new UsersController();

        $request = new Request(
            [],
            ['REQUEST_URI' => "http://api.dev.joind.in/v2.1/users/3/talk-comments", 'REQUEST_METHOD' => 'DELETE']
        );
        $request->user_id = 1;

        $db = $this->getMockBuilder(PDO::class)->disableOriginalConstructor()->getMock();

        $userMapper = $this->getMockBuilder(UserMapper::class)
            ->setConstructorArgs([$db, $request])
            ->getMock();

        $userMapper
            ->expects($this->once())
            ->method('isSiteAdmin')
            ->with(1)
            ->willReturn(true);

        $talkCommentMapper = $this->getMockBuilder(TalkCommentMapper::class)
            ->setConstructorArgs([$db, $request])
            ->getMock();

        $talkCommentMapper
            ->expects($this->once())
            ->method('deleteCommentsForUser')
            ->with(3);

        $usersController->setUserMapper($userMapper);
        $usersController->setTalkCommentMapper($talkCommentMapper);
        $usersController->deleteTalkComments($request, $db);
    }

    public function testThatUserDataIsNotDoubleEscapedOnUserCreation(): void
    {
        $request = $this->getMockBuilder(Request::class)->disableOriginalConstructor()->getMock();
        $request->user_id = 1;
        $request->base = 'base';
        $request->path_info = 'path_info';
        $request->method('getParameter')
            ->withConsecutive(
                ['username'],
                ['full_name'],
                ['email'],
                ['password'],
                ['twitter_username'],
                ['biography']
            )
            ->willReturnOnConsecutiveCalls(
                'user"\'stuff',
                'full"\'stuff',
                'mailstuff@example.com',
                'pass"\'stuff',
                'twitter"\'stuff',
                'Bio"\'stuff'
            );

        $view = $this->getMockBuilder(ApiView::class)->disableOriginalConstructor()->getMock();
        $view->expects($this->once())->method('setHeader')->with('Location', 'basepath_info/1');
        $view->expects($this->once())->method('setResponseCode')->with(Http::CREATED);
        $request->method('getView')->willReturn($view);

        $db = $this->getMockBuilder(PDO::class)->disableOriginalConstructor()->getMock();

        $userMapper = $this->getMockBuilder(UserMapper::class)->disableOriginalConstructor()->getMock();
        $userMapper->expects($this->once())->method('getUserByUsername')->with('user"\'stuff')->willReturn(false);
        $userMapper->expects($this->once())->method('checkPasswordValidity')->with('pass"\'stuff')->willReturn(true);
        $userMapper->expects($this->once())->method('generateEmailVerificationTokenForUserId')->willReturn('token');
        $userMapper->expects($this->once())->method('createUser')->with([
            'username' => 'user"\'stuff',
            'full_name' => 'full"\'stuff',
            'email' => 'mailstuff@example.com',
            'password' => 'pass"\'stuff',
            'twitter_username' => 'twitter"\'stuff',
            'biography' => 'Bio"\'stuff'
        ])->willReturn(true);

        $emailService = $this->getMockBuilder(UserRegistrationEmailService::class)->disableOriginalConstructor()->getMock();
        $emailService->method('sendEmail');

        $controller = new UsersController();
        $controller->setUserMapper($userMapper);
        $controller->setUserRegistrationEmailService($emailService);

        $controller->postAction($request, $db);
    }

    public function testThatUserDataIsNotDoubleEscapedOnUserEdit(): void
    {
        $request = $this->getMockBuilder(Request::class)->disableOriginalConstructor()->getMock();
        $request->method('getUserId')->willReturn(1);
        $request->method('getParameter')->withConsecutive(
            ['password'],
            ['full_name'],
            ['email'],
            ['username'],
            ['twitter_username'],
            ['biography']
        )->willReturnOnConsecutiveCalls(
            '',
            'full"\'stuff',
            'mailstuff@example.com',
            'user"\'stuff',
            'twitter"\'stuff',
            'Bio"\'stuff'
        );

        $oauthmodel = $this->getMockBuilder(OAuthModel::class)->disableOriginalConstructor()->getMock();
        $oauthmodel->expects($this->once())->method('isAccessTokenPermittedPasswordGrant')->willReturn(true);
        $request->expects($this->once())->method('getOauthModel')->willReturn($oauthmodel);

        $view = $this->getMockBuilder(ApiView::class)->disableOriginalConstructor()->getMock();
        $view->expects($this->once())->method('setHeader')->with('Content-Length', 0);
        $view->expects($this->once())->method('setResponseCode')->with(Http::NO_CONTENT);
        $request->method('getView')->willReturn($view);

        $db = $this->getMockBuilder('\PDO')->disableOriginalConstructor()->getMock();

        $userMapper = $this->getMockBuilder(UserMapper::class)->disableOriginalConstructor()->getMock();
        $userMapper->expects($this->once())->method('getUserByUsername')->with('user"\'stuff')->willReturn(false);
        $userMapper->expects($this->once())->method('thisUserHasAdminOn')->willReturn(true);
        $userMapper->expects($this->once())->method('editUser')->with([
            'username' => 'user"\'stuff',
            'full_name' => 'full"\'stuff',
            'email' => 'mailstuff@example.com',
            'twitter_username' => 'twitter"\'stuff',
            'biography' => 'Bio"\'stuff',
            'user_id' => false,
        ])->willReturn(true);

        $controller = new UsersController();
        $controller->setUserMapper($userMapper);

        $controller->updateUser($request, $db);
    }

    /**
     * Ensures that if the setTrusted method is called and no user_id is set,
     * an exception is thrown
     *
     * @return void
     *
     * @group uses_pdo
     */
    public function testSetTrustedWithNoUserIdThrowsException()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('You must be logged in to change a user account');
        $this->expectExceptionCode(Http::UNAUTHORIZED);

        $request = new Request([], ['REQUEST_URI' => "http://api.dev.joind.in/v2.1/users/4/trusted", 'REQUEST_METHOD' => 'POST']);
        $db = $this->getMockBuilder(PDO::class)->disableOriginalConstructor()->getMock();

        $usersController = new UsersController();
        $usersController->setTrusted($request, $db);
    }

    /**
     * Ensures that if the setTrsuted method is called and user_id is a,
     * non-admin, an exception is thrown
     *
     * @return void
     */
    public function testSetTrustedWithNonAdminIdThrowsException()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage("You must be an admin to change a user's trusted state");
        $this->expectExceptionCode(Http::FORBIDDEN);

        $request = new Request([], ['REQUEST_URI' => "http://api.dev.joind.in/v2.1/users/4/trusted", 'REQUEST_METHOD' => 'POST']);
        $request->user_id = 2;
        $usersController = new UsersController();
        $db = $this->getMockBuilder(PDO::class)->disableOriginalConstructor()->getMock();

        $userMapper = $this->getMockBuilder(UserMapper::class)
            ->setConstructorArgs([$db, $request])
            ->getMock();

        $userMapper
            ->expects($this->once())
            ->method('isSiteAdmin')
            ->willReturn(false);

        $usersController->setUserMapper($userMapper);
        $usersController->setTrusted($request, $db);
    }

    /**
     * Ensures that if the setTrusted method is called by an admin,
     * but without a trusted state, an exception is thrown
     *
     * @return void
     */
    public function testSetTrustedWithoutStateThrowsException()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('You must provide a trusted state');
        $this->expectExceptionCode(Http::BAD_REQUEST);

        $request = $this->getMockBuilder(Request::class)->disableOriginalConstructor()->getMock();
        $request->method('getUserId')->willReturn(2);
        $request->method('getParameter')
            ->with("trusted")
            ->willReturn(null);

        $usersController = new UsersController();
        $db = $this->getMockBuilder(PDO::class)->disableOriginalConstructor()->getMock();

        $userMapper = $this->getMockBuilder(UserMapper::class)
            ->setConstructorArgs([$db, $request])
            ->getMock();

        $userMapper
            ->expects($this->once())
            ->method('isSiteAdmin')
            ->willReturn(true);

        $usersController->setUserMapper($userMapper);
        $usersController->setTrusted($request, $db);
    }

    /**
     * Ensures that if the setTrusted method is called by an admin,
     * but the update fails, an exception is thrown
     *
     * @return void
     */
    public function testSetTrustedWithFailureThrowsException()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Unable to update status');
        $this->expectExceptionCode(Http::INTERNAL_SERVER_ERROR);

        $request = $this->getMockBuilder(Request::class)->disableOriginalConstructor()->getMock();
        $request->method('getUserId')->willReturn(2);
        $request->method('getParameter')
            ->with("trusted")
            ->willReturn(true);

        $usersController = new UsersController();
        $db = $this->getMockBuilder(PDO::class)->disableOriginalConstructor()->getMock();

        $userMapper = $this->getMockBuilder(UserMapper::class)
            ->setConstructorArgs([$db, $request])
            ->getMock();

        $userMapper
            ->expects($this->once())
            ->method('isSiteAdmin')
            ->willReturn(true);

        $userMapper
            ->expects($this->once())
            ->method("setTrustedStatus")
            ->willReturn(false);

        $usersController->setUserMapper($userMapper);
        $usersController->setTrusted($request, $db);
    }

    /**
     * Ensures that if the setTrusted method is called by an admin,
     * and the update succeeds, a view is created and null is returned
     *
     * @return void
     */
    public function testSetTrustedWithSuccessCreatesView()
    {
        $request = $this->getMockBuilder(Request::class)->disableOriginalConstructor()->getMock();
        $request->method('getUserId')->willReturn(2);
        $request->method('getParameter')
            ->with("trusted")
            ->willReturn(true);

        $view = $this->getMockBuilder(JsonView::class)->getMock();
        $view->expects($this->once())
            ->method("setHeader");

        $view->expects($this->once())
            ->method("setResponseCode")
            ->with(Http::NO_CONTENT);

        $request->expects($this->once())
            ->method("getView")
            ->willReturn($view);

        $usersController = new UsersController();
        $db = $this->getMockBuilder(PDO::class)->disableOriginalConstructor()->getMock();

        $userMapper = $this->getMockBuilder(UserMapper::class)
            ->setConstructorArgs([$db, $request])
            ->getMock();

        $userMapper
            ->expects($this->once())
            ->method('isSiteAdmin')
            ->willReturn(true);

        $userMapper
            ->expects($this->once())
            ->method("setTrustedStatus")
            ->willReturn(true);

        $usersController->setUserMapper($userMapper);
        $this->assertNull($usersController->setTrusted($request, $db));
    }
}
