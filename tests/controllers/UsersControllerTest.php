<?php

namespace JoindinTest\Controller;


class UsersControllerTest extends \PHPUnit_Framework_TestCase
{

    /**
     * Ensures that if the deleteUser method is called and no user_id is set,
     * an exception is thrown
     *
     * @return void
     *
     * @test
     * @expectedException        \Exception
     * @expectedExceptionMessage You must be logged in to delete data
     */
    public function testDeleteUserWithNoUserIdThrowsException()
    {
        $request = new \Request([], ['REQUEST_URI' => "http://api.dev.joind.in/v2.1/users/3", 'REQUEST_METHOD' => 'DELETE']);

        $usersController = new \UsersController();
        $db = $this->getMockBuilder('\JoindinTest\Inc\mockPDO')->getMock();

        $usersController->deleteUser($request, $db);

    }

    /**
     * Ensures that if the deleteUser method is called and user_id is a,
     * non-admin, an exception is thrown
     *
     * @return void
     *
     * @test
     * @expectedException        \Exception
     * @expectedExceptionMessage You do not have permission to do that
     */
    public function testDeleteUserWithNonAdminIdThrowsException()
    {
        $request = new \Request([], ['REQUEST_URI' => "http://api.dev.joind.in/v2.1/users/3", 'REQUEST_METHOD' => 'DELETE']);
        $request->user_id = 2;
        $usersController = new \UsersController();
        // Please see below for explanation of why we're mocking a "mock" PDO
        // class
        $db = $this->getMockBuilder('\JoindinTest\Inc\mockPDO')->getMock();

        $userMapper = $this->getMockBuilder('\UserMapper')
            ->setConstructorArgs(array($db,$request))
            ->getMock();

        $userMapper
            ->expects($this->once())
            ->method('thisUserHasAdminOn')
            ->will($this->returnValue(false));

        $usersController->setUserMapper($userMapper);
        $usersController->deleteUser($request, $db);

    }

    /**
     * Ensures that if the deleteUser method is called and user_id is an
     * admin, but the delete fails, then an exception is thrown
     *
     * @return void
     *
     * @test
     * @expectedException        \Exception
     * @expectedExceptionMessage There was a problem trying to delete the user
     */
    public function testDeleteUserWithAdminAccessThowsExceptionOnFailedDelete()
    {
        $request = new \Request([], ['REQUEST_URI' => "http://api.dev.joind.in/v2.1/users/3", 'REQUEST_METHOD' => 'DELETE']);
        $request->user_id = 1;
        $usersController = new \UsersController();
        // Please see below for explanation of why we're mocking a "mock" PDO
        // class
        $db = $this->getMockBuilder('\JoindinTest\Inc\mockPDO')->getMock();

        $userMapper = $this->getMockBuilder('\UserMapper')
            ->setConstructorArgs(array($db,$request))
            ->getMock();

        $userMapper
            ->expects($this->once())
            ->method('thisUserHasAdminOn')
            ->will($this->returnValue(true));

        $userMapper
            ->expects($this->once())
            ->method('delete')
            ->will($this->returnValue(false));

        $usersController->setUserMapper($userMapper);
        $usersController->deleteUser($request, $db);

    }


    /**
     * Ensures that if the deleteUser method is called and user_id is an
     * admin, but the delete fails, then an exception is thrown
     *
     * @return void
     */
    public function testDeleteUserWithAdminAccessDeletesSuccesfully()
    {
        $request = new \Request([], ['REQUEST_URI' => "http://api.dev.joind.in/v2.1/users/3", 'REQUEST_METHOD' => 'DELETE']);
        $request->user_id = 1;
        $usersController = new \UsersController();
        // Please see below for explanation of why we're mocking a "mock" PDO
        // class
        $db = $this->getMockBuilder('\JoindinTest\Inc\mockPDO')->getMock();

        $userMapper = $this->getMockBuilder('\UserMapper')
            ->setConstructorArgs(array($db,$request))
            ->getMock();

        $userMapper
            ->expects($this->once())
            ->method('thisUserHasAdminOn')
            ->will($this->returnValue(true));

        $userMapper
            ->expects($this->once())
            ->method('delete')
            ->will($this->returnValue(true));

        $usersController->setUserMapper($userMapper);
        $this->assertNull($usersController->deleteUser($request, $db));

    }

