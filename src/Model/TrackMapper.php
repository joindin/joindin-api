<?php

namespace Joindin\Api\Model;

use Exception;
use PDO;

class TrackMapper extends ApiMapper
{
    /**
     * @return array
     */
    public function getDefaultFields()
    {
        return [
            'track_name'        => 'track_name',
            'track_description' => 'track_desc',
            'talks_count'       => 'talks_count',
        ];
    }

    /**
     * @return array
     */
    public function getVerboseFields()
    {
        return [
            'track_name'        => 'track_name',
            'track_description' => 'track_desc',
            'talks_count'       => 'talks_count',
        ];
    }

    /**
     * @param int  $event_id
     * @param int  $resultsperpage
     * @param int  $start
     * @param bool $verbose
     *
     * @return false|array
     */
    public function getTracksByEventId($event_id, $resultsperpage, $start, $verbose = false)
    {
        $sql = $this->getBasicSQL();
        $sql .= ' where t.event_id = :event_id';
        $sql .= ' order by t.track_name';
        $sql .= $this->buildLimit($resultsperpage, $start);

        $stmt     = $this->_db->prepare($sql);
        $response = $stmt->execute([
            ':event_id' => $event_id
        ]);

        if ($response) {
            $results          = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $results['total'] = $this->getTotalCount($sql, [':event_id' => $event_id]);

            return $this->transformResults($results, $verbose);
        }

        return false;
    }

    /**
     * @inheritdoc
     */
    public function transformResults(array $results, $verbose)
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

        return [
            'tracks' => $list,
            'meta'   => $this->getPaginationLinks($list, $total),
        ];
    }

    /**
     * @param int  $track_id
     * @param bool $verbose
     *
     * @return false|array
     */
    public function getTrackById($track_id, $verbose = false)
    {
        $sql      = $this->getBasicSQL();
        $sql      .= ' where t.ID = :track_id';
        $stmt     = $this->_db->prepare($sql);
        $response = $stmt->execute(["track_id" => $track_id]);

        if ($response) {
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if ($results) {
                $results['total'] = $this->getTotalCount($sql, ["track_id" => $track_id]);

                return $this->transformResults($results, $verbose);
            }
        }

        return false;
    }

    /**
     * @param array $data
     * @param int   $event_id
     *
     * @throws Exception
     * @return string
     */
    public function createEventTrack(array $data, $event_id)
    {
        // Sanity check: ensure all mandatory fields are present.;
        $contains_mandatory_fields = ! array_diff($mandatory_fields = ['track_name'], array_keys($data));

        if (!$contains_mandatory_fields) {
            throw new Exception("Missing mandatory fields");
        }

        // get the list of column to API field name for all valid fields
        $fields = $this->getVerboseFields();

        foreach ($fields as $api_name => $column_name) {
            // Ignore calculated fields
            if (in_array($column_name, ['talks_count'])) {
                continue;
            }

            if (array_key_exists($api_name, $data)) {
                $column_names[]         = $column_name;
                $placeholders[]         = ':' . $api_name;
                $data_values[$api_name] = $data[$api_name];
            }
        }

        // we also need to store the event_id
        $column_names[]          = 'event_id';
        $placeholders[]          = ':event_id';
        $data_values['event_id'] = $event_id;

        // insert row
        $sql  = 'insert into event_track (' . implode(', ', $column_names) . ') ';
        $sql  .= 'values (' . implode(', ', $placeholders) . ')';
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

        return $this->_db->lastInsertId();
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

    /**
     * Edit event track
     *
     * @param  array $data
     * @param  int   $track_id
     *
     * @throws Exception
     * @return int
     */
    public function editEventTrack(array $data, $track_id)
    {
        // Sanity check: ensure all mandatory fields are present.
        $contains_mandatory_fields = ! array_diff($mandatory_fields = ['track_name'], array_keys($data));

        if (!$contains_mandatory_fields) {
            throw new Exception("Missing mandatory fields");
        }

        $sql = "UPDATE event_track SET %s WHERE ID = :track_id";

        // get the list of column to API field name for all valid fields
        $fields = $this->getVerboseFields();
        $items  = [];
        $pairs  = [];

        foreach ($fields as $api_name => $column_name) {
            // We don't change any activation stuff here!!
            if (in_array($column_name, ['pending', 'active'])) {
                continue;
            }

            if (array_key_exists($api_name, $data)) {
                $pairs[]          = "$column_name = :$api_name";
                $items[$api_name] = $data[$api_name];
            }
        }

        $items['track_id'] = $track_id;

        $stmt = $this->_db->prepare(sprintf($sql, implode(', ', $pairs)));

        try {
            $stmt->execute($items);
        } catch (Exception $e) {
            throw new Exception(sprintf(
                'executing "%s" resulted in an error: %s',
                $stmt->queryString,
                implode(' :: ', $stmt->errorInfo())
            ));
        }

        return $track_id;
    }

    /**
     * Delete track and talk associations
     *
     * @param  int $track_id
     */
    public function deleteEventTrack($track_id)
    {
        // delete talk associations
        $sql  = "delete from event_track where ID = :track_id";
        $stmt = $this->_db->prepare($sql);
        $stmt->execute(['track_id' => $track_id]);

        // delete track
        $sql  = "delete from talk_track where track_id = :track_id";
        $stmt = $this->_db->prepare($sql);
        $stmt->execute(['track_id' => $track_id]);
    }
}
