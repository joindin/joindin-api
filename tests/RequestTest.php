<?php

namespace Joindin\Api\Test;

use Joindin\Api\Model\OAuthModel;
use Joindin\Api\Request;
use Joindin\Api\View\ApiView;
use PHPUnit\Framework\TestCase;
use Teapot\StatusCode\Http;
use Joindin\Api\View\JsonView;
use Joindin\Api\View\HtmlView;
use Joindin\Api\View\JsonPView;

final class RequestTest extends TestCase
{
    /**
     * Make sure we have everything we need - in this case the config
     */
    public function setUp(): void
    {
        include __DIR__ . '/../src/config.php';
        $this->config = $config;
    }

    /**
     * Ensures that if a parameter was sent in, calling getParameter for it will
     * return the value it was set to.
     *
     * @return void
     *
     * @backupGlobals
     */
    public function testGetParameterReturnsValueOfRequestedParameter()
    {
        $queryString = http_build_query(
            [
                'foo' => 'bar',
                'baz' => 'samoflange',
            ]
        );

        $server  = [
            'QUERY_STRING' => $queryString,
        ];
        $request = new Request($this->config, $server);

        $this->assertEquals('bar', $request->getParameter('foo'));
        $this->assertEquals('samoflange', $request->getParameter('baz'));
    }

    /**
     * Ensures that getParameter returns the default value if the parameter requested
     * was not set.
     *
     * @return void
     */
    public function testGetParameterReturnsDefaultIfParameterNotSet()
    {
        $uniq    = uniqid();
        $request = new Request($this->config, []);
        $result  = $request->getParameter('samoflange', $uniq);

        $this->assertSame($uniq, $result);
    }

    /**
     * Ensures that methods are properly loaded from the
     * $_SERVER['REQUEST_METHOD'] variable
     *
     * @param string $method Method to try
     *
     * @return void
     *
     * @dataProvider methodProvider
     * @backupGlobals
     */
    public function testRequestMethodIsProperlyLoaded($method)
    {
        $request = new Request($this->config, ['REQUEST_METHOD' => $method]);

        $this->assertEquals($method, $request->getVerb());
    }

    /**
     * Ensures that a verb can be set on the request with setVerb
     *
     * @param string $verb Verb to set
     *
     * @return void
     *
     * @dataProvider methodProvider
     */
    public function testSetVerbAllowsForSettingRequestVerb($verb)
    {
        $request = new Request($this->config, []);
        $request->setVerb($verb);

        $this->assertEquals($verb, $request->getVerb());
    }

    /**
     * Ensure the setVerb method is fluent
     *
     * @return void
     *
     */
    public function testSetVerbIsFluent()
    {
        $request = new Request($this->config, []);
        $this->assertSame($request, $request->setVerb(uniqid()));
    }

    /**
     * Provides a list of valid HTTP verbs to test with
     *
     * @return array
     */
    public function methodProvider()
    {
        return [
            ['GET'],
            ['POST'],
            ['PUT'],
            ['DELETE'],
            ['TRACE'],
            ['HEAD'],
            ['OPTIONS'],
        ];
    }

    /**
     * Ensures that the default value is returned if the requested index is
     * not found on getUrlElement
     *
     * @return void
     */
    public function testGetUrlElementReturnsDefaultIfIndexIsNotFound()
    {
        $request = new Request($this->config, []);

        $default = uniqid('', true);
        $result  = $request->getUrlElement(22, $default);

        $this->assertEquals($default, $result);
    }

    /**
     * Ensures that url elements can be properly fetched with a call to
     * getUrlElement
     *
     * @return void
     *
     * @backupGlobals
     */
    public function testGetUrlElementReturnsRequestedElementFromPath()
    {
        $server  = ['PATH_INFO' => 'foo/bar/baz'];
        $request = new Request($this->config, $server);
        $this->assertEquals('foo', $request->getUrlElement(0));
        $this->assertEquals('bar', $request->getUrlElement(1));
        $this->assertEquals('baz', $request->getUrlElement(2));
    }

