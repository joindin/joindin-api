<?php

namespace JoindinTest\Controller;

use PHPUnit\Framework\TestCase;

class TokenControllerTest extends TestCase
{
    private $request;

    private $pdo;

    public function setup()
    {
        $this->request = $this->getMockBuilder('Request')->disableOriginalConstructor()->getMock();
        $this->pdo = $this->getMockBuilder('PDO')->disableOriginalConstructor()->getMock();
    }

    /**
     * @expectedException        \Exception
     * @expectedExceptionCode    401
     * @expectedExceptionMessage You must be logged in
     */
    public function testThatDeletingATokenWithoutLoginThrowsException()
    {
        $usersController = new \TokenController();

        $usersController->revokeToken($this->request, $this->pdo);
    }

    /**
     * @expectedException        \Exception
     * @expectedExceptionCode    401
     * @expectedExceptionMessage You must be logged in
     */
    public function testThatRetrievingTokensWithoutLoginThrowsException()
    {
        $usersController = new \TokenController();

        $usersController->listTokensForUser($this->request, $this->pdo);
    }
}
