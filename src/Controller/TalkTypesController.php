<?php

namespace Joindin\Api\Controller;

use _HumbugBoxf43f7c5c5350\Nette\Iterators\Mapper;
use Exception;
use Joindin\Api\Factory\MapperFactory;
use Joindin\Api\Model\TalkTypeMapper;
use PDO;
use Joindin\Api\Request;
use Teapot\StatusCode\Http;

class TalkTypesController extends BaseApiController
{
    /**
     * @var MapperFactory|null
     */
    private $mapperFactory;

    public function __construct(array $config = [], MapperFactory $mapperFactory = null)
    {
        parent::__construct($config);

        $this->mapperFactory = $mapperFactory ?? new MapperFactory();
    }

    public function getAllTalkTypes(Request $request, PDO $db)
    {
        // verbosity - here for consistency as we don't have verbose talk type details to return at the moment
        $verbose = $this->getVerbosity($request);

        // pagination settings
        $start          = $this->getStart($request);
        $resultsperpage = $this->getResultsPerPage($request);

        $mapper = $this->mapperFactory->getMapper(TalkTypeMapper::class, $db, $request);

        return $mapper->getTalkTypeList($resultsperpage, $start, $verbose);
    }

    public function getTalkType(Request $request, PDO $db)
    {
        $talk_type_id = $this->getItemId($request);
        // verbosity - here for consistency as we don't have verbose talk type details to return at the moment
        $verbose = $this->getVerbosity($request);

        $mapper = $this->mapperFactory->getMapper(TalkTypeMapper::class, $db, $request);
        $list   = $mapper->getTalkTypeById($talk_type_id, $verbose);

        if (count($list['talk_types']) == 0) {
            throw new Exception('Talk type not found', Http::NOT_FOUND);
        }

        return $list;
    }
}