    /**
     * Ensures the accept headers are properly parsed
     *
     * @return void
     *
     * @backupGlobals
     */
    public function testAcceptsHeadersAreParsedCorrectly()
    {
        $server  = [
            'HTTP_ACCEPT' =>
                'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
        ];
        $request = new Request($this->config, $server);

        $this->assertFalse($request->accepts('image/png'));
        $this->assertTrue($request->accepts('text/html'));
        $this->assertTrue($request->accepts('application/xhtml+xml'));
        $this->assertTrue($request->accepts('application/xml'));
        $this->assertTrue($request->accepts('*/*'));
    }

    /**
     * Ensures that if we're accepting something that the accept headers
     * say, then we get back that format
     *
     * @return void
     *
     * @backupGlobals
     */
    public function testPreferredContentTypeOfReturnsADesiredFormatIfItIsAccepted()
    {
        $server  = [
            'HTTP_ACCEPT' =>
                'text/text,application/xhtml+xml,application/json;q=0.9,*/*;q=0.8',
        ];
        $request = new Request($this->config, $server);

        $result = $request->preferredContentTypeOutOf(
            ['text/html', 'application/json']
        );

        $this->assertEquals('application/json', $result);

        $result = $request->preferredContentTypeOutOf();

        $this->assertEquals('application/json', $result);
    }

    /**
     * Ensures that if the browser doesn't send an accept header we can deal with
     * we return json
     *
     * @return void
     *
     * @backupGlobals
     */
    public function testIfPreferredFormatIsNotAcceptedReturnJson()
    {
        $server  = [
            'HTTP_ACCEPT' =>
                'text/text,application/xhtml+xml,application/json;q=0.9,*/*;q=0.8',
        ];
        $request = new Request($this->config, $server);

        $result = $request->preferredContentTypeOutOf(
            ['text/html'],
            ['application/xml']
        );

        $this->assertEquals('json', $result);
    }

    /**
     * Ensures host is set correctly from headers
     *
     * @return void
     *
     * @backupGlobals
     */
    public function testHostIsSetCorrectlyFromTheHeaders()
    {
        $server  = ['HTTP_HOST' => 'joind.in'];
        $request = new Request($this->config, $server);

        $this->assertEquals('joind.in', $request->host);
        $this->assertEquals('joind.in', $request->getHost());
    }

    /**
     * Ensures that the setHost method is fluent
     *
     * @return void
     */
    public function testSetHostIsFluent()
    {
        $request = new Request($this->config, []);
        $this->assertSame($request, $request->setHost(uniqid('', true)));
    }

    /**
     * Ensures that setHost can be used to set a host
     *
     * @return void
     */
    public function testHostCanBeSetWithSetHost()
    {
        $host    = uniqid() . '.com';
        $request = new Request($this->config, []);
        $request->setHost($host);

        $this->assertEquals($host, $request->getHost());
    }

    /**
     * Ensures that if a json body is provided on a POST or PUT request, it
     * gets parsed as parameters
     *
     * @param string $method Method to use
     *
     * @return void
     *
     * @dataProvider postPutProvider
     * @backupGlobals
     * @throws \ReflectionException
     */
    public function testJsonBodyIsParsedAsParameters($method)
    {
        $body = json_encode(
            [
                'a'     => 'b',
                'array' => ['joind' => 'in'],
            ]
        );

        $inside        = new \stdClass();
        $inside->joind = 'in';

        $server = [
            'REQUEST_METHOD' => $method,
            'CONTENT_TYPE'   => 'application/json',
        ];
        /* @var $request Request */
        $request = $this->getMockBuilder('\Joindin\Api\Request')
            ->setMethods(['getRawBody'])
            ->setConstructorArgs([[], $server])
            ->getMock();
        $request->expects($this->once())
            ->method('getRawBody')
            ->willReturn($body);

        $request->setVerb($method);
        $request->parseParameters($server);

        $this->assertEquals('b', $request->getParameter('a'));
        $this->assertEquals($inside, $request->getParameter('array'));
    }

    /**
     * Provider for methods
     *
     * @return array
     */
    public function postPutProvider()
    {
        return [
            ['POST'],
            ['PUT'],
        ];
    }

