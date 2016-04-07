<?php

/**
 * SearchModel 
 * 
 * @uses ApiModel
 * @package API
 */
class SearchMapper extends ApiMapper 
{
    // Search index definitions
    const SEARCH_EVENTS    = 'events';
    const SEARCH_TALKS     = 'talks';
    const SEARCH_SPEAKERS  = 'speakers';  

    /**
     * Fetch event / talk / speaker results from a search query
     * 
     * @param string $query The string to search for
     * @param integer $limit The total number of results to return from the set
     * @param integer $offset The amount to offset the result start by
     * @param array $types The types to search, defaults to all
     * 
     * @return array of results
     */
    public function getResultsByQuery($query, $limit = 20, $offset = 0, $types = [self::SEARCH_EVENTS, self::SEARCH_TALKS, self::SEARCH_SPEAKERS]) 
    {
        $search_service = new SearchService(new Elasticsearch\Client, 'ji-search');
        $search_service->setSearchTypes($types);
        $search_service->setQuery($query);

        $search_service->setLimit($limit);
        $search_service->setOffset($offset);

        $results = $search_service->search();

        $searchResults = [];

        // Process if we actually have results to transform
        if($results) {
            foreach($results['hits']['hits'] as $hit) {
                $searchResults[$hit['_type']][] = $hit['_id'];
            }

            $search = [];

            // Get the info we need from the API
            if(isset($searchResults['events'])) {
                $event_mapper = new EventMapper($this->_db, $this->_request);
                $events = $event_mapper->getEventsByIds($searchResults['events'], false);
                $search['events'] = $events['events'];
            }

            if(isset($searchResults['talks'])) {
                $talk_mapper = new TalkMapper($this->_db, $this->_request);
                $talks = $talk_mapper->getTalksByIds($searchResults['talks'], false);
                $search['talks'] = $talks['talks'];
            }

            if(isset($searchResults['speakers'])) {
                $linkedSpeakers = [];
                foreach($searchResults['speakers'] as $speaker) {
                    if(substr($speaker, 0, 1) !== 'u') {
                        $linkedSpeakers[] = $speaker;
                    }
                }

                if(!$linkedSpeakers) {
                    $search['speakers'] = [];
                } else {
                    $user_mapper = new UserMapper($this->_db, $this->_request);
                    $speakers = $user_mapper->getUsersByIds($linkedSpeakers, false);
                    $search['speakers'] = $speakers['users'];
                }
            }
            
            $return_data = $this->transformResults($results, $search);
        } else {
            $return_data = ['results' => [], 'meta' => $this->getPaginationLinks([], 0)];
        }


        return $return_data;
    }

    /**
     * Turn results into arrays with relevant data attached to related IDs
     * 
     * @param array $search The response search
     * @param array $data The data to associate with the response search
     * @param boolean $verbose whether to return detailed information
     * @return array Search return data
     */
    public function transformResults($search, $data) 
    {
        $total = $search['hits']['total'];

        $base = $this->_request->base;
        $version = $this->_request->version;

        $transposition = [];
        foreach($search['hits']['hits'] as $hit) {
            if(array_key_exists($hit['_type'], $data)) {
                foreach($data[$hit['_type']] as $index => $dRow) {
                    if($dRow['id'] == $hit['_id']) {
                        $dRow['search_result_type'] = $hit['_type'];
                        $transposition[] = $dRow;

                        // Each row is a one off, so reduce the search space where possible
                        unset($data[$hit['_type']][$index]);
                    }
                }
            }
        }

        $retval = array();
        $retval['results'] = $transposition;
        $retval['meta'] = $this->getPaginationLinks($data, $total);

        return $retval;
    }
}
