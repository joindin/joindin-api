<?php

namespace Joindin\Api\Test\View;

use Joindin\Api\View\JsonView;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Joindin\Api\View\JsonView
 */
final class JsonViewTest extends TestCase
{
    /**
     * DataProvider for testBuildOutput
     *
     * @return array
     */
    public function buildOutputProvider()
    {
        return [
            [ // #0
                'input' => ['a' => 'b', 'c' => 10],
                'expected' => '{"a":"b","c":10}'
            ],
            [ // #1
                'input' => ['stub' => '10', 'b' => ['c', 'd']],
                'expected' => '{"stub":"10","b":["c","d"],"meta":{"count":2}}'
            ],
            [ // #2 - JOINDIN-519
                'input' => false,
                'expected' => 'false'
            ],
        ];
    }

    /**
     * @dataProvider buildOutputProvider
     *
     * @covers       \Joindin\Api\View\JsonView::buildOutput
     *
     * @param mixed  $input
     * @param string $expected
     */
    public function testBuildOutput($input, $expected)
    {
        $view = new JsonView();
        $this->assertEquals($expected, $view->buildOutput($input));
    }
}
