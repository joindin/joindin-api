<?php
/**
 * Actions to deal with search endpoints triggering an elasticsearch query
 */

class SearchController extends ApiController {
    public function handle(Request $request, $db) {
        if($request->getVerb() == 'GET') {
            // Get from all index types
            return $this->getAction($request, $db);
        }
        return false;
    }

    public function getAction(Request $request, $db) {
        $searchRequest = filter_var(
            $request->getParameter('search'),
            FILTER_SANITIZE_STRING
        );

        $searchTypes = filter_var(
            $request->getParameter('type'),
            FILTER_SANITIZE_STRING
        );

        $types = ['events', 'talks', 'speakers'];
        if($searchTypes) {
            $types = explode(',', $searchTypes);

            // Validate selected types
            $types = array_intersect(['events', 'talks', 'speakers'], $types);
        }

        $pageRequest = $this->getStart($request);
        $pageLimit = $this->getResultsPerPage($request);

        $searchMapper = new SearchMapper($db, $request);
        $results = $searchMapper->getResultsByQuery($searchRequest, $pageLimit, $pageRequest, $types);

        return $results;
    }
}
