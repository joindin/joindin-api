<?php

class TalkSpeakerMapper extends ApiMapper
{
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
        if ($results) {
            $retval = $this->transformResults($results, $verbose);
            return $retval;
        }
        return false;
    }
}