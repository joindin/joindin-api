<?php
class SearchService
{
    protected $provider;

    protected $index;
    protected $searchTypes = [];

    protected $search;

    protected $limit = 10;
    protected $offset = 0;

    /**
     * Set up the basics
     * 
     * @param object The ElasticSearch SDK object. This can be updated to use an interface later if more providers are added
     * @param string $index The Index we want ES to look in for search
     */
    public function __construct(Elasticsearch\Client $provider, $index) {
        $this->provider = $provider;
        $this->index = $index;
    }

    /**
     * Basic search data setters
     */

    public function setSearchTypes($types) {
        if(!is_array($types)) {
            $types = [$types];
        }

        $this->searchTypes = $types;
    }

    public function setQuery($search) {
        $this->search = $search;
    }

    public function setLimit($count) {
        $this->limit = (int) $count;
    }

    public function setOffset($offset) {
        $this->offset = (int) $offset;
    }

    public function search() {
        // Check we have all required data
        if(!$this->searchTypes || !$this->search || !$this->limit || !$this->offset) {
            throw new Exception('Missing search data', 400); 
        }

        $params = [
            'index' => $this->index,
            'type'  => implode(',', $this->searchTypes),
            'body'  => [
                'size'  => $this->limit,
                'from'  => $this->offset,
                'query' => [
                    'function_score' => [
                        'query'         => [
                            'multi_match' => [
                                'query' => $this->search,
                                'type'  => 'best_fields',
                                'fields'=> [
                                    'title^3', 'description^2', 'location', 'name^3', 'speaker^3'
                                ],
                                'tie_breaker' => 1,
                                'minimum_should_match' => '50%'
                            ] 
                        ]
                    ]
                ]
            ]
        ];

        $params['body']['query']['function_score']['functions'] = [
                        [
                            'gauss'     => [
                                'start'     => [ 
                                    'origin'    => time(),
                                    'scale'     => 2592000,
                                    'offset'    => 604800,
                                    'decay'     => 0.5
                                ]
                            ]
                        ]
                    ];
        $params['body']['query']['function_score']['boost'] = 1;
        $params['body']['query']['function_score']['score_mode'] = 'multiply';
        $params['body']['query']['function_score']['boost_mode'] = 'multiply';

        $results = $this->provider->search($params);

        return $results;
    }

    public function write($index, $data) {
        switch($index) {
            case 'events': 
                // Check we have all required fields
                if(count(array_diff(['name', 'location', 'description', 'start_date', 'id'], array_keys($data)))) { 
                    throw new Exception('Missing required fields');
                }

                $params = [];
                $params['body']  = [
                    'title' => $data['name'],
                    'location' => $data['location'],
                    'description' => $data['description'],
                    'stub' => (isset($data['stub']) ? $data['stub'] : ''),
                    'hashtag' => (isset($data['hashtag']) ? $data['hashtag'] : ''),
                    'start' => $data['start_date'],
                    'image' => (isset($data['icon']) ? $data['icon'] : '')
                ];
                $params['type']  = 'events';
                $params['id']    = $data['id'];

                break;
            case 'talks':
                // Check we have all required fields
                if(count(array_diff(['title', 'description', 'id'], array_keys($data)))) { 
                    throw new Exception('Missing required fields');
                }

                $params = [];
                $params['body'] = [
                    'title' => $data['title'],
                    'description' => $data['description'],
                    'speaker' => ((isset($data['speakers']) && is_array($data['speakers'])) ? implode(',', $data['speakers']) : ''),
                    'start' => isset($data['start_date']) && $data['start_date'] ? $data['start_date'] : null 
                ];

                $params['type'] = 'talks';
                $params['id'] = $data['id'];

                break;
            case 'speakers':
                // Check we have all required fields
                if(count(array_diff(['speaker_name', 'speaker_id'], array_keys($data)))) { 
                    throw new Exception('Missing required fields');
                }

                $params = [];
                $params['body'] = [
                    'name' => $row['speaker_name'],
                    'start' => null
                ];
                $params['type'] = 'speakers';
                $params['id'] = $row['speaker_id'];

                break;

            default:
                throw new Exception('Unknown index');
        }

        $params['index'] = 'ji-search';

        $ret = $this->provider->index($params);

        if(is_array($ret) && $ret['created'] === true) { 
            return true;
        } else {
            return false;
        }
    }

    /**
     * Remove document from the search index in the provided type
     * 
     * @param string $type Define the index's type we want to remove from
     * @param int $id The ID of the document we want to remove
     *
     * @return bool
     */
    public function remove($type, $id) {
        if(!in_array($type, ['events', 'talks', 'speakers'])) {
            throw new Exception('Unknown index type');
        }

        if(!ctype_digit((string)$id)) {
            throw new Exception('Invalid ID');
        }

        $params = [
            'index'     => 'ji-search',
            'type'      => $type,
            'id'        => intval($id)
        ];

        $ret = $this->provider->delete($params);

        if(is_array($ret) && $ret['found'] === true) { 
            return true;
        } else {
            return false;
        }
    }
}
