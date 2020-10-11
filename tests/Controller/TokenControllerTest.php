<?php

namespace Joindin\Api\Test\Controller;

use Exception;
use Joindin\Api\Controller\TokenController;
use Joindin\Api\Request;
use PDO;
use PHPUnit\Framework\TestCase;
use Teapot\StatusCode\Http;

final class TokenControllerTest extends TestCase
{
    private $request;

    private $pdo;

    public function setup(): void
    {
        $this->request = $this->getMockBuilder(Request::class)->disableOriginalConstructor()->getMock();
        $this->pdo     = $this->getMockBuilder(PDO::class)->disableOriginalConstructor()->getMock();
    }

    /**
     * @group uses_pdo
     */
    public function testThatDeletingATokenWithoutLoginThrowsException()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('You must be logged in');
        $this->expectExceptionCode(Http::UNAUTHORIZED);

        $usersController = new TokenController();

        $usersController->revokeToken($this->request, $this->pdo);
    }

    /**
     * @group uses_pdo
     */
    public function testThatRetrievingTokensWithoutLoginThrowsException()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('You must be logged in');
        $this->expectExceptionCode(Http::UNAUTHORIZED);

        $usersController = new TokenController();

        $usersController->listTokensForUser($this->request, $this->pdo);
    }
}
