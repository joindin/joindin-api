<?php

abstract class BaseApiController
{
    /** @var array */
    protected $config;

    public function __construct(array $config = [])
    {
        $this->config = $config;
    }

    public function getItemId(Request $request)
    {
        // item ID
        if (!empty($request->url_elements[3])
            && is_numeric($request->url_elements[3])
        ) {
            return (int) $request->url_elements[3];
        }

        return false;
    }

    public function getVerbosity(Request $request)
    {
        if (!isset($request->parameters['verbose'])) {
            return false;
        }

        if ($request->parameters['verbose'] !== 'yes') {
            return false;
        }

        return true;
    }

    public function getStart(Request $request)
    {
        return $request->paginationParameters['start'];
    }

    public function getResultsPerPage(Request $request)
    {
        return (int) $request->paginationParameters['resultsperpage'];
    }

    public function getSort(Request $request)
    {
        // unfiltered, you probably want to switch case this
        return $this->getRequestParameter($request, 'sort');
    }

    protected function getRequestParameter(Request $request, $parameter, $default = false)
    {
        if (isset($request->parameters[$parameter])) {
            return $request->parameters[$parameter];
        }

        return $default;
    }
}
