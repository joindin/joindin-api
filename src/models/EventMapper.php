<?php

/**
 * EventModel 
 * 
 * @uses ApiModel
 * @package API
 */
class EventMapper extends ApiMapper 
{

    /**
     * Default mapping for column names to API field names
     * 
     * @return array with keys as API fields and values as db columns
     */
    public function getDefaultFields() 
    {
        $fields = array(
            'name' => 'event_name',
            'url_friendly_name' => 'url_friendly_name',
            'start_date' => 'event_start',
            'end_date' => 'event_end',
            'description' => 'event_desc',
            'stub' => 'event_stub',
            'href' => 'event_href',
            'tz_continent' => 'event_tz_cont',
            'tz_place' => 'event_tz_place',
            'attendee_count' => 'attendee_count',
            'attending' => 'attending',
            'event_comments_count' => 'comment_count',
            'tracks_count' => 'track_count',
            'talks_count' => 'talk_count',
            'icon' => 'event_icon',
            'location' => 'event_loc'
            );
        return $fields;
    }

    /**
     * Field/column name mappings for the verbose version
     *
     * This should contain everything above and then more in most cases
     * 
     * @return array with keys as API fields and values as db columns
     */
    public function getVerboseFields() 
    {
        $fields = array(
            'name' => 'event_name',
            'url_friendly_name' => 'url_friendly_name',
            'start_date' => 'event_start',
            'end_date' => 'event_end',
            'description' => 'event_desc',
            'stub' => 'event_stub',
            'href' => 'event_href',
            'icon' => 'event_icon',
            'latitude' => 'event_lat',
            'longitude' => 'event_long',
            'tz_continent' => 'event_tz_cont',
            'tz_place' => 'event_tz_place',
            'location' => 'event_loc',
            'hashtag' => 'event_hashtag',
            'attendee_count' => 'attendee_count',
            'attending' => 'attending',
            'comments_enabled' => 'comments_enabled',
            'event_comments_count' => 'comment_count',
            'tracks_count' => 'track_count',
            'talks_count' => 'talk_count',
            'cfp_start_date' => 'event_cfp_start',
            'cfp_end_date' => 'event_cfp_end',
            'cfp_url' => 'event_cfp_url'
            );
        return $fields;
    }

    /**
     * Fetch the details for a single event
     * 
     * @param int $event_id events.ID value
     * @param boolean $verbose used to determine how many fields are needed
     * 
     * @return array the event detail
     */
    public function getEventById($event_id, $verbose = false) 
    {
        $results = $this->getEvents(1, 0, array("event_id" => $event_id));
        if ($results) {
            $retval = $this->transformResults($results, $verbose);
            return $retval;
        }
        return false;
    }

