<?php


namespace Joindin\Api\Test\Controller;


use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use Joindin\Api\Controller\FacebookController;
use Joindin\Api\Factory\ClientFactory;
use Joindin\Api\Model\OAuthModel;
use Joindin\Api\Request;
use PDO;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Teapot\StatusCode\Http;
use Throwable;

/**
 * @property MockObject client
 * @property MockObject db
 * @property MockObject request
 * @property MockObject clientFactory
 * @property MockObject oauthModel
 * @property MockObject response
 */
class FacebookControllerTest extends TestCase
{
    /**
     * @var FacebookController
     */
    private $sut;

    private $config = [
        'facebook' => ['app_id' => "abc", 'app_secret' => "facebook secret"],
        'website_url' => "hello"
    ];

    protected function setUp(): void
    {
        $this->db = $this->getMockBuilder(PDO::class)->disableOriginalConstructor()->getMock();
        $this->request = $this->getMockBuilder(Request::class)->disableOriginalConstructor()->getMock();
        $this->clientFactory = $this->getMockBuilder(ClientFactory::class)->disableOriginalConstructor()->getMock();
        $this->oauthModel = $this->getMockBuilder(OAuthModel::class)->disableOriginalConstructor()->getMock();

        $this->sut = new FacebookController($this->config, $this->clientFactory);
    }

    public function testLogUserInThrowsExceptionIfNotConfigured()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Cannot login via Facebook');
        $this->expectExceptionCode(Http::NOT_IMPLEMENTED);

