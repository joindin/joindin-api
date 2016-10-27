<?php

class EventHostMapper extends ApiMapper
{
    public function getDefaultFields()
    {
        $fields = array(
            'host_name' => 'host_name',
            'host_uri'  => 'host_uri',
        );

        return $fields;
    }

    public function getVerboseFields()
    {
        return $this->getDefaultFields();
    }

    public function getHostsByEventId($event_id, $resultsperpage, $start, $verbose = false)
    {
        $sql = $this->getHostSql();
        $sql .= 'and event_id = :event_id order by host_name ';
        $sql .= $this->buildLimit($resultsperpage, $start);
        $stmt     = $this->_db->prepare($sql);
        $response = $stmt->execute(array(
            ':event_id' => $event_id
        ));
        if (! $response) {
            return false;
        }

        $results          = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $results['total'] = $this->getTotalCount($sql, array(':event_id' => $event_id));
        $retval           = $this->transformResults($results, $verbose);

        return $retval;
    }

    /**
     * SQL for fetching event hosts, so it can be used in multiple places
     *
     * @return SQL to fetch hosts, containing an :event_id named parameter
     */
    protected function getHostSql()
    {
        return 'select a.uid as user_id, u.full_name as host_name '
             . 'from user_admin a '
             . 'inner join user u on u.ID = a.uid '
             . 'where rid = :event_id and rtype="event" and (rcode!="pending" OR rcode is null)';
    }

    /**
     * Turn results into arrays with correct fields, add hypermedia
     *
     * @param array $results Results of the database query
     * @param boolean $verbose whether to return detailed information
     *
     * @return array A dataset now with each record having its links,
     *     and pagination if appropriate
     */
    public function transformResults($results, $verbose)
    {
        $total = $results['total'];
        unset($results['total']);
        $list    = parent::transformResults($results, $verbose);
        $base    = $this->_request->base;
        $version = $this->_request->version;

        // add per-item links
        if (! is_array($list)) {
            return [];
        }

        if (1 > count($list)) {
            return [];
        }

        foreach ($results as $key => $row) {
            // generate and store an inflected event name if there isn't one already
            $list[$key]['host_name'] = $row['host_name'];
            $list[$key]['host_uri']  = $base . '/' . $version . '/users/' . $row['user_id'];
        }

        $retval          = [];
        $retval['hosts'] = $list;
        $retval['meta']  = $this->getPaginationLinks($list, $total);

        return $retval;
    }
}
