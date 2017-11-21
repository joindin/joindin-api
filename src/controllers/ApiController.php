<?php

abstract class ApiController
{
    protected $config;

    public function __construct($config = null)
    {
        $this->config = $config;
    }

    public function getItemId($request)
    {
        // item ID
        if (! empty($request->url_elements[3])
            && is_numeric($request->url_elements[3])
        ) {
            return (int) $request->url_elements[3];
        }

        return false;
    }

    public function getVerbosity($request)
    {
        if (! isset($request->parameters['verbose'])) {
            return false;
        }

        if ($request->parameters['verbose'] !== 'yes') {
            return false;
        }

        return true;
    }

    public function getStart($request)
    {
        return $request->paginationParameters['start'];

    }

    public function getResultsPerPage($request)
    {
        return (int) $request->paginationParameters['resultsperpage'];
    }

    public function getSort($request)
    {
        // unfiltered, you probably want to switch case this
        return $this->getRequestParameter($request, 'sort');
    }

    protected function getRequestParameter($request, $parameter, $default = false)
    {
        if (isset($request->parameters[$parameter])) {
            return $request->parameters[$parameter];
        }

        return $default;
    }
}
