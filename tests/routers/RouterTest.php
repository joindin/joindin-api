<?php

require_once __DIR__ . '/../../src/inc/Request.php';

/**
 * A class to test DefaultRouter
 *
 * @covers BaseRouter
 */
class RouterTest extends PHPUnit_Framework_TestCase
{

    /**
     * DataProvider for testConstruct
     *
     * @return array
     */
    public function constructProvider()
    {
        return array(
            array( // #0
                'config' => array('xyz')
            )
        );
    }

    /**
     * @dataProvider constructProvider
     *
     * @covers BaseRouter::__construct
     * @covers BaseRouter::getConfig
     *
     * @param array $config
     */
    public function testConstruct(array $config)
    {
        $obj = new TestRouter3($config);
        $this->assertEquals($config, $obj->getConfig());
    }
}

class TestRouter3 extends BaseRouter
{


    /**
     * {@inheritdoc}
     */
    public function dispatch(Route $route, Request $request, $db)
    {
        throw new BadMethodCallException('Method not implemented');
    }

    /**
     * {@inheritdoc}
     */
    public function getRoute(Request $request)
    {
        throw new BadMethodCallException('Method not implemented');
    }

    public function route(Request $request, $db)
    {
    }
}