        $this->sut = new FacebookController(['facebook' => ['app_id' => "", 'app_secret' => ""]]);
        $this->sut->logUserIn($this->request, $this->db);
    }

    public function testLogUserInThrowsExceptionWhenClientNotPermittedPasswordGrant()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('This client cannot perform this action');
        $this->expectExceptionCode(Http::FORBIDDEN);

        $this->request->expects(self::exactly(2))->method("getParameter")->withConsecutive(['client_id'],
            ['client_secret'])
            ->willReturnOnConsecutiveCalls('clientId', 'clientSecret');

        $this->request->expects(self::once())->method("getOauthModel")->with($this->db)->willReturn($this->oauthModel);
        $this->oauthModel->expects(self::once())->method("isClientPermittedPasswordGrant")
            ->with("clientId", "clientSecret")->willReturn(false);

        $this->sut->logUserIn($this->request, $this->db);
    }

    public function testLogUserInThrowsExceptionWhenCodeNotSupplied()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage("The request code must be supplied");

        $this->request->expects(self::exactly(3))->method("getParameter")->withConsecutive(['client_id'],
            ['client_secret'], ['code'])
            ->willReturnOnConsecutiveCalls('clientId', 'clientSecret', "");

        $this->request->expects(self::once())->method("getOauthModel")->with($this->db)->willReturn($this->oauthModel);
        $this->oauthModel->expects(self::once())->method("isClientPermittedPasswordGrant")
            ->with("clientId", "clientSecret")->willReturn(true);

        $this->sut->logUserIn($this->request, $this->db);
    }


    public function testLogUserInThrowsExceptionWhenClientDoesNotHaveOkStatus()
    {
        self::markTestIncomplete("Lines 78 to 85 prevent this test from succeeding, and unknown if the trigger_error can be removed");
        $this->expectException(Exception::class);
        $this->expectExceptionMessage("Unexpected Facebook error (201: ");
        $this->expectExceptionCode(Http::INTERNAL_SERVER_ERROR);

        $this->request->expects(self::exactly(3))->method("getParameter")->withConsecutive(['client_id'],
            ['client_secret'], ['code'])
            ->willReturnOnConsecutiveCalls('clientId', 'clientSecret', "10");

        $this->request->expects(self::once())->method("getOauthModel")->with($this->db)->willReturn($this->oauthModel);
        $this->oauthModel->expects(self::once())->method("isClientPermittedPasswordGrant")
            ->with("clientId", "clientSecret")->willReturn(true);

        $mock = new MockHandler([new Response(Http::CREATED)]);
        $this->clientFactory->expects(self::once())->method("createClient")
            ->with(['headers' => ['Accept' => "application/json"]])->willReturn(new Client(['handler' => HandlerStack::create($mock)]));

        $this->sut->logUserIn($this->request, $this->db);
    }

    public function testLogUserInThrowsExceptionWhenSigninWithFacebookFails()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage("Could not sign in with Facebook");
        $this->expectExceptionCode(Http::FORBIDDEN);

        $this->request->expects(self::exactly(3))->method("getParameter")->withConsecutive(['client_id'],
            ['client_secret'], ['code'])
            ->willReturnOnConsecutiveCalls('clientId', 'clientSecret', "10");

        $this->request->expects(self::once())->method("getOauthModel")->with($this->db)->willReturn($this->oauthModel);
        $this->oauthModel->expects(self::once())->method("isClientPermittedPasswordGrant")
            ->with("clientId", "clientSecret")->willReturn(true);

        $mock = new MockHandler([
            new Response(Http::OK, [], json_encode(['access_token' => "hi"])),
            new Response(Http::CREATED, [], "")
        ]);
        $handlerStack = HandlerStack::create($mock);

        $this->clientFactory->expects(self::once())->method("createClient")
            ->with(['headers' => ['Accept' => "application/json"]])->willReturn(new Client(['handler' => $handlerStack]));

        $this->sut->logUserIn($this->request, $this->db);
    }

    public function testLogUserInThrowsExceptionWhenSigninWithFacebookDoesNotHaveEmail()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage("Email address is unavailable");
        $this->expectExceptionCode(Http::FORBIDDEN);

        $this->request->expects(self::exactly(3))->method("getParameter")->withConsecutive(['client_id'],
            ['client_secret'], ['code'])
            ->willReturnOnConsecutiveCalls('clientId', 'clientSecret', "10");

        $this->request->expects(self::once())->method("getOauthModel")->with($this->db)->willReturn($this->oauthModel);
        $this->oauthModel->expects(self::once())->method("isClientPermittedPasswordGrant")
            ->with("clientId", "clientSecret")->willReturn(true);

        $mock = new MockHandler([
            new Response(Http::OK, [], json_encode(['access_token' => "hi"])),
            new Response(Http::OK, [], json_encode([]))
        ]);
        $handlerStack = HandlerStack::create($mock);

        $this->clientFactory->expects(self::once())->method("createClient")
            ->with(['headers' => ['Accept' => "application/json"]])->willReturn(new Client(['handler' => $handlerStack]));

        $this->sut->logUserIn($this->request, $this->db);
    }

    public function testLogUserInThrowsExceptionWhenOauthCantCreateAccessTokenFromEmail()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage("Could not sign in with Facebook");
        $this->expectExceptionCode(Http::FORBIDDEN);

        $this->request->expects(self::exactly(3))->method("getParameter")->withConsecutive(['client_id'],
            ['client_secret'], ['code'])
            ->willReturnOnConsecutiveCalls('clientId', 'clientSecret', "10");

        $this->request->expects(self::once())->method("getOauthModel")->with($this->db)->willReturn($this->oauthModel);
        $this->oauthModel->expects(self::once())->method("isClientPermittedPasswordGrant")
            ->with("clientId", "clientSecret")->willReturn(true);

        $mock = new MockHandler([
            new Response(Http::OK, [], json_encode(['access_token' => "hi"])),
            new Response(Http::OK, [], json_encode(['email' => 'email', 'name' => 'name', 'id' => 'id']))
        ]);
        $handlerStack = HandlerStack::create($mock);

        $this->clientFactory->expects(self::once())->method("createClient")
            ->with(['headers' => ['Accept' => "application/json"]])->willReturn(new Client(['handler' => $handlerStack]));
        $this->oauthModel->expects(self::once())->method("createAccessTokenFromTrustedEmail")
            ->with("clientId", 'email', 'name', 'id')->willReturn(false);

        $this->sut->logUserIn($this->request, $this->db);
    }

    public function testLogUserInWorksAsExpected()
    {
        $this->request->expects(self::exactly(3))->method("getParameter")->withConsecutive(['client_id'],
            ['client_secret'], ['code'])
            ->willReturnOnConsecutiveCalls('clientId', 'clientSecret', "10");

        $this->request->expects(self::once())->method("getOauthModel")->with($this->db)->willReturn($this->oauthModel);
        $this->oauthModel->expects(self::once())->method("isClientPermittedPasswordGrant")
            ->with("clientId", "clientSecret")->willReturn(true);

        $container = [];
        $mock = MockHandler::createWithMiddleware([
            new Response(Http::OK, [], json_encode(['access_token' => "hi"])),
            new Response(Http::OK, [], json_encode(['email' => 'email', 'name' => 'name', 'id' => 'id']))
        ]);
        $handlerStack = HandlerStack::create($mock);
        $history = Middleware::history($container);
        $handlerStack->push($history);

        $this->clientFactory->expects(self::once())->method("createClient")
            ->with(['headers' => ['Accept' => "application/json"]])->willReturn(new Client(['handler' => $handlerStack]));
        $this->oauthModel->expects(self::once())->method("createAccessTokenFromTrustedEmail")
            ->with("clientId", 'email', 'name', 'id')->willReturn(['access_token' => "token", 'user_uri' => 'uri']);

        self::assertEquals(['access_token' => "token", 'user_uri' => 'uri'],
            $this->sut->logUserIn($this->request, $this->db));

        self::assertEquals("GET", $container[0]['request']->getMethod());
        self::assertEquals("graph.facebook.com/v2.10/oauth/access_token",
            $container[0]['request']->getUri()->getHost() . $container[0]['request']->getUri()->getPath());
        self::assertEquals("client_id=abc&redirect_uri=hello%2Fuser%2Ffacebook-access&client_secret=facebook%20secret&code=10",
            $container[0]['request']->getUri()->getQuery());

        self::assertEquals("GET", $container[1]['request']->getMethod());
        self::assertEquals("graph.facebook.com/me",
            $container[1]['request']->getUri()->getHost() . $container[1]['request']->getUri()->getPath());
        self::assertEquals("access_token=hi&fields=name%2Cemail", $container[1]['request']->getUri()->getQuery());
    }
}
