<?php
class TalkTypesController extends ApiController
{
    public function getAllTalkTypes($request, $db)
    {
        // verbosity - here for consistency as we don't have verbose talk type details to return at the moment
        $verbose = $this->getVerbosity($request);

        // pagination settings
        $start = $this->getStart($request);
        $resultsperpage = $this->getResultsPerPage($request);

        $mapper = new TalkTypeMapper($db, $request);
        $list = $mapper->getTalkTypeList($resultsperpage, $start, $verbose);
        return $list;
    }

    public function getTalkType($request, $db)
    {
        $talk_type_id = $this->getItemId($request);
        // verbosity - here for consistency as we don't have verbose talk type details to return at the moment
        $verbose = $this->getVerbosity($request);

        $mapper = new TalkTypeMapper($db, $request);
        $list = $mapper->getTalkTypeById($talk_type_id, $verbose);

        if (count($list['talk_types']) == 0) {
            throw new Exception('Talk type not found', 404);
        }
        return $list;
    }
}
