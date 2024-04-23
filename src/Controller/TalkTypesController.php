<?php

namespace Joindin\Api\Controller;

use Exception;
use Joindin\Api\Model\TalkTypeMapper;
use PDO;
use Joindin\Api\Request;
use Teapot\StatusCode\Http;

class TalkTypesController extends BaseApiController
{
    public function getAllTalkTypes(Request $request, PDO $db): false|array
    {
        // verbosity - here for consistency as we don't have verbose talk type details to return at the moment
        $verbose = $this->getVerbosity($request);

        // pagination settings
        $start          = $this->getStart($request);
        $resultsperpage = $this->getResultsPerPage($request);

        $mapper = new TalkTypeMapper($db, $request);

        return $mapper->getTalkTypeList($resultsperpage, $start, $verbose);
    }

    public function getTalkType(Request $request, PDO $db): false|array
    {
        $talk_type_id = $this->getItemId($request, 'Talk type not found');
        // verbosity - here for consistency as we don't have verbose talk type details to return at the moment
        $verbose = $this->getVerbosity($request);

        $mapper = new TalkTypeMapper($db, $request);
        $list   = $mapper->getTalkTypeById($talk_type_id, $verbose);

        if ($list === false || count($list['talk_types']) === 0) {
            throw new Exception('Talk type not found', Http::NOT_FOUND);
        }

        return $list;
    }
}
