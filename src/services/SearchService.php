<?php
class SearchService
{
    protected $provider;

    protected $index;
    protected $searchTypes = [];

    protected $search;

    protected $limit = 10;
    protected $offset = 0;

    public function __construct(Elasticsearch\Client $provider, $index) {
        $this->provider = $provider;
        $this->index = $index;
    }

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
        $params = [
            'index' => $this->index,
            'type'  => implode($this->searchTypes, ','),
            'body'  => [
                'size'  => $this->limit,
                'from'  => $this->offset,
                'query' => [
                    'multi_match' => [
                        'query' => $this->search,
                        'type'  => 'best_fields',
                        'fields'=> [
                            'title^3', 'name^4', 'description^2', 'location'
                        ],
                        'tie_breaker' => 0.3,
                        'minimum_should_match' => '20%'
                    ] 
                ]
            ]
        ];
        $results = $this->provider->search($params);

        return $results;
    }
}
