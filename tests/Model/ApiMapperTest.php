<?php

namespace Joindin\Api\Test\Model;

use Exception;
use Joindin\Api\Model\ApiMapper;
use Joindin\Api\Request;
use PDO;
use PDOStatement;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

/**
 *
 */
final class ApiMapperTest extends TestCase
{
    private $pdo;
    private $request;

    public function setup(): void
    {
        $this->pdo     = $this->getMockBuilder(PDO::class)
                              ->disableOriginalConstructor()
                              ->getMock();
        $this->request = $this->getMockBuilder(Request::class)
                              ->disableOriginalConstructor()
                              ->getMock();
    }

    public function testThatApiMapperHasNoDefaultFields()
    {
        $mapper = new ApiMapper($this->pdo, $this->request);

        $this->assertEquals([], $mapper->getDefaultFields());
        $this->assertEquals([], $mapper->getVerboseFields());
    }

    public function testThatDefaultMapperReturnsNothing()
    {
        $mapper = new ApiMapper($this->pdo, $this->request);

        $result = [['a', 'b', 'c']];

        $this->assertEquals([[]], $mapper->transformResults($result, true));
        $this->assertEquals([[]], $mapper->transformResults($result, false));
        $this->assertNotEquals([], $mapper->transformResults($result, true));
        $this->assertNotEquals([], $mapper->transformResults($result, false));
    }

    /** @dataProvider defaultMapperConvertsDateTimeFieldsCorrectlyProvider */
    public function testThatDefaultMapperConvertsDateTimeFieldsCorrectly($expected, $values)
    {
        $mapper = new TestApiMapper($this->pdo, $this->request);

        $this->assertEquals($expected, $mapper->transformResults($values, false));
    }

    public function defaultMapperConvertsDateTimeFieldsCorrectlyProvider()
    {
        return [
            [
                [
                    [
                        'event_start_date' => '2016-09-19T05:54:18+00:00',
                        'event_tz_place'   => null,
                        'event_tz_cont'    => null,
                        'name'             => null,
                    ],

                ],
                [
                    [
                        'event_start_date' => 1474264458,
                        'event_tz_place'   => null,
                        'event_tz_cont'    => null,
                    ]
                ]
            ],
            [
                [
                    [
                        'event_start_date' => '2016-09-19T07:54:18+02:00',
                        'event_tz_place'   => 'Berlin',
                        'event_tz_cont'    => 'Europe',
                        'name'             => null,
                    ]
                ],
                [
                    [
                        'event_start_date' => 1474264458,
                        'event_tz_place'   => 'Berlin',
                        'event_tz_cont'    => 'Europe',
                    ]
                ]
            ],
        ];
    }

    public function testThatDefaultMappingReturnsOnlyDefaultFields()
    {
        $mapper = new TestApiMapper($this->pdo, $this->request);

        $this->assertEquals([
            'event_tz_place'   => 'event_tz_place',
            'event_tz_cont'    => 'event_tz_cont',
            'event_start_date' => 'event_start_date',
            'name'             => 'name',
        ], $mapper->getDefaultFields());
    }

    public function testBuildingTheLimitClause()
    {
        $mapper = new TestApiMapper($this->pdo, $this->request);


        $this->assertEquals('', $mapper->buildLimit(0, 12));
        $this->assertEquals(' LIMIT 12,1', $mapper->buildLimit(1, 12));
        $this->assertEquals(' LIMIT 12,1', $mapper->buildLimit("1", "12"));
    }

    /** @dataProvider retrievingTotalCountFromQueryWorksProvider */
    public function testThatRetrievingTotalCountFromQueryWorks($query, $countquery, $data, $returns, $exception = false)
    {
        $result = $this->getMockBuilder(PDOStatement::class)
                       ->disableOriginalConstructor()
                       ->getMock();
        if ($exception) {
            $result->method('execute')
                   ->with($this->equalTo($data))
                   ->will($this->throwException(new Exception()));
        } else {
            $result->method('execute')
                   ->with($this->equalTo($data));
        }
        $result->method('fetchColumn')
               ->with($this->equalTo(0))
               ->willReturn($returns);

        $pdo = $this->getMockBuilder(PDO::class)
                    ->disableOriginalConstructor()
                    ->getMock();
        $pdo->method('prepare')
            ->with($this->equalTo($countquery))
            ->willReturn($result);

        $mapper = new ApiMapper($pdo, $this->request);

        $obj    = new ReflectionClass(ApiMapper::class);
        $method = $obj->getMethod('getTotalCount');
        $method->setAccessible(true);

        $this->assertEquals($returns, $method->invoke($mapper, $query, $data));
    }

    public function retrievingTotalCountFromQueryWorksProvider()
    {
        return [
            [
                'select * from a LIMIT 12,1',
                'SELECT count(*) AS count FROM (select * from a ) as counter',
                [],
                7
            ],
            [
                'select * from a WHERE x = :foo LIMIT 12,3',
                'SELECT count(*) AS count FROM (select * from a WHERE x = :foo ) as counter',
                ['foo' => 12],
                6
            ],
            [
                'select * from a WHERE x = :foo LIMIT 12,3',
                'SELECT count(*) AS count FROM (select * from a WHERE x = :foo ) as counter',
                ['foo' => 12],
                0,
                true
            ]
        ];
    }

    /** @dataProvider inflectionWorksProvider */
    public function testThatInflectionWorks($string, $inflected)
    {
        $mapper = new TestApiMapper($this->pdo, $this->request);
        $this->assertEquals($inflected, $mapper->inflect($string));
    }

    public function inflectionWorksProvider()
    {
        return [
            ['test', 'test'],
            ['äöüß', 'aouss'],
            ['test test', 'test-test'],
            ['<-.,;:_+*#\'äÄöÖüÜ´`ß)(/&%$§"!>€@', '-aaoouuss'],
            [
                'Эволюция обработки вебхука Facebook: с нуля до 12\'500 в секунду',
                'evolucia-obrabotki-vebhuka-facebook-s-nula-do-12500-v-sekundu'
            ],
        ];
    }
}
