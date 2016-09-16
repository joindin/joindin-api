<?php

/**
 *
 */
class ApiMapperTest extends PHPUnit_Framework_TestCase
{
    public function setup()
    {
        $this->pdo     = $this->getMockBuilder('PDO')
            ->disableOriginalConstructor()
            ->getMock();
        $this->request = $this->getMockBuilder('Request')
            ->disableOriginalConstructor()
            ->getMock();
    }

    public function testThatMapperInstanceHasDependencies()
    {
        $mapper = new ApiMapper($this->pdo, $this->request);

        $this->assertAttributeEquals($this->pdo, '_db', $mapper);
        $this->assertAttributeEquals($this->request, '_request', $mapper);
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

    public function testThatDefaultMappingReturnsOnlyDefaultFields()
    {
        $mapper = new ApiMapper($this->pdo, $this->request);

        $this->markTestIncomplete('Needs to be implemented somehow...');
    }

    public function testBuildingTheLimitClause()
    {
        $mapper = new ApiMapper($this->pdo, $this->request);

        $obj = new ReflectionClass('ApiMapper');
        $method = $obj->getMethod('buildLimit');
        $method->setAccessible(true);

        $this->assertEquals('', $method->invoke($mapper, 0, 12));
        $this->assertEquals(' LIMIT 12,1', $method->invoke($mapper, 1, 12));
        $this->assertEquals(' LIMIT 12,1', $method->invoke($mapper, "1", "12"));
    }

    /** @dataProvider retrievingTotalCountFromQueryWorksProvider */
    public function testThatRetrievingTotalCountFromQueryWorks($query, $countquery, $data, $returns)
    {
        $result = $this->getMockBuilder('PDOStatement')
            ->disableOriginalConstructor()
            ->getMock();
        $result->method('execute')
            ->with($this->equalTo($countquery))
            ->willReturn($returns);

        $pdo = $this->getMockBuilder('PDO')
            ->disableOriginalConstructor()
            ->getMock();
        $pdo->method('prepare')
            ->with($this->equalTo($countquery))
            ->willReturn($result);
        $mapper = new ApiMapper($pdo, $this->request);

        $obj = new ReflectionClass('ApiMapper');
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
            ]
        ];
    }
}
