<?php

class TalkSpeakerMapper extends ApiMapper
{
    public function getDefaultFields() {
        $fields = array(
            "username" => "speaker_name",
            "full_name" => "full_name",
            "twitter_username" => "twitter_username"
        );
        return $fields;
    }

    public function getVerboseFields() {
        $fields = array(
            "username" => "speaker_name",
            "full_name" => "full_name",
            "twitter_username" => "twitter_username"
        );
        return $fields;
    }

    /**
     * Get a list of ALL speakers of a talk (confirmed AND unconfirmed)
     *
     * @param      $talk_id
     * @param      $resultsperpage
     * @param      $start
     * @param bool $verbose
     */
    public function getSpeakersByTalkId($talk_id, $resultsperpage, $start, $verbose = false)
    {
        $results = $this->getUsers(
            $resultsperpage,
            $start,
            'speaker.talk_id= :talk_id',
            null,
            array('talk_id' => $talk_id)
        );
        if ($results) {
            $retval = $this->transformResults($results, $verbose);
            return $retval;
        }
        return false;
    }

    public function getSpeakerById($speaker_id, $verbose = false)
    {
        $result = $this->getUsers(
            1,
            0,
            'speaker.ID= :speaker_id',
            null,
            array(
                'speaker_id' => $speaker_id,
            )
        );

        if ($result) {
            $retVal = $this->transformResults($result, $verbose);
            return $retVal;
        }

        return false;
    }

    public function getSpeakerByTalkAndSpeakerName($talk_id, $speaker_name, $resultsperpage, $start, $verbose = false)
    {
        $result = $this->getUsers(
            $resultsperpage,
            $start,
            'speaker.talk_id= :talk_id AND speaker.speaker_name = :speaker_name',
            null,
            array(
                'talk_id' => $talk_id,
                'speaker_name' => urldecode($speaker_name),
            )
        );

        if ($result) {
            $retVal = $this->transformResults($result, $verbose);
            return $retVal;
        }

        return false;
    }

    public function transformResults($results, $verbose)
    {
        $total = $results['total'];
        unset($results['total']);
        $list = parent::transformResults($results, $verbose);

        $base = $this->_request->base;
        $version = $this->_request->version;
        if(is_array($list) && count($list)) {
            foreach($results as $key => $row) {
                // add speakers
                $list[$key]['uri'] = $base . '/' . $version . '/speakers/' . $row['speaker_id'] ;
                $list[$key]['verbose_uri'] = $base . '/' . $version . '/speakers/' . $row['speaker_id'] . '?verbose=yes';
                $list[$key]['talk_uri'] = $base . '/' . $version . '/talks/' . $row['talk_id'];
                if (isset($row['ID'])) {
                    $list[$key]['user_uri'] = $base . '/' . $version . '/users/' . $row['user_id'];
                }
            }
        }

        $retval = array();
        $retval['speakers'] = $list;
        $retval['meta'] = $this->getPaginationLinks($list, $total);

        return $retval;

    }

    /**
     * Get users for a given talk
     *
     * @param      $resultsperpage
     * @param      $start
     * @param null $where
     * @param null $order
     *
     * @return array|bool
     */
    protected function getUsers($resultsperpage, $start, $where = null, $order = null, $parameters = array())
    {
        $sql = 'select speaker.ID as speaker_id, speaker.speaker_name, speaker.talk_id, user.ID as user_id, user.email, '
               . 'user.full_name, user.twitter_username '
               . 'from talk_speaker as speaker '
               . 'left join user on (speaker.speaker_id = user.ID) '
               . 'where speaker.speaker_name <> "" ';

        // where
        if ($where) {
            $sql .= ' and ' . $where;
        }

        // group by because of adding the user_attend join
        $sql .= ' group by speaker.ID ';

        // order by
        if ($order) {
            $sql .= ' order by ' . $order;
        }

        // limit clause
        $sql .= $this->buildLimit($resultsperpage, $start);

        $stmt = $this->_db->prepare($sql);

        $response = $stmt->execute($parameters);
        if ($response) {
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $results['total'] = $stmt->rowCount();
            return $results;
        }
        return false;
    }