    /**
     * Ensures that the scheme is set to http unless https is on
     *
     * @return void
     */
    public function testSchemeIsHttpByDefault()
    {
        $request = new Request($this->config, []);

        $this->assertEquals('http://', $request->scheme);
        $this->assertEquals('http://', $request->getScheme());
    }

    /**
     * Ensures that the scheme is set to https:// if the HTTPS value is
     * set to 'on'
     *
     * @return void
     *
     * @backupGlobals
     */
    public function testSchemeIsHttpsIfHttpsValueIsOn()
    {
        $server  = ['HTTPS' => 'on'];
        $request = new Request($this->config, $server);

        $this->assertEquals('https://', $request->scheme);
        $this->assertEquals('https://', $request->getScheme());
    }

    /**
     * Ensures setScheme provides a fluent interface
     *
     * @return void
     */
    public function testSetSchemeIsFluent()
    {
        $request = new Request($this->config, []);
        $this->assertSame($request, $request->setScheme('http://'));
    }

    /**
     * Ensures that the scheme can be set by the set scheme method
     *
     * @param string $scheme Scheme to set
     *
     * @return void
     *
     * @dataProvider schemeProvider
     */
    public function testSchemeCanBeSetBySetSchemeMethod($scheme)
    {
        $request = new Request($this->config, []);
        $request->setScheme($scheme);

        $this->assertEquals($scheme, $request->getScheme());
    }

    /**
     * Provides schemes for tests
     *
     * @return array
     */
    public function schemeProvider()
    {
        return [
            ['http://'],
            ['https://'],
        ];
    }

    /**
     * Ensures that an exception is thrown if the authorization header
     * doesn't have two parts
     *
     * @return void
     */
    public function testIfIdentificationDoesNotHaveTwoPartsExceptionIsThrown()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid Authorization Header');
        $this->expectExceptionCode(Http::BAD_REQUEST);

