<?php


namespace Joindin\Api\Factory;


use Joindin\Api\Model\ApiMapper;
use Joindin\Api\Request;
use PDO;

class MapperFactory
{
    protected $mappers = [];

    public function getMapper($mapperClass, PDO $db, Request $request) : ApiMapper {
        return $this->mappers[$mapperClass] ?? new $mapperClass($db, $request);
    }

    public function setMapper(ApiMapper $mapper) {
        $this->mappers[get_class($mapper)] = $mapper;
    }
}