    /**
     * Save the given data for the first time.
     *
     * The data-array is expected to have the following keys:
     *
     * - speaker_name
     *
     * @param $data
     *
     * @return int
     */
    public function createSpeaker($data)
    {

        // TODO map from the field mappings in getVerboseFields()
        $sql = 'insert into talk_speaker (talk_id, speaker_name) '
             . 'VALUES (:talk_id, :speaker_name)';

        $stmt     = $this->_db->prepare($sql);
        $response = $stmt->execute(array(
            ':talk_id'      => $data['talk_id'],
            ':speaker_name' => $data['speaker_name'],
        ));

        $speaker_id = $this->_db->lastInsertId();

        if (0 == $speaker_id) {
            throw new Exception(
                'There has been an error storing the speaker.',
                400
            );
        }

        return $speaker_id;
    }

    /**
     * Edit the given speaker information
     *
     * The data-array is expected to contain the following keys:
     *
     * - talk_id
     * - speaker_name
     * - speaker_id
     *
     * @param array $data
     *
     * @return int
     */
    public function editSpeaker($data)
    {
        // Sanity check: ensure all mandatory fields are present.
        $mandatory_fields = array(
            'talk_id',
            'speaker_name',
        );
        $contains_mandatory_fields = !array_diff($mandatory_fields, array_keys($data));
        if (!$contains_mandatory_fields) {
            throw new Exception("Missing mandatory fields");
        }

        $sql = "UPDATE talk_speaker SET %s WHERE ID = :speaker_id";

        // get the list of column to API field name for all valid fields
        $fields = array(
            'talk_id' => 'talk_id',
            'speaker_name' => 'speaker_name',
            'speaker_id' => 'speaker_id',
        );
        $items  = array('speaker_id' => $data['speaker_id']);

        foreach ($fields as $api_name => $column_name) {
            // We don't change any activation stuff here!!
            if (in_array($column_name, ['pending'])) {
                continue;
            }
            if (isset($talk[$api_name])) {
                $pairs[] = "$column_name = :$api_name";
                $items[$api_name] = $talk[$api_name];
            }
        }

        $stmt = $this->_db->prepare(sprintf($sql, implode(', ', $pairs)));

        if (! $stmt->execute($items)) {
            throw new Exception(sprintf(
                'executing "%s" resulted in an error: %s',
                $stmt->queryString,
                implode(' :: ', $stmt->errorInfo())
            ));
            return false;
        }

        return $data['speaker_id'];
    }

    /**
     * Delete a speaker-entry
     *
     * @param int $speaker_id
     *
     * @return boolean
     */
    public function deleteSpeaker($speaker_id)
    {
        $sql = 'DELETE FROM talk_speaker WHERE ID = :speaker_id';

        $stmt = $this->_db->prepare($sql);
        $stmt->execute(array("speaker_id" => $speaker_id));

        return true;
    }


    public function getTalkIdForSpeaker($speaker_id)
    {
        $sql = 'SELECT talk_id FROM talk_speaker WHERE ID = :speaker_id';

        $stmt = $this->_db->prepare($sql);
        $result = $stmt->execute((array('speaker_id' => $speaker_id)));
        if (! $result) {
            return false;
        }

        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return $result[0]['talk_id'];
    }

    public function isSpeakerOnTalk($user_id, $talk_id)
    {
        $sql = 'SELECT * FROM talk_speaker WHERE speaker_id = :user_id AND talk_id = :talk_id';

        $stmt = $this->_db->prepare($sql);
        $result = $stmt->execute(array(
            'user_id' => $user_id,
            'talk_id' => $talk_id,
        ));

        if (count($result->fetchAll()) > 0) {
            return true;
        }

        return false;
    }

}