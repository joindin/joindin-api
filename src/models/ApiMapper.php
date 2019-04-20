<?php

class ApiMapper
{
    /**
     * Object constructor, sets up the db and some objects need request too.
     *
     * @param PDO     $db      The database connection handle
     * @param Request $request The request object (optional not all objects need it)
     */
    public function __construct(PDO $db, Request $request = null)
    {
        $this->_db = $db;
        if (isset($request)) {
            $this->_request = $request;
            $this->website_url = $request->getConfigValue('website_url');
        }

        return true;
    }

    public function getDefaultFields()
    {
        return [];
    }

    public function getVerboseFields()
    {
        return [];
    }

    /**
     * Turn results into arrays with correct fields, add hypermedia.
     *
     * @param array $results Results of the database query
     * @param bool  $verbose whether to return detailed information
     *
     * @return array A dataset now with each record having its links,
     *               and pagination if appropriate
     */
    public function transformResults(array $results, $verbose)
    {
        $fields = $verbose ? $this->getVerboseFields() : $this->getDefaultFields();
        $retval = [];

        // format results to only include named fields
        foreach ($results as $row) {
            $entry = [];
            foreach ($fields as $key => $value) {
                // special handling for dates
                if (substr($key, -5) == '_date' && !empty($row[$value])) {
                    if ($row['event_tz_place'] != '' && $row['event_tz_cont'] != '') {
                        $tz = new DateTimeZone($row['event_tz_cont'].'/'.$row['event_tz_place']);
                    } else {
                        $tz = new DateTimeZone('UTC');
                    }
                    $entry[$key] = (new DateTime('@'.$row[$value]))->setTimezone($tz)->format('c');
                } else {
                    if (array_key_exists($value, $row)) {
                        $entry[$key] = $row[$value];
                    } else {
                        $entry[$key] = null;
                    }
                }
            }
            $retval[] = $entry;
        }

        return $retval;
    }

    /**
     * @param int $resultsperpage
     * @param int $start
     *
     * @return string
     */
    protected function buildLimit($resultsperpage, $start)
    {
        if ($resultsperpage == 0) {
            // special case, no limits
            return '';
        }

        return ' LIMIT '.(int) $start.','.(int) $resultsperpage;
    }

    /**
     * get a total-results count.
     *
     * @param string $sqlQuery
     * @param array  $data
     *
     * @return int
     */
    public function getTotalCount($sqlQuery, array $data = [])
    {
        $limitPos = strrpos($sqlQuery, 'LIMIT');
        if (false !== $limitPos) {
            $sqlQuery = substr($sqlQuery, 0, $limitPos);
        }
        $countSql = sprintf(
            'SELECT count(*) AS count FROM (%s) as counter',
            $sqlQuery
        );
        $stmtCount = $this->_db->prepare($countSql);

        try {
            $stmtCount->execute($data);
        } catch (Exception $e) {
            return 0;
        }

        return $stmtCount->fetchColumn(0);
    }

    protected function getPaginationLinks(array $list, $total = 0)
    {
        $request = $this->_request;

        $meta['count'] = count($list);
        $meta['total'] = $total;
        $meta['this_page'] = $request->base.$request->path_info.'?'.
                             http_build_query($request->paginationParameters);
        $next_params = $request->paginationParameters;
        $prev_params = $request->paginationParameters;
        $counter_params = $request->paginationParameters;
        $firstOnNextPage = $counter_params['start'] +
                             $counter_params['resultsperpage'];
        $firstOnThisPage = $counter_params['start'];

        if ($firstOnNextPage < $total) {
            $next_params['start'] = $next_params['start'] + $next_params['resultsperpage'];
            $meta['next_page'] = $request->base.$request->path_info.'?'.
                                    http_build_query($next_params);
        }
        if (0 < $firstOnThisPage) {
            $prev_params['start'] = $prev_params['start'] - $prev_params['resultsperpage'];
            if ($prev_params['start'] < 0) {
                $prev_params['start'] = 0;
            }
            $meta['prev_page'] = $request->base.$request->path_info.'?'.
                                 http_build_query($prev_params);
        }

        return $meta;
    }

    protected function inflect($string)
    {
        // code ported from web2
        $alpha = preg_replace('/[^0-9a-zA-Z- ]/', '', $string);

        return strtolower(str_replace(' ', '-', $alpha));
    }
}
