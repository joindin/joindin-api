<?php


namespace Joindin\Api\Test;

use Joindin\Api\Factory\MapperFactory;
use Joindin\Api\Model\ApiMapper;
use Joindin\Api\Request;
use PDO;
use PHPUnit\Framework\MockObject\MockBuilder;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Test;

class MapperFactoryForTests extends MapperFactory
{
    public function getMapperMock(TestCase $case, $mapperClass) : MockObject {
        if ($this->mappers[$mapperClass] == null) {
            $this->setMapperMock($case, $mapperClass);
        }
        return $this->mappers[$mapperClass];
    }

    public function setMapperMock(TestCase $case, $mapperClass) {
        $this->mappers[$mapperClass] = $case->getMockBuilder($mapperClass)->disableOriginalConstructor()->getMock();
    }

    public function setMapperMocks(TestCase $case, ...$mapperClasses) {
        foreach ($mapperClasses as $mapperClass) {
            $this->setMapperMock($case, $mapperClass);
        }
    }
}
