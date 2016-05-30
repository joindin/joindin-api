<?php

class TrackMapper extends ApiMapper
{
    public function getDefaultFields()
    {
        $fields = array(
            'track_name'        => 'track_name',
            'track_description' => 'track_desc',
            'talks_count'       => 'talks_count',
        );

        return $fields;
    }

    public function getVerboseFields()
    {
        $fields = array(
            'track_name'        => 'track_name',
            'track_description' => 'track_desc',
            'talks_count'       => 'talks_count',
        );

        return $fields;
    }

    public function getTracksByEventId($event_id, $resultsperpage, $start, $verbose = false)
    {
        $sql = $this->getBasicSQL();
        $sql .= ' where t.event_id = :event_id';
        $sql .= ' order by t.track_name';
        $sql .= $this->buildLimit($resultsperpage, $start);

        $stmt     = $this->_db->prepare($sql);
        $response = $stmt->execute(array(
            ':event_id' => $event_id
        ));
        if ($response) {
            $results          = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $results['total'] = $this->getTotalCount($sql, array(':event_id' => $event_id));
            $retval           = $this->transformResults($results, $verbose);

            return $retval;
        }

        return false;
    }

    public function transformResults($results, $verbose)
    {

        $total = $results['total'];
        unset($results['total']);
        $list    = parent::transformResults($results, $verbose);
        $base    = $this->_request->base;
        $version = $this->_request->version;

        // loop again and add links specific to this item
        if (is_array($list) && count($list)) {
            foreach ($results as $key => $row) {
                $list[$key]['uri']         = $base . '/' . $version . '/tracks/' . $row['ID'];
                $list[$key]['verbose_uri'] = $base . '/' . $version . '/tracks/' . $row['ID'] . '?verbose=yes';
                $list[$key]['event_uri']   = $base . '/' . $version . '/events/' . $row['event_id'];
            }
        }

        $retval           = array();
        $retval['tracks'] = $list;
        $retval['meta']   = $this->getPaginationLinks($list, $total);

        return $retval;
    }

    public function getTrackById($track_id, $verbose = false)
    {
        $sql = $this->getBasicSQL();
        $sql .= ' where t.ID = :track_id';
        $stmt     = $this->_db->prepare($sql);
        $response = $stmt->execute(array("track_id" => $track_id));
        if ($response) {
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            if ($results) {
                $results['total'] = $this->getTotalCount($sql, array("track_id" => $track_id));
                $retval           = $this->transformResults($results, $verbose);

                return $retval;
            }
        }

        return false;
    }

    public function createEventTrack($data, $event_id)
    {
        // Sanity check: ensure all mandatory fields are present.
        $mandatory_fields = [
            'track_name',
            'track_description',
        ];
        $contains_mandatory_fields = !array_diff($mandatory_fields, array_keys($data));
        if (!$contains_mandatory_fields) {
            throw new Exception("Missing mandatory fields");
        }

        // get the list of column to API field name for all valid fields
        $fields = $this->getVerboseFields();
        $items  = array();
        foreach ($fields as $api_name => $column_name) {
            // Ignore calculated fields
            if (in_array($column_name, ['talks_count'])) {
                continue;
            }
            if (array_key_exists($api_name, $data)) {
                $column_names[] = $column_name;
                $placeholders[] = ':' . $api_name;
                $data_values[$api_name] = $data[$api_name];
            }
        }

        // we also need to store the event_id
        $column_names[] = 'event_id';
        $placeholders[] = ':event_id';
        $data_values['event_id'] = $event_id;

        // insert row
        $sql = 'insert into event_track (' . implode(', ', $column_names) . ') ';
        $sql .= 'values (' . implode(', ', $placeholders) . ')';
        $stmt = $this->_db->prepare($sql);
        try {
            $stmt->execute($data_values);
        } catch (Exception $e) {
            throw new Exception(sprintf(
                "executing '%s' resulted in an error: %s.",
                $stmt->queryString,
                $e->getMessage()
            ));
        }

        $track_id = $this->_db->lastInsertId();
        return $track_id;
    }

    public function getBasicSQL()
    {
        $sql = 'select t.*, '
               . '(select COUNT(tk.ID) from talks tk '
               . 'inner join talk_track ttk ON tk.ID = ttk.talk_id '
               . 'WHERE ttk.track_id = t.ID and tk.active = 1) as talks_count '
               . 'from event_track t';

        return $sql;
    }
}
