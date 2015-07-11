<?php

class TalkMapper extends ApiMapper
{
    public function getDefaultFields()
    {
        $fields = array(
            'talk_title' => 'talk_title',
            'url_friendly_talk_title' => 'url_friendly_talk_title',
            'talk_description' => 'talk_desc',
            'type' => 'category',
            'start_date' => 'date_given',
            'duration' => 'duration',
            'stub' => 'stub',
            'average_rating' => 'avg_rating',
            'comments_enabled' => 'comments_enabled',
            'comment_count' => 'comment_count',
            'starred' => 'starred',
            'starred_count' => 'starred_count',
            );
        return $fields;
    }

    public function getVerboseFields()
    {
        $fields = array(
            'talk_title' => 'talk_title',
            'url_friendly_talk_title' => 'url_friendly_talk_title',
            'talk_description' => 'talk_desc',
            'type' => 'category',
            'slides_link' => 'slides_link',
            'language' => 'lang_name',
            'start_date' => 'date_given',
            'duration' => 'duration',
            'stub' => 'stub',
            'average_rating' => 'avg_rating',
            'comments_enabled' => 'comments_enabled',
            'comment_count' => 'comment_count',
            'starred' => 'starred',
            'starred_count' => 'starred_count',
            );
        return $fields;
    }