    public function testThatUserDataIsNotDoubleEscapedOnUserCreation()
    {
        $request = $this->getMockBuilder('\Request')->disableOriginalConstructor()->getMock();
        $request->user_id = 1;
        $request->base = 'base';
        $request->path_info = 'path_info';
        $request->method('getParameter')->withConsecutive(
            ['username'],
            ['full_name'],
            ['email'],
            ['password'],
            ['twitter_username']
        )->willReturnOnConsecutiveCalls(
            'user"\'stuff',
            'full"\'stuff',
            'mailstuff@example.com',
            'pass"\'stuff',
            'twitter"\'stuff'
        );

        $view = $this->getMockBuilder('\ApiView')->disableOriginalConstructor()->getMock();
        $view->expects($this->once())->method('setHeader')->with('Location', 'basepath_info/1');
        $view->expects($this->once())->method('setResponseCode')->with(201);
        $request->method('getView')->willReturn($view);

        $db = $this->getMockBuilder('\PDO')->disableOriginalConstructor()->getMock();

        $userMapper = $this->getMockBuilder('\UserMapper')->disableOriginalConstructor()->getMock();
        $userMapper->expects($this->once())->method('getUserByUsername')->with('user"\'stuff')->willReturn(false);
        $userMapper->expects($this->once())->method('checkPasswordValidity')->with('pass"\'stuff')->willReturn(true);
        $userMapper->expects($this->once())->method('generateEmailVerificationTokenForUserId')->willReturn('token');
        $userMapper->expects($this->once())->method('createUser')->with([
            'username' => 'user"\'stuff',
            'full_name' => 'full"\'stuff',
            'email' => 'mailstuff@example.com',
            'password' => 'pass"\'stuff',
            'twitter_username' => 'twitter"\'stuff',
        ])->willReturn(true);

        $emailService = $this->getMockBuilder('\UserRegistrationEmailService')->disableOriginalConstructor()->getMock();
        $emailService->method('sendEmail');

        $controller = new \UsersController();
        $controller->setUserMapper($userMapper);
        $controller->setUserRegistrationEmailService($emailService);

        $controller->postAction($request, $db);
    }

    public function testThatUserDataIsNotDoubleEscapedOnUserEdit()
    {
        $request = $this->getMockBuilder('\Request')->disableOriginalConstructor()->getMock();
        $request->method('getUserId')->willReturn(1);
        $request->method('getParameter')->withConsecutive(
            ['password'],
            ['full_name'],
            ['email'],
            ['username'],
            ['twitter_username']
        )->willReturnOnConsecutiveCalls(
            '',
            'full"\'stuff',
            'mailstuff@example.com',
            'user"\'stuff',
            'twitter"\'stuff'
        );

        $oauthmodel = $this->getMockBuilder('\OAuthModel')->disableOriginalConstructor()->getMock();
        $oauthmodel->expects($this->once())->method('isAccessTokenPermittedPasswordGrant')->willReturn(true);
        $request->expects($this->once())->method('getOauthModel')->willReturn($oauthmodel);

        $view = $this->getMockBuilder('\ApiView')->disableOriginalConstructor()->getMock();
        $view->expects($this->once())->method('setHeader')->with('Content-Length', 0);
        $view->expects($this->once())->method('setResponseCode')->with(204);
        $request->method('getView')->willReturn($view);

        $db = $this->getMockBuilder('\PDO')->disableOriginalConstructor()->getMock();

        $userMapper = $this->getMockBuilder('\UserMapper')->disableOriginalConstructor()->getMock();
        $userMapper->expects($this->once())->method('getUserByUsername')->with('user"\'stuff')->willReturn(false);
        $userMapper->expects($this->once())->method('thisUserHasAdminOn')->willReturn(true);
        $userMapper->expects($this->once())->method('editUser')->with([
            'username' => 'user"\'stuff',
            'full_name' => 'full"\'stuff',
            'email' => 'mailstuff@example.com',
            'twitter_username' => 'twitter"\'stuff',
            'user_id' => false,
        ])->willReturn(true);

        $controller = new \UsersController();
        $controller->setUserMapper($userMapper);

        $controller->updateUser($request, $db);
    }
}

