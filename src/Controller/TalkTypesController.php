<?php

namespace Joindin\Api\Controller;

use Exception;
use Joindin\Api\Model\TalkTypeMapper;
use PDO;
use Joindin\Api\Request;

class TalkTypesController extends BaseApiController
{
    public function getAllTalkTypes(Request $request, PDO $db)
    {
        // verbosity - here for consistency as we don't have verbose talk type details to return at the moment
        $verbose = $this->getVerbosity($request);

        // pagination settings
        $start          = $this->getStart($request);
        $resultsperpage = $this->getResultsPerPage($request);

        $mapper = new TalkTypeMapper($db, $request);

        return $mapper->getTalkTypeList($resultsperpage, $start, $verbose);
    }

    public function getTalkType(Request $request, PDO $db)
    {
        $talk_type_id = $this->getItemId($request);
        // verbosity - here for consistency as we don't have verbose talk type details to return at the moment
        $verbose = $this->getVerbosity($request);

        $mapper = new TalkTypeMapper($db, $request);
        $list   = $mapper->getTalkTypeById($talk_type_id, $verbose);

        if (count($list['talk_types']) == 0) {
            throw new Exception('Talk type not found', 404);
        }

        return $list;
    }
}
