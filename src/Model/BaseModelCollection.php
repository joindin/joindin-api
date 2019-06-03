<?php

namespace Joindin\Api\Model;

use Joindin\Api\Request;

abstract class BaseModelCollection
{
    protected $list = [];
    protected $total;

    /**
     * Adds count, total, and this_page links.  Also adds next_page and prev_page
     * as appropriate
     *
     * @param Request $request
     *
     * @return array
     */
    protected function addPaginationLinks(Request $request)
    {
        $meta['count'] = count($this->list);

        $meta['total']     = $this->total;
        $meta['this_page'] = $request->base . $request->path_info . '?' .
                             http_build_query($request->paginationParameters);

        $next_params = $prev_params = $counter_params = $request->paginationParameters;

        $firstOnNextPage = $counter_params['start'] + $counter_params['resultsperpage'];
        $firstOnThisPage = $counter_params['start'];

        if ($firstOnNextPage < $this->total) {
            $next_params['start'] = $next_params['start'] + $next_params['resultsperpage'];
            $meta['next_page']    = $request->base . $request->path_info . '?' . http_build_query($next_params);
        }
        if (0 < $firstOnThisPage) {
            $prev_params['start'] = $prev_params['start'] - $prev_params['resultsperpage'];
            if ($prev_params['start'] < 0) {
                $prev_params['start'] = 0;
            }
            $meta['prev_page'] = $request->base . $request->path_info . '?' . http_build_query($prev_params);
        }

        return $meta;
    }
}
