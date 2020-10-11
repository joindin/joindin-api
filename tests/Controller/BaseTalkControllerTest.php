<?php


namespace Joindin\Api\Test\Controller;


use Exception;
use Joindin\Api\Controller\BaseTalkController;
use Joindin\Api\Model\ClientMapper;
use Joindin\Api\Model\EventMapper;
use Joindin\Api\Model\TalkMapper;
use Joindin\Api\Model\UserMapper;
use Joindin\Api\Request;
use PDO;
use PHPUnit\Framework\TestCase;
use Teapot\StatusCode\Http;

class BaseTalkControllerTest extends TestCase
{
    /**
     * @var BaseTalkControllerImplementation
     */
    private $sut;

    private $db;

    private $request;

    public function getMapperProvider()
    {
        return [
            [TalkMapper::class, "getTalkMapper"],
            [EventMapper::class, "getEventMapper"],
            [UserMapper::class, "getUserMapper"]
        ];
    }

    public function checkLoggedInProvider()
    {
        return [
            ['POST', 'create data'],
            ['DELETE', 'remove data'],
            ['GET', 'view data'],
            ['PUT', 'update data'],
        ];
    }

    protected function setUp(): void
    {
        $this->sut = new BaseTalkControllerImplementation();
        $this->request = $this->getMockBuilder(Request::class)->disableOriginalConstructor()->getMock();
        $this->db = $this->getMockBuilder(PDO::class)->disableOriginalConstructor()->getMock();
    }

    /**
     * @dataProvider checkLoggedInProvider
     */
    public function testCheckLoggedInThrowsExceptions($method, $message)
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('You must be logged in to '.$message);
        $this->expectExceptionCode(Http::UNAUTHORIZED);

        $this->request->expects(self::once())->method("getVerb")->willReturn($method);
        $this->sut->checkLoggedIn($this->request);
    }

    /**
     * @dataProvider getMapperProvider
     */
    public function testGetReturnsNewIfNotSet($mapperClass, $getMethod)
    {
        self::assertInstanceOf($mapperClass, call_user_func([$this->sut, $getMethod], $this->db, $this->request));
    }

    public function testGetMapperCreatesMapperIfNotSet()
    {
        self::assertInstanceOf(TalkMapper::class, $this->sut->getMapper('talk', $this->db, $this->request));
    }
}

class BaseTalkControllerImplementation extends BaseTalkController
{

    public function checkLoggedIn(Request $request)
    {
        parent::checkLoggedIn($request);
    }

    public function getMapper($type, PDO $db = null, Request $request = null)
    {
        return parent::getMapper($type, $db, $request);
    }
}
