<?php

namespace Joindin\Api\Test\View;

use Joindin\Api\View\JsonView;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Joindin\Api\View\JsonView
 */
final class JsonViewTest extends TestCase
{
    use \phpmock\phpunit\PHPMock;

    /**
     * DataProvider for testBuildOutput
     *
     * @return array
     */
    public function buildOutputProvider(): array
    {
        return [
            [ // #0
                'input'    => ['a' => 'b', 'c' => 10],
                'expected' => '{"a":"b","c":10}'
            ],
            [ // #1
                'input'    => ['stub' => '10', 'b' => ['c', 'd']],
                'expected' => '{"stub":"10","b":["c","d"],"meta":{"count":2}}'
            ],
            [ // #2 - JOINDIN-519
                'input'    => false,
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
    public function testBuildOutput(mixed $input, string $expected): void
    {
        $view = new JsonView();
        $this->assertEquals($expected, $view->buildOutput($input));
    }

    /**
     * @runInSeparateProcess
     *
     * @covers \Joindin\Api\View\JsonPView::render
     */
    public function testCorsHeaderIsSet(): void
    {
        $header = $this->getFunctionMock('Joindin\Api\View', "header");
        $header->expects(self::exactly(2))->withConsecutive(
            ['Content-Type: application/json; charset=utf8'],
            ['Access-Control-Allow-Origin: *']
        );

        $view = new JsonView();

        self::expectOutputString('"test"');

        $view->render('test');
    }
}
