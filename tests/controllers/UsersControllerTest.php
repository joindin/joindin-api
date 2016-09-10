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


        $usersController->deleteUser($request, $db, $userMapper);
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


        $usersController->deleteUser($request, $db, $userMapper);
    }


    /**
     * Ensures that if the deleteUser method is called and user_id is an
     * admin, but the delete fails, then an exception is thrown
     *
     * @return void
     */
    public function testDeleteUserWithAdminAccessDeletesSuccesfully()
    {
        define('UNIT_TEST', 1);
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


        $this->assertTrue($usersController->deleteUser($request, $db, $userMapper));
    }

}



/**
 * Class to allow for mocking PDO to send to the OAuthModel
 */
class mockPDO extends \PDO
{
    /**
     * Constructor that does nothing but helps us test with fake database
     * adapters
     */
    public function __construct()
    {
        // We need to do this crap because PDO has final on the __sleep and
        // __wakeup methods. PDO requires a parameter in the constructor but we don't
        // want to create a real DB adapter. If you tell getMock to not call the
        // original constructor, it fakes stuff out by unserializing a fake
        // serialized string. This way, we've got a "PDO" object but we don't need
        // PHPUnit to fake it by unserializing a made-up string. We've neutered
        // the constructor in mockPDO.
    }

}