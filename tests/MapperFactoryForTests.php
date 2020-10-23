<?php


namespace Joindin\Api\Test;

use Joindin\Api\Factory\MapperFactory;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class MapperFactoryForTests extends MapperFactory
{
    public function getMapperMock(TestCase $case, $mapperClass): MockObject
    {
        if (empty($this->mappers[$mapperClass])) {
            $this->setMapperMock($case, $mapperClass);
        }
        return $this->mappers[$mapperClass];
    }

    public function createMapperMock(TestCase $case, $mapperClass): MockObject
    {
        return $case->getMockBuilder($mapperClass)->disableOriginalConstructor()->getMock();
    }

    public function setMapperMockAs($mapperClass, MockObject $mock)
    {
        $this->mappers[$mapperClass] = $mock;
    }

    public function setMapperMock(TestCase $case, $mapperClass)
    {
        $this->mappers[$mapperClass] = $case->getMockBuilder($mapperClass)->disableOriginalConstructor()->getMock();
    }

    public function setMapperMocks(TestCase $case, ...$mapperClasses)
    {
        foreach ($mapperClasses as $mapperClass) {
            $this->setMapperMock($case, $mapperClass);
        }
    }
}
