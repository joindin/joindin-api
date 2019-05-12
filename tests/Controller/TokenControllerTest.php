<?php

namespace Joindin\Api\Test\Controller;

use PHPUnit\Framework\TestCase;

class TokenControllerTest extends TestCase
{
    private $request;

    private $pdo;

    public function setup(): void
    {
        $this->request = $this->getMockBuilder('Joindin\Api\Request')->disableOriginalConstructor()->getMock();
        $this->pdo     = $this->getMockBuilder('PDO')->disableOriginalConstructor()->getMock();
    }

    public function testThatDeletingATokenWithoutLoginThrowsException()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('You must be logged in');
        $this->expectExceptionCode(401);

        $usersController = new \Joindin\Api\Controller\TokenController();

        $usersController->revokeToken($this->request, $this->pdo);
    }

    public function testThatRetrievingTokensWithoutLoginThrowsException()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('You must be logged in');
        $this->expectExceptionCode(401);

        $usersController = new \Joindin\Api\Controller\TokenController();

        $usersController->listTokensForUser($this->request, $this->pdo);
    }
}
