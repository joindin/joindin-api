<?php

/**
 * @covers JsonView
 */
class JsonViewTest extends PHPUnit_Framework_TestCase
{

    /**
     * DataProvider for testBuildOutput
     *
     * @return array
     */
    public function buildOutputProvider()
    {
        return array(
            array( // #0
                'input' => array('a' => 'b', 'c' => 10),
                'expected' => '{"a":"b","c":10}'
            ),
            array( // #1
                'input' => array('stub' => '10', 'b' => array('c', 'd')),
                'expected' => '{"stub":"10","b":["c","d"],"meta":{"count":2}}'
            ),
            array( // #2 - JOINDIN-519
                'input' => false,
                'expected' => 'false'
            ),
        );
    }

    /**
     * @dataProvider buildOutputProvider
     *
     * @covers JsonView::buildOutput
     *
     * @param mixed $input
     * @param string $expected
     */
    public function testBuildOutput($input, $expected)
    {
        $view = new JsonView();
        $this->assertEquals($expected, $view->buildOutput($input));
    }
}