    /**
     * Internal function called by other event-fetching code, with changeable SQL
     * 
     * @param int $resultsperpage how many records to return
     * @param int $start offset to start returning records from
     * @param array $params filters and other parameters to limit/order the collection
     *
     * @return array the raw database results
     */
    protected function getEvents($resultsperpage, $start, $params = array()) 
    {
        $data = array();
        $order = " order by ";
        $where = "";

        $sql = 'select events.*, '
            . '(select count(*) from user_attend where user_attend.eid = events.ID) 
                as attendee_count, '
            . 'abs(datediff(from_unixtime(events.event_start), 
                from_unixtime('.mktime(0, 0, 0).'))) as score, '
            . 'CASE 
                WHEN (((events.event_start - 3600*24) < '.mktime(0,0,0).') and (events.event_start + (3*30*3600*24)) > '.mktime(0,0,0).') THEN 1
                ELSE 0
               END as comments_enabled '
            . 'from events ';

        if(array_key_exists("tags", $params)) {
            $sql .= "left join tags_events on tags_events.event_id = events.ID 
                left join tags on tags.ID = tags_events.tag_id ";
        }

        $sql .= 'where active = 1 and '
            . '(pending = 0 or pending is NULL) and '
            . '(private <> "y" OR private IS NULL) ';

        if(array_key_exists("event_id", $params)) {
            $where .= "and events.ID = :event_id ";
            $data["event_id"] = $params["event_id"];
        }

        if(array_key_exists("filter", $params)) {
            switch($params['filter']) {
                case "hot": // current and popular events
                    $order .= "score - ((comment_count + attendee_count + 1) / 5)";
                    break;
                case "upcoming": // future events, soonest first
                    $where .= ' and (events.event_start >=' . (mktime(0, 0, 0) - (3 * 86400)) . ')';
                    $order .= 'events.event_start';
                    break;
                case "past": // past events, most recent first
                    $where .= ' and (events.event_start <' . (mktime(0, 0, 0)) . ')';
                    $order .= 'events.event_start desc';
                    break;
                case "cfp": // events with open CfPs, soonest closing first
                    $where .= ' and events.event_cfp_url IS NOT NULL AND events.event_cfp_end >= ' . mktime(0, 0, 0);
                    $order .= 'events.event_start';
                    break;
                default:
                    $order .= 'events.event_start desc';
                    break;
            }
        } else {
            // default ordering
            $order .= 'events.event_start desc ';
        }

        if(array_key_exists("stub", $params)) {
            $where .= ' and events.event_stub = :stub';
            $data['stub'] = $params['stub'];
        }

        // fuzzy/partial match for title
        if(array_key_exists("title", $params)) {
            $order .= ', events.event_start desc';
            $where .= ' and LOWER(events.event_name) like :title';
            $data['title'] = "%" . strtolower($params['title']) . "%";
        }

        // messy OR statements because we can't escape when using IN()
        if(array_key_exists("tags", $params)) {
            $where .= ' and (';

            $i = 0;
            foreach($params['tags'] as $tag) {
                if($i > 0) {
                    $where .= " OR ";
                }

                $where .= "tags.tag_value = :tag" . $i;
                $data["tag" . $i] = $tag;
                $i++;
            }

            $where .= ') ';
        }

        if(array_key_exists("startdate", $params)) {
            $where .= ' and events.event_start >= :startdate ';
            $data["startdate"] = $params["startdate"];
        }

        if(array_key_exists("enddate", $params)) {
            $where .= ' and events.event_start < :enddate ';
            $data["enddate"] = $params["enddate"];
        }

        // now add all that where clause
        $sql .= $where;

        // group by if we joined additional tables
        if(array_key_exists("tags", $params)) {
            $sql .= " group by events.ID ";
        }

        // add the ordering instruction
        $sql .= $order;

        // limit clause
        $sql .= $this->buildLimit($resultsperpage, $start);

        $stmt = $this->_db->prepare($sql);
        $response = $stmt->execute($data);
        if ($response) {
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $results['total'] = $this->getTotalCount($sql, $data);
            return $results;
        }
        return false;
    }

    /**
     * getEventList 
     * 
     * @param int $resultsperpage how many records to return
     * @param int $start offset to start returning records from
     * @param array $params filters and other parameters to limit/order the collection
     * @param boolean $verbose used to determine how many fields are needed
     * 
     * @return array the data, or false if something went wrong
     */
    public function getEventList($resultsperpage, $start, $params, $verbose = false) 
    {
        $results = $this->getEvents($resultsperpage, $start, $params);
        if (is_array($results)) {
            $retval = $this->transformResults($results, $verbose);
            return $retval;
        }
        return false;
    }

    /**
     * Set a user as attending for an event
     *
     * @param int $event_id The event ID to update for
     * @param int $user_id The user's ID
     */
    public function setUserAttendance($event_id, $user_id)
    {
        $sql = 'insert into user_attend (uid,eid) values (:uid, :eid)';
        $stmt = $this->_db->prepare($sql);
        $stmt->execute(array('uid' => $user_id, 'eid' => $event_id));
        return true;
    }

    /**
     * Set a user as not attending an event
     *
     * @param int $event_id The event ID 
     * @param int $user_id The user's ID
     */
    public function setUserNonAttendance($event_id, $user_id)
    {
        $sql = 'delete from user_attend where uid = :uid and eid = :eid';
        $stmt = $this->_db->prepare($sql);
        $stmt->execute(array('uid' => $user_id, 'eid' => $event_id));
        // we don't mind if the delete failed; the record didn't exist and that's fine
        return true;
    }

    /**
     * User attending an event?
     * 
     * @param int $event_id the event to check
     * @param int $user_id the user you're interested in
     */

    public function getUserAttendance($event_id, $user_id)
    {
        $retval = array();
        $retval['is_attending'] = $this->isUserAttendingEvent($event_id, $user_id);
        return $retval;
    }

    /**
     * Is this user attending this event?
     *
     * @param int $event_id the Event of interest
     * @param int $user_id which user (often the current one)
     */

    protected function isUserAttendingEvent($event_id, $user_id)
    {
        $sql = "select * from user_attend where eid = :event_id and uid = :user_id";
        $stmt = $this->_db->prepare($sql);
        $stmt->execute(array("event_id" => $event_id, "user_id" => $user_id));
        $result = $stmt->fetch();

        if(is_array($result)) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Turn results into arrays with correct fields, add hypermedia
     * 
     * @param array $results Results of the database query
     * @param boolean $verbose whether to return detailed information
     * @return array A dataset now with each record having its links,
     *     and pagination if appropriate
     */
    public function transformResults($results, $verbose) 
    {
        $total = $results['total'];
        unset($results['total']);
        $list = parent::transformResults($results, $verbose);
        $base = $this->_request->base;
        $version = $this->_request->version;

        // add per-item links 
        if (is_array($list) && count($list)) {
            foreach ($results as $key => $row) {
                // generate and store an inflected event name if there isn't one already
                if(empty($row['url_friendly_name'])) {
                    $list[$key]['url_friendly_name'] = 
                        $this->generateInflectedName($row['event_name'], $row['ID'], $list[$key]['start_date']);
                }

                // if the user is logged in, get their attending data
                if(isset($this->_request->user_id)) {
                    $list[$key]['attending'] = $this->isUserAttendingEvent($row['ID'], $this->_request->user_id);
                } else {
                    $list[$key]['attending'] = false;
                }

                if($verbose) {
                    $list[$key]['talk_comments_count'] = $this->getTalkCommentCount($row['ID']);
                }
                $list[$key]['tags'] = $this->getTags($row['ID']);;
                $list[$key]['uri'] = $base . '/' . $version . '/events/' 
                    . $row['ID'];
                $list[$key]['verbose_uri'] = $base . '/' . $version . '/events/' 
                    . $row['ID'] . '?verbose=yes';
                $list[$key]['comments_uri'] = $base . '/' . $version . '/events/' 
                    . $row['ID'] . '/comments';
                $list[$key]['talks_uri'] = $base . '/' . $version . '/events/' 
                . $row['ID'] . '/talks';
                $list[$key]['tracks_uri'] = $base . '/' . $version . '/events/'
                    . $row['ID'] . '/tracks';
                $list[$key]['attending_uri'] = $base . '/' . $version . '/events/'
                    . $row['ID'] . '/attending';
                $list[$key]['website_uri'] = 'http://joind.in/event/view/' . $row['ID'];
                // handle the slug
                if(!empty($row['event_stub'])) {
                    $list[$key]['humane_website_uri'] = 'http://joind.in/event/' . $row['event_stub'];    
                }

                if($verbose) {
                    $list[$key]['all_talk_comments_uri'] = $base . '/' . $version . '/events/' 
                        . $row['ID'] . '/talk_comments';
                    $list[$key]['hosts'] = $this->getHosts($row['ID']);
                }
                $list[$key]['attendees_uri'] = $base . '/' . $version . '/events/' 
                    . $row['ID'] . '/attendees';
            }
        }
        $retval = array();
        $retval['events'] = $list;
        $retval['meta'] = $this->getPaginationLinks($list, $total);

        return $retval;
    }

    /**
     * Fetch the users who are hosting this event
     * 
     * @param int $event_id 
     * @return array The list of people hosting the event 
     */
    protected function getHosts($event_id)
    {
        $base = $this->_request->base;
        $version = $this->_request->version;

        $host_sql = $this->getHostSql();
        $host_stmt = $this->_db->prepare($host_sql);
        $host_stmt->execute(array("event_id" => $event_id));
        $hosts = $host_stmt->fetchAll(PDO::FETCH_ASSOC);
        $retval = array();
        if(is_array($hosts)) {
           foreach($hosts as $person) {
               $entry = array();
               $entry['host_name'] = $person['full_name'];
               $entry['host_uri'] = $base . '/' . $version . '/users/' . $person['user_id'];
               $retval[] = $entry;
           }
        }
        return $retval;
    }

    /**
     * SQL for fetching event hosts, so it can be used in multiple places
     *
     * @return SQL to fetch hosts, containing an :event_id named parameter
     */
    protected function getHostSql() {
        $host_sql = 'select a.uid as user_id, u.full_name'
            . ' from user_admin a '
            . ' inner join user u on u.ID = a.uid '
            . ' where rid = :event_id and rtype="event" and rcode!="pending"';
        return $host_sql;
    }

    /**
     * Return an array of tags for the event
     * 
     * @param int $event_id The event whose tags we want
     * @return array An array of tags
     */
    protected function getTags($event_id)
    {
        $tag_sql = 'select tag_value as tag'
            . ' from tags_events te'
            . ' inner join tags t on t.ID = te.tag_id'
            . ' where te.event_id = :event_id';
        $tag_stmt = $this->_db->prepare($tag_sql);
        $tag_stmt->execute(array("event_id" => $event_id));
        $tags = $tag_stmt->fetchAll(PDO::FETCH_ASSOC);
        $retval = array();
        if(is_array($tags)) {
           foreach($tags as $row) {
               $retval[] = $row['tag'];
           }
        }
        return $retval;
    }

    /**
     * Events that a particular user is marked as attending
     * 
     * @param int $resultsperpage how many records to return
     * @param int $start offset to start returning records from
     * @param boolean $verbose used to determine how many fields are needed
     * 
     * @return array the data, or false if something went wrong
     */
    public function getEventsAttendedByUser($user_id, $resultsperpage, $start, $verbose = false) 
    {
        $data = array("user_id" => (int)$user_id);
        $sql = 'select events.*, '
            . '(select count(*) from user_attend where user_attend.eid = events.ID) 
                as attendee_count, '
            . 'abs(datediff(from_unixtime(events.event_start), 
                from_unixtime('.mktime(0, 0, 0).'))) as score, '
            . 'CASE 
                WHEN (((events.event_start - 3600*24) < '.mktime(0,0,0).') and (events.event_start + (3*30*3600*24)) > '.mktime(0,0,0).') THEN 1
                ELSE 0
               END as comments_enabled '
            . 'from events '
            . 'left join user_attend ua on (ua.eid = events.ID) ';

        $sql .= 'where active = 1 and '
            . '(pending = 0 or pending is NULL) and '
            . 'private <> "y" and '
            . ' ua.uid = :user_id';

        // group by for the multiple attending recipes; only ever want to see each event once
        $sql .= ' group by events.ID ';

        $sql .= ' order by events.event_start desc ';

        // limit clause
        $sql .= $this->buildLimit($resultsperpage, $start);

        $stmt = $this->_db->prepare($sql);
        $response = $stmt->execute($data);
        if ($response) {
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            if(is_array($results)) {
                $results['total'] = $this->getTotalCount($sql, $data);
                $retval = $this->transformResults($results, $verbose);
                return $retval;
            }
        }
        return false;
    }

    /**
     * Does the currently-authenticated user have rights on a particular event?
     *
     * @param int $event_id The identifier for the event to check
     * @return bool True if the user has privileges, false otherwise
     */
    public function thisUserHasAdminOn($event_id) {
        // do we even have an authenticated user?
        if(isset($this->_request->user_id)) {
            $user_mapper = new UserMapper($this->_db, $this->_request);

            // is user site admin?
            $is_site_admin = $user_mapper->isSiteAdmin($this->_request->user_id);
            if($is_site_admin) { 
                return true;
            }

            // is user an event admin?
            $sql = $this->getHostSql();
            $sql .= ' AND u.ID = :user_id';
            $stmt = $this->_db->prepare($sql);
            $stmt->execute(array("event_id" => $event_id, 
                "user_id" => $this->_request->user_id));
            $results = $stmt->fetchAll();
            if($results) {
                return true;
            }
        } 
        return false;
    }

    /**
     * Count the number of talk comments for an event
     * 
     * @param int $event_id The event to count talk comments for
     * @return int Number of comments across all talks
     */
    protected function getTalkCommentCount($event_id)
    {
        $comment_sql = 'select count(*) as comment_count from talk_comments tc '
            . 'inner join talks t on tc.talk_id = t.ID '
            . 'inner join events e on t.event_id = e.ID '
            . 'where e.ID = :event_id';
        $comment_stmt = $this->_db->prepare($comment_sql);
        $comment_stmt->execute(array("event_id" => $event_id));
        $comments = $comment_stmt->fetch(PDO::FETCH_ASSOC);
        return $comments['comment_count'];
    }


    /**
     * This event doesn't have an inflected name yet, make and store one. Try to
     * ensure uniqueness, by adding year, then year-month and then finally
     * year-month-day to the inflected title.
     *
     * @param string $name     The event name to inflect
     * @param int    $event_id The event to store it against
     * @return string The value we stored
     */
    protected function generateInflectedName($name, $event_id, $start_date)
    {
        $date = new DateTime($start_date);
        $inflected_name = $this->inflect($name);
        $name_choices = array(
            $inflected_name,
            $inflected_name . $date->format('-Y'),
            $inflected_name . $date->format('-Y-m'),
            $inflected_name . $date->format('-Y-m-d'),
        );

        foreach ($name_choices as $inflected_name) {
            $sql = "update events set url_friendly_name = :inflected_name
                where ID = :event_id";

            $stmt   = $this->_db->prepare($sql);
            $result = $stmt->execute(array(
                "inflected_name" => $inflected_name,
                "event_id" => $event_id));

            if ($result) {
                return $inflected_name;
            }
        }

        return false;
    }

    /**
     * Submit an event for approval via the API, optionally allowing 
     * callers to mark the event as automatically approved.
     *
     * Accepts a subset of event fields
     *
     * @param string[] $event        Event data to insert into the database.
     * @param boolean  $auto_approve if false an event is registered as 'pending' first and must be actively approved.
     *
     * @return integer|false
     */
    public function createEvent($event, $auto_approve = false) {
        $sql = "insert into events set event_name = :name,
            event_desc = :description, event_start = :start_date,
            event_end = :end_date, event_tz_cont = :tz_continent,
            event_tz_place = :tz_place ";

        // optional fields included if present
        $optional_fields = array('href', 'cfp_url', 'cfp_start_date', 'cfp_end_date');
        foreach ($optional_fields as $field) {
            if (isset($event[$field])) {
                $sql .= ", event_$field = :$field ";
            }
        }

        // the active opposite to pending to get it on the right web1 lists
        $sql .= ", private=0 ";
        if(!$auto_approve) {
            $sql .= ", pending = 1, active=0";
        } else {
            $sql .= ", active = 1";
        }

        $stmt   = $this->_db->prepare($sql);
        $result = $stmt->execute($event);
        if($result) {
            return $this->_db->lastInsertId();
        }

        return false;
    }

    /**
     * Add a user as an admin on an event
     */
    public function addUserAsHost($event_id, $user_id) {
        $sql = "insert into user_admin set rtype = 'event', rid = :event_id,
            uid = :user_id";
        $stmt   = $this->_db->prepare($sql);
        $result = $stmt->execute(array("event_id" => $event_id, "user_id" => $user_id));
        return $result;
    }

    /**
     * Remove a user's admin rights to a specific event
     */
    public function removeUserAsHost($event_id, $user_id) {
        $sql = "delete from user_admin where rtype = 'event' and rid = :event_id 
            and uid = :user_id";
        $stmt   = $this->_db->prepare($sql);
        $result = $stmt->execute(array("event_id" => $event_id, "user_id" => $user_id));
        return $result;
    }

    /**
     * Update the cached count of talks for a specific event
     *
     * @param $event_id
     * @return bool
     */
    public function cacheTalkCount($event_id) {
        $sql = "UPDATE events e SET talk_count = (SELECT COUNT(*) FROM talks t WHERE t.event_id = e.ID) WHERE e.ID = :event_id;";
        $stmt = $this->_db->prepare($sql);
        $result = $stmt->execute(array("event_id" => $event_id));

        return $result;
    }

    /**
     * Update the cached count of comments for a specific event
     *
     * @param $event_id
     * @return bool
     */
    public function cacheCommentCount($event_id) {
        $sql = "UPDATE events e SET comment_count = (SELECT COUNT(*) FROM event_comments ec WHERE ec.event_id = e.ID) WHERE e.ID = :event_id;";
        $stmt = $this->_db->prepare($sql);
        $result = $stmt->execute(array("event_id" => $event_id));

        return $result;
    }

    /**
     * Update the cached count of tracks for a specific event
     *
     * @param $event_id
     * @return bool
     */
    public function cacheTrackCount($event_id) {
        $sql = "UPDATE events e SET track_count = (SELECT COUNT(*) FROM event_track et WHERE et.event_id = e.ID) WHERE e.ID = :event_id;";
        $stmt = $this->_db->prepare($sql);
        $result = $stmt->execute(array("event_id" => $event_id));

        return $result;
    }
}
