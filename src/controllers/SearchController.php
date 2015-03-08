<?php

class SearchController extends ApiController {
    public function handle(Request $request, $db) {
        if($request->getVerb() == 'GET') {
            // Get from all index types
            return $this->getAction($request, $db);
        } else if($request->getVerb() == 'POST') {
            // Complex query

        }
        return false;
    }

    public function getAction(Request $request, $db) {
        $searchRequest = filter_var(
            $request->getParameter('search'),
            FILTER_SANITIZE_STRING
        );

        $pageRequest = $this->getStart($request);
        $pageLimit = $this->getResultsPerPage($request);

	$searchMapper = new SearchMapper($db, $request);
	$results = $searchMapper->getResultsByQuery($searchRequest, $pageLimit, $pageRequest);

	return $results;
    }

    public function postAction(Request $request, $db) {

    }

    private function getFilter($request) {
        if(!empty($request->url_elements[3])) {
            return $request->url_elements[3];
        }
        return false;
    }
}
