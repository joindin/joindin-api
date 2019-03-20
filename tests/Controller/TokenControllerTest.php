<?php

namespace Joindin\Api\Test\Controller;

use PHPUnit\Framework\TestCase;

class TokenControllerTest extends TestCase
{
    private $request;

    private $pdo;

    public function setup()
    {
        $this->request = $this->getMockBuilder('Joindin\Api\Request')->disableOriginalConstructor()->getMock();
        $this->pdo     = $this->getMockBuilder('PDO')->disableOriginalConstructor()->getMock();
    }

    /**
     * @expectedException        \Exception
     * @expectedExceptionCode    401
     * @expectedExceptionMessage You must be logged in
     */
    public function testThatDeletingATokenWithoutLoginThrowsException()
    {
        $usersController = new \Joindin\Api\Controller\TokenController();

        $usersController->revokeToken($this->request, $this->pdo);
    }

    /**
     * @expectedException        \Exception
     * @expectedExceptionCode    401
     * @expectedExceptionMessage You must be logged in
     */
    public function testThatRetrievingTokensWithoutLoginThrowsException()
    {
        $usersController = new \Joindin\Api\Controller\TokenController();

        $usersController->listTokensForUser($this->request, $this->pdo);
    }
}
