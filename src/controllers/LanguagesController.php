<?php

class LanguagesController extends ApiController
{
    public function handle(Request $request, $db) {
        // only GET is implemented so far
        if ($request->getVerb() == 'GET') {
            return $this->getAction($request, $db);
        }
        return false;
    }

    public function getAction($request, $db) {
        $language_id = $this->getItemId($request);

        // verbosity - here for consistency as we don't have verbose language details to return at the moment
        $verbose = $this->getVerbosity($request);

        // pagination settings
        $start = $this->getStart($request);
        $resultsperpage = $this->getResultsPerPage($request);

        $mapper = new LanguageMapper($db, $request);

        if ($language_id !== false) {
            $list = $mapper->getLanguageById($language_id, $verbose);
            if (count($list['languages']) == 0) {
                throw new Exception('Language not found', 404);
            }
        } else {
            $list = $mapper->getLanguageList($resultsperpage, $start, $verbose);
        }

        return $list;
    }
}