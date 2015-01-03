<?php

abstract class ApiController {
    protected $config;

    /**
     * Handles a Request to this controller
     *
     * @param Request $request  The Request to respond to
     * @param mixed $db         The PDO object
     *
     * @return mixed        The response to the handled Request. This is passed
     *                      from the Router to a View object for rendering.
     *
     * @throws Exception    If a Request cannot be handled, an Exception is
     *                      thrown to respond accordingly. The Exception should
     *                      carry the HTTP status code as appropriate
     */
	abstract public function handle(Request $request, $db);

    public function __construct($config = null) {
        $this->config = $config;
    }

    public function getItemId($request) {
        // item ID
		if(!empty($request->url_elements[3]) && is_numeric($request->url_elements[3])) {
            $item_id = (int)$request->url_elements[3];
            return $item_id;
		}
        return false;
    }

    public function getVerbosity($request) {
        // verbosity
        if(isset($request->parameters['verbose'])
                && $request->parameters['verbose'] == 'yes') {
            $verbose = true;
        } else {
            $verbose = false;
        }
        return $verbose;
    }

    public function getStart($request) {
        return (int)$request->paginationParameters['start'];
         
    }
    
    public function getResultsPerPage($request) {
        return (int)$request->paginationParameters['resultsperpage'];
    }

    public function getSort($request) {
        // unfiltered, you probably want to switch case this
        if(isset($request->parameters['sort'])) {
            return $request->parameters['sort'];
        } else {
            return false;
        }
    }
    
}
