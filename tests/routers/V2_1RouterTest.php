<?php

require_once(__DIR__ . '/../../src/inc/Request.php');

/**
 * A class to test V2_1Router
 *
 * @covers V2_1Router
 */
class V2_1RouterTest extends PHPUnit_Framework_TestCase
{

    /**
     * DataProvider for testRoute
     *
     * @return array
     */
    public function routeProvider()
    {
        return array(
            array( // #0
                'url' => '/v1/test',
            ),
            array( // #1
                'url' => '/v1/test',
                'expectedException' => 'Exception',
                400
            ),
            array( // #2
                'url' => '/v1/test',
                'expectedException' => 'Exception',
                404
            )
        );
    }

    /**
     * @dataProvider routeProvider
     *
     * @covers V2_1Router::route
     *
     * @param string $url
     * @param string|false $expectedException
     * @param integer|false $expectedExceptionCode
     */
    public function testRoute($url, $expectedException = false, $expectedExceptionCode = false)
    {
        $request = new Request([], ['REQUEST_URI' => $url]);
        $db = 'database';
        $value = 'val';
        $obj = new V2_1Router(array('xyz' => 'abc'));

        try {
            $this->assertEquals($value, $obj->route($request, $db));
        } catch (Exception $ex) {
            if (!$expectedException) {
                throw $ex;
            }
            $this->assertInstanceOf($expectedException, $ex);
            if ($expectedExceptionCode !== false) {
                $this->assertEquals($expectedExceptionCode, $ex->getCode());
            }
        }
    }
}

class TestController extends ApiController
{
    public function __construct(array $config) {
        if (!isset($config['xyz'])) {
            throw new Exception('xyz', 1001);
        }
    }

    public function handle(Request $req, $db) {
        if ($db == 'database') {
            return 'val';
        }
    }
}