    public function getTalksByEventId($event_id, $resultsperpage, $start, $verbose = false)
    {
        $sql = $this->getBasicSQL();
        $sql .= ' and t.event_id = :event_id';
        $sql .= ' order by t.date_given';
        $sql .= $this->buildLimit($resultsperpage, $start);

        $stmt = $this->_db->prepare($sql);
        $response = $stmt->execute(array(
            ':event_id' => $event_id
            ));
        if ($response) {
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $results['total'] = $this->getTotalCount($sql, array(':event_id' => $event_id));
            $retval = $this->transformResults($results, $verbose);
            return $retval;
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

        // loop again and add links specific to this item
        if (is_array($list) && count($list)) {
            foreach ($results as $key => $row) {
                // generate and store an inflected talk title if there isn't one
                if (empty($row['url_friendly_talk_title'])) {
                    $list[$key]['url_friendly_talk_title'] = $this->generateInflectedTitle($row['talk_title'], $row['ID']);
                }

                if (isset($this->_request->user_id)) {
                    $list[$key]['starred'] = $this->hasUserStarredTalk($row['ID'], $this->_request->user_id);
                } else {
                    $list[$key]['starred'] = false;
                }

                // if the stub is empty, we need to generate one and store it
                if (empty($row['stub'])) {
                    $list[$key]['stub'] = $this->generateStub($row['ID']);
                }
                // add speakers
                $list[$key]['speakers'] = $this->getSpeakers($row['ID']);
                $list[$key]['tracks'] = $this->getTracks($row['ID']);
                $list[$key]['uri'] = $base . '/' . $version . '/talks/' . $row['ID'];
                $list[$key]['verbose_uri'] = $base . '/' . $version . '/talks/' . $row['ID'] . '?verbose=yes';
                $list[$key]['website_uri'] = 'http://joind.in/talk/view/' . $row['ID'];
                $list[$key]['comments_uri'] = $base . '/' . $version . '/talks/' . $row['ID'] . '/comments';
                $list[$key]['starred_uri'] = $base . '/' . $version . '/talks/' . $row['ID'] . '/starred';
                $list[$key]['verbose_comments_uri'] = $base . '/' . $version . '/talks/' . $row['ID'] . '/comments?verbose=yes';
                $list[$key]['event_uri'] = $base . '/' . $version . '/events/' . $row['event_id'];
            }
        }

        $retval = array();
        $retval['talks'] = $list;
        $retval['meta'] = $this->getPaginationLinks($list, $total);

        return $retval;
    }

    public function getTalkById($talk_id, $verbose = false)
    {
        $sql = $this->getBasicSQL();
        $sql .= ' and t.ID = :talk_id';
        $stmt = $this->_db->prepare($sql);
        $response = $stmt->execute(array("talk_id" => $talk_id));
        if ($response) {
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            if ($results) {
                $results['total'] = $this->getTotalCount($sql, array('talk_id' => $talk_id));
                $retval = $this->transformResults($results, $verbose);
                return $retval;
            }
        }
        return false;
    }

    /**
     * Search talks by title
     *
     * @param string $keyword
     * @param int    $resultsperpage
     * @param int    $start
     * @param bool   $verbose
     *
     * @return array|bool Result array or false on failure
     */
    public function getTalksByTitleSearch($keyword, $resultsperpage, $start, $verbose = false)
    {
        $sql = $this->getBasicSQL();
        $sql .= ' and LOWER(t.talk_title) like :title';
        $sql .= ' order by t.date_given desc';
        $sql .= $this->buildLimit($resultsperpage, $start);

        $data = array(
            ':title' => "%" . strtolower($keyword) . "%"
        );

        $stmt = $this->_db->prepare($sql);
        $response = $stmt->execute($data);

        if ($response) {
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $results['total'] = $this->getTotalCount($sql, $data);
            $retval = $this->transformResults($results, $verbose);

            return $retval;
        }

        return false;
    }

    /**
     * User attending a talk?
     *
     * @param int $talk_id the talk to check
     * @param int $user_id the user you're interested in
     * @return array
     */
    public function getUserStarred($talk_id, $user_id)
    {
        $retval = array();
        $retval['has_starred'] = $this->hasUserStarredTalk($talk_id, $user_id);

        return $retval;
    }

    /**
     * Set a user as starring a talk
     *
     * @param int $talk_id The talk ID to update
     * @param int $user_id The user's ID
     * @return bool
     */
    public function setUserStarred($talk_id, $user_id)
    {
        $sql = 'insert into user_talk_star (uid,tid) values (:uid, :tid)';
        $stmt = $this->_db->prepare($sql);
        $stmt->execute(array('uid' => $user_id, 'tid' => $talk_id));

        return true;
    }

    /**
     * Set a user as not starring a talk
     *
     * @param int $talk_id The talk ID
     * @param int $user_id The user's ID
     * @return bool
     */
    public function setUserNonStarred($talk_id, $user_id)
    {
        $sql = 'delete from user_talk_star where uid = :uid and tid = :tid';
        $stmt = $this->_db->prepare($sql);
        $stmt->execute(array('uid' => $user_id, 'tid' => $talk_id));
        // we don't mind if the delete failed; the record didn't exist and that's fine

        return true;
    }

    public function getBasicSQL()
    {
        $sql = 'select t.*, l.lang_name, e.event_tz_place, e.event_tz_cont, '
            . '(select COUNT(ID) from talk_comments tc where tc.talk_id = t.ID) as comment_count, '
            . '(select get_talk_rating(t.ID)) as avg_rating, '
            . '(select count(*) from user_talk_star where user_talk_star.tid = t.ID)
                as starred_count, '
            . 'CASE
                WHEN (((t.date_given - 3600*24) < '.mktime(0, 0, 0).') and (t.date_given + (3*30*3600*24)) > '.mktime(0, 0, 0).') THEN 1
                ELSE 0
               END as comments_enabled, '
            . 'c.cat_title as category '
            . 'from talks t '
            . 'inner join events e on e.ID = t.event_id '
            . 'inner join lang l on l.ID = t.lang '
            . 'join talk_cat tc on tc.talk_id = t.ID '
            . 'join categories c on c.ID = tc.cat_id '
            . 'where t.active = 1 and '
            . 'e.active = 1 and '
            . '(e.pending = 0 or e.pending is NULL) and '
            . '(e.private <> "y" or e.private is NULL)';
        return $sql;
    }

    protected function getSpeakers($talk_id)
    {
        $base = $this->_request->base;
        $version = $this->_request->version;

        $speaker_sql = 'select ts.*, user.full_name from talk_speaker ts '
            . 'left join user on user.ID = ts.speaker_id '
            . 'where ts.talk_id = :talk_id and ts.status IS NULL';
        $speaker_stmt = $this->_db->prepare($speaker_sql);
        $speaker_stmt->execute(array("talk_id" => $talk_id));
        $speakers = $speaker_stmt->fetchAll(PDO::FETCH_ASSOC);
        $retval = array();
        if (is_array($speakers)) {
            foreach ($speakers as $person) {
                $entry = array();
                if ($person['full_name']) {
                    $entry['speaker_name'] = $person['full_name'];
                    $entry['speaker_uri'] = $base . '/' . $version . '/users/' . $person['speaker_id'];
                } else {
                    $entry['speaker_name'] = $person['speaker_name'];
                }
                $retval[] = $entry;
            }
        }
        return $retval;
    }

    protected function getTracks($talk_id)
    {
        $base = $this->_request->base;
        $version = $this->_request->version;
        $track_sql = 'select et.ID,et.track_name '
            . 'from talk_track tt '
            . 'inner join event_track et on et.ID = tt.track_id '
            . 'where tt.talk_id = :talk_id';
        $track_stmt = $this->_db->prepare($track_sql);
        $track_stmt->execute(array("talk_id" => $talk_id));
        $tracks = $track_stmt->fetchAll(PDO::FETCH_ASSOC);
        $retval = array();
        if (is_array($tracks)) {
            foreach ($tracks as $track) {
                // Make the track_uri
               $track_uri = $base . '/' . $version . '/tracks/' . $track['ID'];
                $retval[] = array(
                   'track_name' => $track['track_name'],
                   'track_uri' => $track_uri,
               );
            }
        }
        return $retval;
    }

    public function getTalksBySpeaker($user_id, $resultsperpage, $start, $verbose = false)
    {
        // based on getBasicSQL() but needs the speaker table joins
        $sql = 'select t.*, l.lang_name, e.event_tz_place, e.event_tz_cont, '
            . '(select COUNT(ID) from talk_comments tc where tc.talk_id = t.ID) as comment_count, '
            . '(select get_talk_rating(t.ID)) as avg_rating, '
            . '(select count(*) from user_talk_star where user_talk_star.tid = t.ID)
                as starred_count, '
            . 'CASE
                WHEN (((t.date_given - 3600*24) < '.mktime(0, 0, 0).') and (t.date_given + (3*30*3600*24)) > '.mktime(0, 0, 0).') THEN 1
                ELSE 0
               END as comments_enabled '
            . 'from talks t '
            . 'inner join events e on e.ID = t.event_id '
            . 'inner join lang l on l.ID = t.lang '
            . 'left join talk_speaker ts on t.id = ts.talk_id '
            . 'where t.active = 1 and '
            . 'e.active = 1 and '
            . '(e.pending = 0 or e.pending is NULL) and '
            . '(e.private <> "y" or e.private is NULL) and '
            . 'ts.speaker_id = :user_id '
            . 'order by t.date_given desc';
        $sql .= $this->buildLimit($resultsperpage, $start);

        $stmt = $this->_db->prepare($sql);
        $response = $stmt->execute(array(
            ':user_id' => $user_id
            ));
        if ($response) {
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $results['total'] = $this->getTotalCount($sql, array(':user_id' => $user_id));
            $retval = $this->transformResults($results, $verbose);
            return $retval;
        }
        return false;
    }


    public function save($data)
    {
        // TODO map from the field mappings in getVerboseFields()
        $sql = 'insert into talks (event_id, talk_title, talk_desc, '
            . 'lang, date_given) '
            . 'values (:event_id, :talk_title, :talk_description, '
            . '(select ID from lang where lang_name = :language), '
            . ':date)';

        $stmt = $this->_db->prepare($sql);
        $response = $stmt->execute(array(
            ':event_id' => $data['event_id'],
            ':talk_title' => $data['title'],
            ':talk_description' => $data['description'],
            ':language' => $data['language'],
            ':date' => $data['date']
        ));
        $talk_id = $this->_db->lastInsertId();

        // set talk type
        // TODO support more than just talks
        $cat_sql = 'insert into talk_cat (talk_id, cat_id) values (:talk_id, 1)';
        $cat_stmt = $this->_db->prepare($cat_sql);
        $cat_stmt->execute(array(':talk_id' => $talk_id));

        // save speakers
        if (isset($data['speakers']) && is_array($data['speakers'])) {
            foreach ($data['speakers'] as $speaker) {
                $speaker_sql = 'insert into talk_speaker (talk_id, speaker_name) values '
                    . '(:talk_id, :speaker)';
                $speaker_stmt = $this->_db->prepare($speaker_sql);
                $speaker_stmt->execute(array(
                    ':talk_id' => $talk_id,
                    ':speaker' => $speaker
                ));
            }
        }

        return $talk_id;
    }

    /**
     * Is this user attending this talk?
     *
     * @param int $talk_id the talk of interest
     * @param int $user_id which user (often the current one)
     * @return bool
     */
    protected function hasUserStarredTalk($talk_id, $user_id)
    {
        $sql = "select * from user_talk_star where tid = :talk_id and uid = :user_id";
        $stmt = $this->_db->prepare($sql);
        $stmt->execute(array("talk_id" => $talk_id, "user_id" => $user_id));
        $result = $stmt->fetch();

        return is_array($result);
    }

    /**
     * Is this user a speaker?
     *
     * @param int $talk_id the talk of interest
     * @param int $user_id which user
     * @return bool
     */
    public function isUserASpeakerOnTalk($talk_id, $user_id)
    {
        $sql = "select ID from talk_speaker where talk_id = :talk_id and speaker_id = :user_id";
        $stmt = $this->_db->prepare($sql);
        $stmt->execute(array("talk_id" => (int)$talk_id, "user_id" => (int)$user_id));

        if ($stmt->fetch()) {
            return true;
        }
        return false;
    }

    /**
     * This talk has no stub, so create, store and return one
     *
     * @param int $talk_id The talk that needs a new stub
     * @return string
     */
    protected function generateStub($talk_id)
    {
        $i = 0;
        while ($i < 5) {
            $stub = substr(md5(mt_rand()), 3, 5);
            $stored = $this->storeStub($stub, $talk_id);
            if ($stored) {
                // only return a value if we actually stored one
                $stored_stub = $stub;
                break;
            }
            $i++;
        }

        return $stored_stub;
    }

    /**
     * Store the stub and return whether that was successful.
     * If not, we probably failed the unique check and the calling code can
     * decide what to do next
     *
     * @param string $stub    The stub for this talk
     * @param int    $talk_id The talk to store against
     * @return boolean whether we stored it or not
     */
    protected function storeStub($stub, $talk_id)
    {
        $sql = "update talks set stub = :stub 
            where ID = :talk_id";

        $stmt = $this->_db->prepare($sql);
        $result = $stmt->execute(array("stub" => $stub, "talk_id" => $talk_id));
        return $result;
    }

    /**
     * This talk doesn't have an inflected title, make and store one. Try to
     * ensure uniqueness, by adding hour and then hour-minute to the inflected
     * title.
     *
     * @param string $title   The talk title
     * @param int    $talk_id The talk to store the title against
     * @return string The value we stored
     */
    protected function generateInflectedTitle($title, $talk_id)
    {
        $inflected_title = $this->inflect($title);
        $result = $this->storeInflectedTitle($inflected_title, $talk_id);
        if ($result) {
            return $inflected_title;
        }
        
        // Add a number to the
        $inflected_title .= '-1';
        for ($i=2; $i <=5; $i++) {
            $inflected_title = preg_replace('/-(\d)$/', "-$i", $inflected_title);
            $result = $this->storeInflectedTitle($inflected_title, $talk_id);
            if ($result) {
                return $inflected_title;
            }
        }

        return false;
    }

    /**
     * Try to store the inflected title against this talk
     *
     * A database constraint ensures uniqueness for url_friendly_talk_title
     * values per event, so you can have the same inflected talk title at two 
     * different events, but try to have the same one at the same event and this
     * update will fail.  The calling code catches this and picks a new title
     */
    protected function storeInflectedTitle($inflected_title, $talk_id)
    {
        $sql = "update talks set url_friendly_talk_title = :inflected_title
            where ID = :talk_id";

        $stmt   = $this->_db->prepare($sql);
        $result = $stmt->execute(array(
            "inflected_title" => $inflected_title,
            "talk_id" => $talk_id
        ));

        return $result;
    }

    public function getSpeakerEmailsByTalkId($talk_id)
    {
        $speaker_sql = 'select user.email from talk_speaker ts '
            . 'left join user on user.ID = ts.speaker_id '
            . 'where ts.talk_id = :talk_id and ts.status IS NULL '
            . 'and email IS NOT null';
        $speaker_stmt = $this->_db->prepare($speaker_sql);
        $speaker_stmt->execute(array("talk_id" => $talk_id));
        $speakers = $speaker_stmt->fetchAll(PDO::FETCH_ASSOC);
        return $speakers;
    }

    /**
     * Does the currently-authenticated user have rights on a particular talk?
     *
     * @param int $talk_id The identifier for the talk to check
     * @return bool True if the user has privileges, false otherwise
     */
    public function thisUserHasAdminOn($talk_id)
    {
        // do we even have an authenticated user?
        if (isset($this->_request->user_id)) {
            $user_mapper = new UserMapper($this->_db, $this->_request);

            // is user site admin?
            $is_site_admin = $user_mapper->isSiteAdmin($this->_request->user_id);
            if ($is_site_admin) {
                return true;
            }

            // is user an event admin?
            $sql = 'select a.uid as user_id, u.full_name'
                . ' from user_admin a '
                . ' inner join user u on u.ID = a.uid '
                . ' inner join talks t on t.event_id = rid '
                . ' where rtype="event" and rcode!="pending"'
                . ' AND u.ID = :user_id'
                . ' AND t.ID = :talk_id';
            $stmt = $this->_db->prepare($sql);
            $stmt->execute(array("talk_id" => $talk_id,
                "user_id" => $this->_request->user_id));
            $results = $stmt->fetchAll();
            if ($results) {
                return true;
            }
        }
        return false;
    }

    public function delete($talk_id)
    {
        $sql = "delete from talks where ID = :talk_id";
        $stmt = $this->_db->prepare($sql);
        $stmt->execute(array("talk_id" => $talk_id));
        return true;
    }
}
