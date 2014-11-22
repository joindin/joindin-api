<?php

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
                'request' => $this->getRequest(array('', 'v1', 'test'))
            ),
            array( // #0
                'request' => $this->getRequest(array('', 'v2', 'bob')),
                'expectedException' => 'Exception',
                400
            ),
            array( // #0
                'request' => $this->getRequest(array('', 'v2')),
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
     * @param Request $request
     * @param string|false $expectedException
     * @param integer|false $expectedExceptionCode
     */
    public function testRoute(Request $request, $expectedException = false, $expectedExceptionCode = false)
    {
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

    /**
     * Gets a Request for testing
     *
     * @param array $urlElements
     *
     * @return Request
     */
    private function getRequest(array $urlElements)
    {
        $request = $this->getMock('Request', array('getUrlElement'), array(), '', false);
        $request->url_elements = $urlElements;
        return $request;
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