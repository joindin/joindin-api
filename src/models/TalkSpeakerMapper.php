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
     * Generate the API-Array
     *
     * @param array   $results
     * @param boolean $verbose
     *
     * @return array
     */
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
                if (isset($row['user_id'])) {
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
     * Get a list of ALL speakers of a talk (confirmed AND unconfirmed)
     *
     * @param int  $talk_id
     * @param int  $resultsperpage
     * @param int  $start
     * @param bool $verbose
     *
     * @return array|false
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

        if ($results && $results['total'] > 0) {
            $retval = $this->transformResults($results, $verbose);
            return $retval;
        }
        return false;
    }

    /**
     * Get users for a given talk
     *
     * @param int    $resultsperpage
     * @param int    $start
     * @param string $where
     * @param string $order
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

        $countStmt = $this->_db->prepare($sql);

        // limit clause
        $sql .= $this->buildLimit($resultsperpage, $start);

        $stmt = $this->_db->prepare($sql);

        $response = $stmt->execute($parameters);
        if ($response) {
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $results['total'] = $this->getTotalCount($sql, $parameters);
            return $results;
        }
        return false;
    }

    /**
     * Get informations about a speaker identified by its speaker-id
     *
     * @param int  $speaker_id The ID of the speaker to fetch
     * @param bool $verbose    Whether to return verbose information or not
     *
     * @return array|bool
     */
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
}