        $request = new Request($this->config, ['HTTPS' => 'on']);
        $request->identifyUser('This is a bad header');
    }

    /**
     * Ensures that an exception is thrown if the authorization header doesn't
     * start with oauth
     *
     * @return void
     */
    public function testIfIdentificationHeaderDoesNotStartWithOauthThrowException()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Unknown Authorization Header Received');
        $this->expectExceptionCode(Http::BAD_REQUEST);

        $request = new Request($this->config, ['HTTPS' => 'on']);
        $request->identifyUser('Auth Me');
    }

    /**
     * Ensures that identifyUser returns false if the request is HTTP
     *
     * @return void
     */
    public function testIfRequestIsntHTTPSReturnsFalse()
    {
        $config  = array_merge($this->config, ['mode' => 'production']);
        $request = new Request($config, []);
        $request->setScheme('http://');
        $this->assertFalse($request->identifyUser('This is a bad header'));
    }

    /**
     * Ensures that if getOAuthModel is called, an instance of OAuthModel
     * is returned
     *
     * @return void
     * @throws \ReflectionException
     */
    public function testGetOauthModelProvidesAnOauthModel()
    {
        // Please see below for explanation of why we're mocking a "mock" PDO
        // class
        $db = $this->getMockBuilder(
            '\Joindin\Api\Test\Mock\mockPDO'
        )->getMock();
        $db->method('getAvailableDrivers');

        $request = new Request($this->config, []);
        $result  = $request->getOAuthModel($db);

        $this->assertInstanceOf('Joindin\Api\Model\OAuthModel', $result);
    }

    /**
     * Ensures that if the getOauthModel method is called and no model is already
     * set, and no PDO adapter is provided, an exception is thrown
     *
     * @return void
     */
    public function testCallingGetOauthModelWithoutADatabaseAdapterThrowsAnException()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Db Must be provided to get Oauth Model');
        $this->expectExceptionCode(0);

        $request = new Request($this->config, []);
        $request->getOauthModel();
    }

    /**
     * Ensures that the setOauthModel method is fluent
     *
     * @return void
     * @throws \ReflectionException
     */
    public function testSetOauthModelMethodIsFluent()
    {
        /* @var $mockOauth \Joindin\Api\Model\OAuthModel */
        $mockOauth = $this->getMockBuilder('Joindin\Api\Model\OAuthModel')->disableOriginalConstructor()->getMock();
        $request   = new Request($this->config, []);

        $this->assertSame($request, $request->setOauthModel($mockOauth));
    }

    /**
     * Ensures that the setOauthModel method allows for an OAuthModel
     * to be set and retrieved
     *
     * @return void
     * @throws \ReflectionException
     */
    public function testSetOauthModelAllowsSettingOfOauthModel()
    {
        /* @var $mockOauth \Joindin\Api\Model\OAuthModel */
        $mockOauth = $this->getMockBuilder(OAuthModel::class)->disableOriginalConstructor()->getMock();
        $request   = new Request($this->config, []);
        $request->setOauthModel($mockOauth);

        $this->assertSame($mockOauth, $request->getOauthModel());
    }

    /**
     * Ensures that identifyUser method sets a user id on the request model when
     * using the oauth token type
     *
     * @return void
     * @throws \ReflectionException
     */
    public function testIdentifyUserWithOauthTokenTypeSetsUserIdForValidHeader()
    {
        $request   = new Request($this->config, ['HTTPS' => 'on']);
        $mockOauth = $this->getMockBuilder(OAuthModel::class)->disableOriginalConstructor()->getMock();
        $mockOauth->expects($this->once())
            ->method('verifyAccessToken')
            ->with('authPart')
            ->willReturn('TheUserId');

        $request->setOauthModel($mockOauth);

        $request->identifyUser('oauth authPart');

        $this->assertEquals('TheUserId', $request->user_id);
        $this->assertEquals('TheUserId', $request->getUserId());
    }

    /**
     * Ensures that identifyUser method sets a user id on the request model when
     * using the bearer token type
     *
     * @return void
     * @throws \ReflectionException
     */
    public function testIdentifyUserWithBearerTokenTypeSetsUserIdForValidHeader()
    {
        $request   = new Request($this->config, ['HTTPS' => 'on']);
        $mockOauth = $this->getMockBuilder(OAuthModel::class)->disableOriginalConstructor()->getMock();
        $mockOauth->expects($this->once())
            ->method('verifyAccessToken')
            ->with('authPart')
            ->willReturn('TheUserId');

        $request->setOauthModel($mockOauth);

        $request->identifyUser('Bearer authPart');

        $this->assertEquals('TheUserId', $request->user_id);
        $this->assertEquals('TheUserId', $request->getUserId());
    }

    /**
     * Ensures that the setUserId method is fluent
     *
     * @return void
     */
    public function testSetUserIdIsFluent()
    {
        $request = new Request($this->config, []);
        $this->assertSame($request, $request->setUserId('TheUserToSet'));
    }

    /**
     * Ensures that setUserId can set a user id into the model that can be
     * retrieved with getUserId
     *
     * @return void
     */
    public function testSetUserIdAllowsForSettingOfUserId()
    {
        $request = new Request($this->config, []);
        $user    = uniqid('', true);

        $request->setUserId($user);
        $this->assertEquals($user, $request->getUserId());
    }

    /**
     * Ensures the setPathInfo method allows setting of a path
     *
     * @return void
     */
    public function testSetPathInfoAllowsSettingOfPathInfo()
    {
        $path    = uniqid('', true) . '/' . uniqid('', true) . '/' . uniqid('', true);
        $parts   = explode('/', $path);
        $request = new Request($this->config, []);
        $request->setPathInfo($path);

        $this->assertEquals($path, $request->getPathInfo());
        $this->assertEquals($path, $request->path_info);

        $this->assertEquals($parts[0], $request->getUrlElement(0));
        $this->assertEquals($parts[1], $request->getUrlElement(1));
        $this->assertEquals($parts[2], $request->getUrlElement(2));
    }

    /**
     * Ensures the setPath method is fluent
     *
     * @return void
     */
    public function testSetPathIsFluent()
    {
        $request = new Request($this->config, []);
        $this->assertSame($request, $request->setPathInfo(uniqid('', true)));
    }

    /**
     * Ensures the setAccept header sets the accept variable
     *
     * @return void
     */
    public function testSetAcceptSetsTheAcceptVariable()
    {
        $accept      = uniqid('', true) . ',' . uniqid('', true) . ',' . uniqid('', true);
        $acceptParts = explode(',', $accept);

        $request = new Request($this->config, []);
        $request->setAccept($accept);
        $this->assertEquals($acceptParts, $request->accept);

        foreach ($acceptParts as $thing) {
            $this->assertTrue($request->accepts($thing));
        }
    }

    /**
     * Ensures that the setAccept method is fluent
     *
     * @return void
     */
    public function testSetAcceptsIsFluent()
    {
        $request = new Request($this->config, []);
        $this->assertSame($request, $request->setAccept(uniqid('', true)));
    }

    /**
     * Ensures the setBase method allows setting of the base variable
     *
     * @return void
     */
    public function testSetBaseAllowsSettingOfBase()
    {
        $request = new Request($this->config, []);
        $base    = uniqid('', true);
        $request->setBase($base);
        $this->assertEquals($base, $request->getBase());
        $this->assertEquals($base, $request->base);
    }

    /**
     * Ensures the setBase method is fluent
     *
     * @return void
     */
    public function testSetBaseIsFluent()
    {
        $request = new Request($this->config, []);
        $this->assertSame($request, $request->setBase(uniqid('', true)));
    }

    /**
     * DataProvider for testGetView
     *
     * NB: The array keys are for readability; order still matters
     *
     * @return array
     */
    public function getViewProvider()
    {
        return [
            [ // #0
                'parameters'    => [],
                'expectedClass' => JsonView::class,
            ],
            [ // #1
                'parameters'    => ['format' => 'html'],
                'expectedClass' => HtmlView::class,
            ],
            [ // #2
                'parameters'    => ['callback' => 'dave'],
                'expectedClass' => JsonPView::class,
            ],
            [ // #3
                'parameters'    => ['format' => 'html'],
                'expectedClass' => HtmlView::class,
            ],
            [ // #4
                'parameters'    => ['format' => 'html'],
                'expectedClass' => HtmlView::class,
                'accepts'       => 'text/html',
            ],
            [ // #5
                'parameters'    => [],
                'expectedClass' => JsonView::class,
                'accepts'       => 'application/json',
            ],
            [ // #6
                'parameters'    => [],
                'expectedClass' => JsonView::class,
                'accepts'       => 'application/json,text/html',
            ],
            [ // #7
                'parameters'    => [],
                'expectedClass' => HtmlView::class,
                'accepts'       => 'text/html,applicaton/json',
                'view'          => new \Joindin\Api\View\HtmlView(),
//                'skip' => true // Currently we're not applying Accept correctly
// Can @choult check what's the reason for the skip?
            ],
            [ // #8
                'parameters'    => ['format' => 'html'],
                'expectedClass' => HtmlView::class,
                'accepts'       => 'applicaton/json,text/html',
            ],
            [ // #9
                'parameters'    => [],
                'expectedClass' => false,
                'accepts'       => '',
                'view'          => new ApiView(),
            ],
        ];
    }

    /**
     * @dataProvider getViewProvider
     * @covers       \Joindin\Api\Request::getView
     * @covers       \Joindin\Api\Request::setView
     *
     * @param array $parameters Request query parameters
     * @param string $expectedClass The name of the expected class to be returned
     * @param string $accept An HTTP Accept header
     * @param ApiView|null $view A plan getter/setter test
     * @param boolean $skip Set to true to skip the test
     *
     * @test
     */
    public function testGetView(
        array $parameters = [],
        $expectedClass = '',
        $accept = '',
        ApiView $view = null,
        $skip = false
    ) {
        if ($skip) {
            $this->markTestSkipped();
        }

        $server = [
            'QUERY_STRING' => http_build_query($parameters),
            'HTTP_ACCEPT'  => $accept,
        ];

        $request = new Request($this->config, $server);
        if ($view) {
            $request->setView($view);
            $this->assertEquals($view, $request->getView());
        } else {
            $view = $request->getView();
            $this->assertInstanceOf($expectedClass, $view);
        }
    }

    /**
     * DataProvider for testGetSetFormatChoices
     *
     * NB: The array keys are for readability; order still matters
     *
     * @return array
     */
    public function getSetFormatChoicesProvider()
    {
        return [
            [ // #0
                'expected' => [
                    Request::CONTENT_TYPE_JSON,
                    Request::CONTENT_TYPE_HTML,
                ],
            ],
            [ // #1
                'expected' => [
                    Request::CONTENT_TYPE_HTML,
                    Request::CONTENT_TYPE_JSON,
                ],
                'choices'  => [
                    Request::CONTENT_TYPE_HTML,
                    Request::CONTENT_TYPE_JSON,
                ],
            ],
            [ // #2
                'expected' => ['a', 'b'],
                'choices'  => ['a', 'b'],
            ],
        ];
    }

    /**
     * @dataProvider getSetFormatChoicesProvider
     * @covers       \Joindin\Api\Request::getFormatChoices
     * @covers       \Joindin\Api\Request::setFormatChoices
     *
     * @param array $expected
     * @param array|null $choices
     */
    public function testGetSetFormatChoices(
        array $expected,
        array $choices = null
    ) {
        $request = new Request($this->config, []);
        if ($choices) {
            $request->setFormatChoices($choices);
        }

        $this->assertEquals($expected, $request->getFormatChoices());
    }

    /**
     * @covers \Joindin\Api\Request::getRouteParams
     * @covers \Joindin\Api\Request::setRouteParams
     */
    public function testGetSetRouteParams()
    {
        $request = new Request($this->config, []);
        $params  = ['event_id' => 10];
        $request->setRouteParams($params);
        $this->assertEquals($params, $request->getRouteParams());
    }

    /**
     * @covers \Joindin\Api\Request::getAccessToken
     * @covers \Joindin\Api\Request::setAccessToken
     */
    public function testGetSetAccessToken()
    {
        $request = new Request($this->config, []);
        $token   = 'token';
        $request->setAccessToken($token);
        $this->assertEquals($token, $request->getAccessToken());
    }

    /**
     * Adding coverage for the case where PATH_INFO doesn't exist in $_SERVER but
     * REQUEST_URI does.
     */
    public function testConstructorParsesRequestUri()
    {
        $server  = ['REQUEST_URI' => '/v2/one/two?three=four'];
        $request = new Request($this->config, $server);
        $this->assertEquals('/v2/one/two', $request->getPathInfo());
    }

    /**
     * @dataProvider clientIpProvider
     */
    public function testGettingClientIp($header)
    {
        $_SERVER = array_merge($header, $_SERVER);
        $request = new Request($this->config, []);
        $this->assertEquals('192.168.1.1', $request->getClientIP());
    }

    public function clientIpProvider()
    {
        return [
            'remote_addr'     => [['REMOTE_ADDR' => '192.168.1.1']],
            'x-forwarded-for' => [['HTTP_X_FORWARDED_FOR' => '192.168.1.1']],
            'http-forwarded'  => [['HTTP_FORWARDED' => 'for=192.168.1.1, for=198.51.100.17']],
        ];
    }

    /** @dataProvider gettingClientUserAgentProvider */
    public function testGettingClientUserAgent($header, $userAgent)
    {
        $_SERVER = array_merge($header, $_SERVER);
        $request = new Request($this->config, []);
        $this->assertEquals($userAgent, $request->getClientUserAgent());
    }

    public function gettingClientUserAgentProvider()
    {
        return [
            [['HTTP_USER_AGENT' => 'Foo'], 'Foo'],
        ];
    }

    public function testGettingConfigValues()
    {
        $request = new Request(['Foo' => 'Bar'], []);
        $this->assertEquals('Bar', $request->getConfigValue('Foo'));
        $this->assertEquals('Foo', $request->getConfigValue('Bar', 'Foo'));
    }
}
