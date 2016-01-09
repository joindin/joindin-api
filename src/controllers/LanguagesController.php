<?php

class LanguagesController extends ApiController
{
    public function getLanguage($request, $db)
    {
        $language_id = $this->getItemId($request);
        // verbosity - here for consistency as we don't have verbose language details to return at the moment
        $verbose = $this->getVerbosity($request);

        $mapper = new LanguageMapper($db, $request);
        $list   = $mapper->getLanguageById($language_id, $verbose);

        if (count($list['languages']) == 0) {
            throw new Exception('Language not found', 404);
        }

        return $list;
    }

    public function getAllLanguages($request, $db)
    {
        // verbosity - here for consistency as we don't have verbose language details to return at the moment
        $verbose = $this->getVerbosity($request);

        // pagination settings
        $start          = $this->getStart($request);
        $resultsperpage = $this->getResultsPerPage($request);

        $mapper = new LanguageMapper($db, $request);
        $list   = $mapper->getLanguageList($resultsperpage, $start, $verbose);

        return $list;
    }
}
