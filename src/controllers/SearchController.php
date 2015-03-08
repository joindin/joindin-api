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
        $pageLimit = $this->getResultsPerPage($request) ?: 10;

        $search_service = new SearchService(new Elasticsearch\Client, 'ji-search');
        $search_service->setSearchTypes(['events', 'talks', 'speakers']);
        $search_service->setQuery($searchRequest);

        $search_service->setLimit($pageLimit);
        $search_service->setOffset($pageRequest);

        $results = $search_service->search();

        $searchResults = [];
        foreach($results['hits']['hits'] as $hit) {
            $searchResults[$hit['_type']][] = $hit['_id'];
        }

        // Get the info we need from the API
        $events     = [];
        $talks      = [];
        $speakers   = [];

        if(isset($searchResults['events'])) {
            $event_mapper = new EventMapper($db, $request);
            $events = $event_mapper->getEventsByIds($searchResults['events'], false);
        }

        if(isset($searchResults['talks'])) {
            $talk_mapper = new TalkMapper($db, $request);
            $talks = $talk_mapper->getTalksByIds($searchResults['talks'], false);
        }

        if(isset($searchResults['speakers'])) {
            $linkedSpeakers = [];

            foreach($searchResults['speakers'] as $speaker) {
                if(substr($speaker, 0, 1) === 'l') {
                    $linkerSpeakers[] = $speaker;
                }
            }

            $user_mapper = new UserMapper($db, $request);
            $speakers = $user_mapper->getUsersByIds($linkedSpeakers, false);
        }

        var_dump($speakers); exit;


        $retval = [];
        $retval['results'] = $searchResults;
        $retval['meta'] = $this->getPaginationLinks($searchResults, $results['hits']['total']);

        return $retval; 
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
