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
        $this->assertTrue($usersController->deleteUser($request, $db));

    }

}

