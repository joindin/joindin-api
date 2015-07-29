<?php

/**
 * Container for multiple TwitterRequestTokenModel objects, also handles
 * collection metadata such as pagination
 */

class TwitterRequestTokenModelCollection {
    protected $list = array();
    protected $total;

    /**
     * Take arrays of data and create a collection of models; store metadata
     */
    public function __construct($data, $total = 0) {
        $this->total = $total;

        // hydrate the model objects
        foreach($data as $item) {
            $this->list[] = new TwitterRequestTokenModel($item);
        }
    }

    /**
     * Present this collection ready for the output handlers
     *
     * This creates the expected output structure, converting each resource
     * to it's presentable representation and adding the meta fields for totals
     * and pagination
     */
    public function getOutputView($request, $verbose = false) {
        // handle the collection first
        $retval = array();
        $retval['twitter_request_tokens'] = array();
        foreach($this->list as $item) {
            $retval['twitter_request_tokens'][] = $item->getOutputView($request, $verbose);
        }

        // add other fields
        $retval['meta'] = $this->addPaginationLinks($request);

        return $retval;
    }

    /**
     * Direct port of the function in ApiMapper
     *
     * Adds count, total, and this_page links.  Also adds next_page and prev_page 
     * as appropriate
     */
    protected function addPaginationLinks($request)
    {
        $count = count($this->list);
        $meta['count'] = $count;

        $meta['total'] = $this->total;
        $meta['this_page'] = $request->base . $request->path_info .'?' . http_build_query($request->paginationParameters);
        $next_params = $prev_params = $counter_params = $request->paginationParameters;
        $firstOnNextPage = $counter_params['start'] + $counter_params['resultsperpage'];
        $firstOnThisPage = $counter_params['start'];

        if ($firstOnNextPage < $this->total) {
            $next_params['start'] = $next_params['start'] + $next_params['resultsperpage'];
            $meta['next_page']    = $request->base . $request->path_info . '?' . http_build_query($next_params);
        }
        if (0 < $firstOnThisPage) {
            $prev_params['start'] = $prev_params['start'] - $prev_params['resultsperpage'];
            if ($prev_params['start'] < 0) $prev_params['start'] = 0;
            $meta['prev_page'] = $request->base . $request->path_info . '?' . http_build_query($prev_params);
        }
        return $meta;
    }

}
