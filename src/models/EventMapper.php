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
            'name'                 => 'event_name',
            'url_friendly_name'    => 'url_friendly_name',
            'start_date'           => 'event_start',
            'end_date'             => 'event_end',
            'description'          => 'event_desc',
            'stub'                 => 'event_stub',
            'href'                 => 'event_href',
            'tz_continent'         => 'event_tz_cont',
            'tz_place'             => 'event_tz_place',
            'attendee_count'       => 'attendee_count',
            'attending'            => 'attending',
            'event_average_rating' => 'avg_rating',
            'event_comments_count' => 'comment_count',
            'tracks_count'         => 'track_count',
            'talks_count'          => 'talk_count',
            'icon'                 => 'event_icon',
            'location'             => 'event_loc',
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
            'name'                 => 'event_name',
            'url_friendly_name'    => 'url_friendly_name',
            'start_date'           => 'event_start',
            'end_date'             => 'event_end',
            'description'          => 'event_desc',
            'stub'                 => 'event_stub',
            'href'                 => 'event_href',
            'icon'                 => 'event_icon',
            'latitude'             => 'event_lat',
            'longitude'            => 'event_long',
            'tz_continent'         => 'event_tz_cont',
            'tz_place'             => 'event_tz_place',
            'location'             => 'event_loc',
            'contact_name'         => 'event_contact_name',
            'hashtag'              => 'event_hashtag',
            'attendee_count'       => 'attendee_count',
            'attending'            => 'attending',
            'event_average_rating' => 'avg_rating',
            'comments_enabled'     => 'comments_enabled',
            'event_comments_count' => 'comment_count',
            'tracks_count'         => 'track_count',
            'talks_count'          => 'talk_count',
            'cfp_start_date'       => 'event_cfp_start',
            'cfp_end_date'         => 'event_cfp_end',
            'cfp_url'              => 'event_cfp_url',
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
    public function getEventById($event_id, $verbose = false, $activeEventsOnly = true)
    {
        $results = $this->getEvents(1, 0, array("event_id" => $event_id, 'active' => $activeEventsOnly));
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
        $data  = array();
        $order = " order by ";
        $where = "";

        $sql = 'select events.*, '
               . '(select ifnull(round(avg(event_comments.rating)), 0)
                from event_comments
                where
                    event_comments.event_id = events.ID
                    and event_comments.rating > 0
                    and event_comments.user_id not in (
                        select ifnull(user_admin.uid, 0) from user_admin
                            where rid = events.ID and rtype="event" and (rcode!="pending" OR rcode is null)
                        union
                        select 0
                    )
                ) as avg_rating, '
               . '(select count(*) from user_attend where user_attend.eid = events.ID) as attendee_count, '
               . 'abs(datediff(from_unixtime(events.event_start), from_unixtime(' . mktime(0, 0, 0) . '))) as score, '
               . 'CASE WHEN (((events.event_start - 3600*24) < ' . mktime(0, 0, 0)
               . ') and (events.event_start + (3*30*3600*24)) > ' . mktime(0, 0, 0)
               . ') THEN 1 ELSE 0 END as comments_enabled '
               . 'from events ';
        if (array_key_exists("tags", $params)) {
            $sql .= "left join tags_events on tags_events.event_id = events.ID 
                left join tags on tags.ID = tags_events.tag_id ";
        }

        $sql .= ' where (events.private <> "y" OR events.private IS NULL) ';

        if (array_key_exists("event_id", $params)) {
            $where .= "and events.ID = :event_id ";
            $data["event_id"] = $params["event_id"];
        }

        $active = true;
        if (array_key_exists("active", $params)) {
            $active = $params['active'];
        }
        if (array_key_exists("filter", $params)) {
            switch ($params['filter']) {
                case "all": // current and popular events
                    $order .= 'events.event_start';
                    break;
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
                    $where .= sprintf(
                        ' AND events.event_cfp_url IS NOT NULL' .
                        ' AND events.event_cfp_end >= %1$d' .
                        ' AND events.event_cfp_start <= %2$d',
                        mktime(0, 0, 0),
                        mktime(0, 0, 0) + (7 * 86400)
                    );
                    $order .= 'events.event_cfp_end';
                    break;
                case "pending": // events to be approved
                    $order .= 'events.event_start';
                    $active = false;
                    $where .= ' and pending = 1';
                    break;
                default:
                    $order .= 'events.event_start desc';
                    break;
            }
        } else {
            // default ordering
            $order .= 'events.event_start desc ';
        }

        if ($active) {
            $where .= " and events.active = 1 and (events.pending = 0 or events.pending is NULL) ";
        }

        if (array_key_exists("stub", $params)) {
            $where .= ' and events.event_stub = :stub';
            $data['stub'] = $params['stub'];
        }

        // fuzzy/partial match for title
        if (array_key_exists("title", $params)) {
            $order .= ', events.event_start desc';
            $where .= ' and LOWER(events.event_name) like :title';
            $data['title'] = "%" . strtolower($params['title']) . "%";
        }

        // messy OR statements because we can't escape when using IN()
        if (array_key_exists("tags", $params)) {
            $where .= ' and (';

            $i = 0;
            foreach ($params['tags'] as $tag) {
                if ($i > 0) {
                    $where .= " OR ";
                }

                $where .= "tags.tag_value = :tag" . $i;
                $data[ "tag" . $i] = $tag;
                $i++;
            }

            $where .= ') ';
        }

        if (array_key_exists("startdate", $params)) {
            $where .= ' and events.event_start >= :startdate ';
            $data["startdate"] = $params["startdate"];
        }

        if (array_key_exists("enddate", $params)) {
            $where .= ' and events.event_start < :enddate ';
            $data["enddate"] = $params["enddate"];
        }

        // If the "all" filter is selected and a start parameter isn't provided, then
        // we need to calculate it such that the results returned include the first
        // upcoming event. This allows client to go backwards and forwards from here.
        if (array_key_exists("filter", $params) && $params['filter'] == 'all' && $start === null) {
            // How many events are there up to "now"?
            $this_sql = $sql . $where . ' and (events.event_start <' . (mktime(0, 0, 0)) . ')';
            $start = $this->getTotalCount($this_sql, $data);

            // store back into paginationParameters so that meta is correct
            $this->_request->paginationParameters['start'] = $start;
        }
 
        // now add all that where clause
        $sql .= $where;

        // group by if we joined additional tables
        if (array_key_exists("tags", $params)) {
            $sql .= " group by events.ID ";
        }

        // add the ordering instruction
        $sql .= $order;

        // limit clause
        $sql .= $this->buildLimit($resultsperpage, $start);
        $stmt     = $this->_db->prepare($sql);
        $response = $stmt->execute($data);
        if ($response) {
            $results          = $stmt->fetchAll(PDO::FETCH_ASSOC);
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
        $sql  = 'insert into user_attend (uid,eid) values (:uid, :eid)';
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
        $sql  = 'delete from user_attend where uid = :uid and eid = :eid';
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
        $retval                 = array();
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
        $sql  = "select * from user_attend where eid = :event_id and uid = :user_id";
        $stmt = $this->_db->prepare($sql);
        $stmt->execute(array("event_id" => $event_id, "user_id" => $user_id));
        $result = $stmt->fetch();

        if (is_array($result)) {
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
        if (is_array($list) && count($list)) {
            $thisUserCanApproveEvents = $this->thisUserCanApproveEvents();

            foreach ($results as $key => $row) {
                // generate and store an inflected event name if there isn't one already
                if (empty($row['url_friendly_name'])) {
                    $list[$key]['url_friendly_name'] =
                        $this->generateInflectedName($row['event_name'], $row['ID'], $list[$key]['start_date']);
                }

                // if the user is logged in, get their attending data
                if (isset($this->_request->user_id)) {
                    $list[$key]['attending'] = $this->isUserAttendingEvent($row['ID'], $this->_request->user_id);
                } else {
                    $list[$key]['attending'] = false;
                }

                if ($verbose) {
                    $list[$key]['talk_comments_count'] = $this->getTalkCommentCount($row['ID']);
                }
                $list[$key]['images']        = $this->getImages($row['ID']);
                $list[$key]['tags']          = $this->getTags($row['ID']);
                $list[$key]['uri']           = $base . '/' . $version . '/events/' . $row['ID'];
                $list[ $key ]['verbose_uri'] = $base . '/' . $version . '/events/' . $row['ID'] . '?verbose=yes';
                $list[$key]['comments_uri']  = $base . '/' . $version . '/events/' . $row['ID'] . '/comments';
                $list[$key]['talks_uri']     = $base . '/' . $version . '/events/' . $row['ID'] . '/talks';
                $list[ $key]['tracks_uri']   = $base . '/' . $version . '/events/' . $row['ID'] . '/tracks';
                $list[$key]['attending_uri'] = $base . '/' . $version . '/events/' . $row['ID'] . '/attending';
                $list[$key]['images_uri']    = $base . '/' . $version . '/events/' . $row['ID'] . '/images';

                if ($row['pending'] == 1 && $thisUserCanApproveEvents) {
                    $list[$key]['approval_uri'] = $base . '/' . $version . '/events/' . $row['ID'] . '/approval';
                }
                $list[$key]['website_uri'] = $this->website_url . '/event/' . $row['url_friendly_name'];
                // handle the slug
                if (!empty($row['event_stub'])) {
                    $list[$key]['humane_website_uri'] = $this->website_url . '/e/' . $row['event_stub'];
                }

                if ($verbose) {
                    if ($this->thisUserHasAdminOn($row['ID'])) {
                        $list[$key]['reported_comments_uri'] = $base . '/' . $version . '/events/'
                            . $row['ID'] . '/comments/reported';
                        $list[$key]['reported_talk_comments_uri'] = $base . '/' . $version . '/events/'
                            . $row['ID'] . '/talk_comments/reported';
                    }
                    $list[$key]['all_talk_comments_uri'] = $base . '/' . $version . '/events/'
                                                           . $row['ID'] . '/talk_comments';
                    $list[ $key]['hosts']                 = $this->getHosts($row['ID']);
                }
                $list[$key]['attendees_uri'] = $base . '/' . $version . '/events/' . $row['ID'] . '/attendees';

                if ($verbose) {
                    // can this user edit this event?
                    $list[$key]['can_edit'] = $this->thisUserHasAdminOn($row['ID']);
                }
            }
        }
        $retval           = array();
        $retval['events'] = $list;
        $retval['meta']   = $this->getPaginationLinks($list, $total);

        return $retval;
    }

    /**
     * Fetch the users who are hosting this event
     *
     * @param int $event_id
     *
     * @return array The list of people hosting the event
     */
    protected function getHosts($event_id)
    {
        $base    = $this->_request->base;
        $version = $this->_request->version;

        $host_sql  = $this->getHostSql();
        $host_stmt = $this->_db->prepare($host_sql);
        $host_stmt->execute(array("event_id" => $event_id));
        $hosts  = $host_stmt->fetchAll(PDO::FETCH_ASSOC);
        $retval = array();
        if (is_array($hosts)) {
            foreach ($hosts as $person) {
                $entry              = array();
                $entry['host_name'] = $person['full_name'];
                $entry['host_uri']  = $base . '/' . $version . '/users/' . $person['user_id'];
                $retval[]           = $entry;
            }
        }

        return $retval;
    }

    /**
     * SQL for fetching event hosts, so it can be used in multiple places
     *
     * @return SQL to fetch hosts, containing an :event_id named parameter
     */
    protected function getHostSql()
    {
        $host_sql = 'select a.uid as user_id, u.full_name'
                    . ' from user_admin a '
                    . ' inner join user u on u.ID = a.uid '
                    . ' where rid = :event_id and rtype="event" and (rcode!="pending" OR rcode is null)';

        return $host_sql;
    }

    /**
     * Fetch the email addresses of the event hosts so that we can email them
     *
     * @param int $event_id
     *
     * @return array The email addresses of people hosting the event
     */
    public function getHostsEmailAddresses($event_id)
    {
        $base    = $this->_request->base;
        $version = $this->_request->version;

        $host_sql  = 'select u.email'
                     . ' from user_admin a '
                     . ' inner join user u on u.ID = a.uid '
                     . ' where rid = :event_id and rtype="event" and (rcode!="pending" OR rcode is null)';
        $host_stmt = $this->_db->prepare($host_sql);
        $host_stmt->execute(array("event_id" => $event_id));

        return $host_stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    /**
     * Return an array of tags for the event
     *
     * @param int $event_id The event whose tags we want
     *
     * @return array An array of tags
     */
    protected function getTags($event_id)
    {
        $tag_sql  = 'select tag_value as tag'
                    . ' from tags_events te'
                    . ' inner join tags t on t.ID = te.tag_id'
                    . ' where te.event_id = :event_id';
        $tag_stmt = $this->_db->prepare($tag_sql);
        $tag_stmt->execute(array("event_id" => $event_id));
        $tags   = $tag_stmt->fetchAll(PDO::FETCH_ASSOC);
        $retval = array();
        if (is_array($tags)) {
            foreach ($tags as $row) {
                $retval[] = $row['tag'];
            }
        }

        return $retval;
    }

    /**
     * Events that a particular user has admin privileges on
     *
     * @param int $resultsperpage how many records to return
     * @param int $start offset to start returning records from
     * @param boolean $verbose used to determine how many fields are needed
     *
     * @return array the data, or false if something went wrong
     */
    public function getEventsHostedByUser($user_id, $resultsperpage, $start, $verbose = false)
    {
        $data = array("user_id" => (int) $user_id);

        $sql = 'select events.*, '
               . '(select count(*) from user_attend where user_attend.eid = events.ID) as attendee_count, '
               . 'abs(datediff(from_unixtime(events.event_start), from_unixtime(' . mktime(0, 0, 0) . '))) as score, '
               . 'CASE WHEN (((events.event_start - 3600*24) < ' . mktime(0, 0, 0)
               . ') and (events.event_start + (3*30*3600*24)) > ' . mktime(0, 0, 0)
               . ') THEN 1 ELSE 0 END as comments_enabled '
               . 'from events '
               . 'join user_admin ua on (ua.rid = events.ID) AND rtype="event" AND (rcode!="pending" OR rcode is null)';

        $sql .= 'where active = 1 and '
                . '(pending = 0 or pending is NULL) and '
                . ' ua.uid = :user_id';

        $sql .= ' order by events.event_start desc ';

        // limit clause
        $sql .= $this->buildLimit($resultsperpage, $start);

        $stmt     = $this->_db->prepare($sql);
        $response = $stmt->execute($data);
        if ($response) {
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            if (is_array($results)) {
                $results['total'] = $this->getTotalCount($sql, $data);
                $retval           = $this->transformResults($results, $verbose);

                return $retval;
            }
        }

        return false;
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
        $data = array("user_id" => (int) $user_id);
        $sql  = 'select events.*, '
                . '(select count(*) from user_attend where user_attend.eid = events.ID) as attendee_count, '
                . 'abs(datediff(from_unixtime(events.event_start), from_unixtime(' .
                mktime(0, 0, 0) . '))) as score, '
                . 'CASE WHEN (((events.event_start - 3600*24) < ' . mktime(0, 0, 0) .
                ') and (events.event_start + (3*30*3600*24)) > ' . mktime(0, 0, 0)
                . ') THEN 1 ELSE 0 END as comments_enabled '
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

        $stmt     = $this->_db->prepare($sql);
        $response = $stmt->execute($data);
        if ($response) {
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            if (is_array($results)) {
                $results['total'] = $this->getTotalCount($sql, $data);
                $retval           = $this->transformResults($results, $verbose);

                return $retval;
            }
        }

        return false;
    }

    /**
     * Does the currently-authenticated user have rights on a particular event?
     *
     * @param int $event_id The identifier for the event to check
     *
     * @return bool True if the user has privileges, false otherwise
     */
    public function thisUserHasAdminOn($event_id)
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
            return $this->isUserAHostOn($this->_request->user_id, $event_id);
        }

        return false;
    }

    /**
     * Does the currently-authenticated user have rights to approve events?
     *
     * @return bool True if the user has rights, false otherwise
     */
    public function thisUserCanApproveEvents()
    {
        // do we even have an authenticated user?
        if (isset($this->_request->user_id)) {
            $user_mapper = new UserMapper($this->_db, $this->_request);

            // is user site admin?
            $is_site_admin = $user_mapper->isSiteAdmin($this->_request->user_id);
            if ($is_site_admin) {
                return true;
            }
        }

        return false;
    }

    /**
     * Is this user
     *
     * @param  int $user_id The identifier of the user to check
     * @param  int $event_id The identifier of the event to check
     *
     * @return True if the user is a host, false otherwise
     */
    public function isUserAHostOn($user_id, $event_id)
    {
        $sql = $this->getHostSql();
        $sql .= ' AND u.ID = :user_id';
        $stmt = $this->_db->prepare($sql);
        $stmt->execute(array(
            "event_id" => $event_id,
            "user_id"  => $user_id,
        ));
        $results = $stmt->fetchAll();
        if ($results) {
            return true;
        }

        return false;
    }

    /**
     * Count the number of talk comments for an event
     *
     * @param int $event_id The event to count talk comments for
     *
     * @return int Number of comments across all talks
     */
    protected function getTalkCommentCount($event_id)
    {
        $comment_sql  = 'select count(*) as comment_count from talk_comments tc '
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
     * @param string $name The event name to inflect
     * @param int $event_id The event to store it against
     *
     * @return string The value we stored
     */
    protected function generateInflectedName($name, $event_id, $start_date)
    {
        $date           = new DateTime($start_date);
        $inflected_name = $this->inflect($name);
        $name_choices   = array(
            $inflected_name,
            $inflected_name . $date->format('-Y'),
            $inflected_name . $date->format('-Y-m'),
            $inflected_name . $date->format('-Y-m-d'),
            // weird/random friendly name is better than no friendly name
            $inflected_name . $date->format('-Y-m-d') . '-' . mt_rand(1, 99),
        );

        foreach ($name_choices as $inflected_name) {
            $sql = "update events set url_friendly_name = :inflected_name where ID = :event_id";

            $stmt   = $this->_db->prepare($sql);

            try {
                $result = $stmt->execute(array(
                    "inflected_name" => $inflected_name,
                    "event_id"       => $event_id,
                ));

                return $inflected_name;
            } catch (Exception $e) {
                // failed - try again
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
     * @param string[] $event Event data to insert into the database.
     * @param boolean $auto_approve if false an event is registered as 'pending' first and must be actively approved.
     *
     * @return integer|false
     */
    public function createEvent($event, $auto_approve = false)
    {

        // Sanity check: ensure all mandatory fields are present.
        $mandatory_fields          = array(
            'name',
            'description',
            'start_date',
            'end_date',
            'tz_continent',
            'tz_place',
            'contact_name',
        );
        $contains_mandatory_fields = !array_diff($mandatory_fields, array_keys($event));
        if (!$contains_mandatory_fields) {
            throw new Exception("Missing mandatory fields");
        }

        $sql = "insert into events set ";

        // create list of column to API field name for all valid fields
        $fields = $this->getVerboseFields();
        foreach ($fields as $api_name => $column_name) {
            if (isset($event[$api_name])) {
                $pairs[] = "$column_name = :$api_name";
            }
        }

        // the active opposite to pending to get it on the right web1 lists
        $pairs[] = 'private = 0';
        if (!$auto_approve) {
            $pairs[] = 'pending = 1';
            $pairs[] = 'active = 0';
        } else {
            $pairs[] = 'active = 1';
        }

        // comma separate all pairs and add to SQL string
        $sql .= implode(', ', $pairs);

        $stmt   = $this->_db->prepare($sql);
        $result = $stmt->execute($event);
        if ($result) {
            return $this->_db->lastInsertId();
        }

        return false;
    }

    /**
     * Edit an event.
     *
     * Accepts a subset of event fields
     *
     * @param string[] $event Event data to insert into the database.
     * @param int $event_id The ID of the event to be edited
     *
     * @return integer|false
     */
    public function editEvent($event, $event_id)
    {
        // Sanity check: ensure all mandatory fields are present.
        $mandatory_fields          = array(
            'name',
            'description',
            'start_date',
            'end_date',
            'tz_continent',
            'tz_place',
        );
        $contains_mandatory_fields = !array_diff($mandatory_fields, array_keys($event));
        if (!$contains_mandatory_fields) {
            throw new Exception("Missing mandatory fields");
        }

        $sql = "UPDATE events SET %s WHERE ID = :event_id";

        // get the list of column to API field name for all valid fields
        $fields = $this->getVerboseFields();
        $items  = array();

        foreach ($fields as $api_name => $column_name) {
            // We don't change any activation stuff here!!
            if (in_array($column_name, ['pending', 'active'])) {
                continue;
            }
            if (array_key_exists($api_name, $event)) {
                $pairs[]            = "$column_name = :$api_name";
                $items[$api_name] = $event[$api_name];
            }
        }

        $items['event_id'] = $event_id;

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

        return $event_id;
    }

    /**
     * Add a user as an admin on an event
     */
    public function addUserAsHost($event_id, $user_id)
    {
        $sql    = "insert into user_admin set rtype = 'event', rid = :event_id, uid = :user_id";
        $stmt   = $this->_db->prepare($sql);
        $result = $stmt->execute(array("event_id" => $event_id, "user_id" => $user_id));

        return $result;
    }

    /**
     * Remove a user's admin rights to a specific event
     */
    public function removeUserAsHost($event_id, $user_id)
    {
        $sql    = "delete from user_admin where rtype = 'event' and rid = :event_id and uid = :user_id";
        $stmt   = $this->_db->prepare($sql);
        $result = $stmt->execute(array("event_id" => $event_id, "user_id" => $user_id));

        return $result;
    }

    /**
     * Update the cached count of talks for a specific event
     *
     * @param $event_id
     *
     * @return bool
     */
    public function cacheTalkCount($event_id)
    {
        $sql    = "UPDATE events e SET talk_count = (SELECT COUNT(*) FROM talks t WHERE t.event_id = e.ID) ".
                  "WHERE e.ID = :event_id;";
        $stmt   = $this->_db->prepare($sql);
        $result = $stmt->execute(array("event_id" => $event_id));

        return $result;
    }

    /**
     * Update the cached count of comments for a specific event
     *
     * @param $event_id
     *
     * @return bool
     */
    public function cacheCommentCount($event_id)
    {
        $sql    = "UPDATE events e SET comment_count = ".
                  "(SELECT COUNT(*) FROM event_comments ec WHERE ec.event_id = e.ID) WHERE e.ID = :event_id;";
        $stmt   = $this->_db->prepare($sql);
        $result = $stmt->execute(array("event_id" => $event_id));

        return $result;
    }

    /**
     * Update the cached count of tracks for a specific event
     *
     * @param $event_id
     *
     * @return bool
     */
    public function cacheTrackCount($event_id)
    {
        $sql    = "UPDATE events e SET track_count = (SELECT COUNT(*) FROM event_track et WHERE et.event_id = e.ID) " .
                  "WHERE e.ID = :event_id;";
        $stmt   = $this->_db->prepare($sql);
        $result = $stmt->execute(array("event_id" => $event_id));

        return $result;
    }

    /**
     * Get an event by ID, but just the event table fields
     * and including pending events
     *
     * @param int $event_id The event you want
     *
     * @return array The event details, or false if it wasn't found
     */
    public function getPendingEventById($event_id)
    {
        $sql = 'select events.* '
               . 'from events '
               . 'where events.ID = :event_id ';

        $stmt     = $this->_db->prepare($sql);
        $response = $stmt->execute(array("event_id" => $event_id));
        if ($response) {
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $retval  = parent::transformResults($results, true);

            return $retval[0];
        }

        return false;
    }

    /*
     * How many events currently pending?
     *
     * @return int The number of pending events
     */
    public function getPendingEventsCount()
    {
        $sql = 'select count(*) as count '
               . 'from events '
               . 'where pending = 1 and (events.private <> "y" OR events.private IS NULL)';

        $stmt     = $this->_db->prepare($sql);
        $response = $stmt->execute();
        $result   = $stmt->fetch();

        return $result['count'];
    }

    /*
     * How many events currently pending for a
     * given user
     *
     * @param int $user_id The user ID to search for
     * @return int The number of pending events
     */
    public function getPendingEventsCountByUser($user_id)
    {
        $sql = 'select count(*) as count '
            . 'from events e '
            . 'join user_admin ua ON e.ID=ua.rid '
            . 'where e.pending = 1 AND ua.rtype="event" '
            . 'AND ua.uid = :user_id ';

        $stmt     = $this->_db->prepare($sql);
        $response = $stmt->execute(array("user_id" => $user_id));
        $result   = $stmt->fetch();

        return $result['count'];
    }

    /**
     * Add the tags to the given event
     *
     * For that we first remove all entries from the tags_events-Table that
     * connect the given event with any tag from the tags-table. Then we check
     * for each tag whether it exists in the tags-table nad if not add it to
     * that table. Finally we add an entry to the tags_events-table for each
     * given tag to connect the new tags with the given event.
     *
     * @param int $event_id
     * @param array $tags
     *
     * @return bool
     */
    public function setTags($event_id, array $tags)
    {
        $deleteAllEventTagsSql  = 'DELETE FROM tags_events WHERE event_id = :event_id;';
        $deleteAllEventTagsStmt = $this->_db->prepare($deleteAllEventTagsSql);

        $checkForTagSql  = 'SELECT ID FROM tags WHERE tag_value = :tag;';
        $checkForTagStmt = $this->_db->prepare($checkForTagSql);

        $addTagToDbSql  = 'INSERT INTO tags SET tag_value = :tag;';
        $addTagToDbStmt = $this->_db->prepare($addTagToDbSql);

        $addTagToEventSql  = 'INSERT INTO tags_events SET tag_id = :tag_id, event_id = :event_id;';
        $addTagToEventStmt = $this->_db->prepare($addTagToEventSql);

        // remove all existing tags for the event
        $deleteAllEventTagsStmt->execute(array('event_id' => $event_id));

        // for each tag
        foreach ($tags as $tag) {
            // Check whether the tag already exists in the tag-list
            $result = $checkForTagStmt->execute(array('tag' => $tag));
            $tagId  = $checkForTagStmt->fetchColumn(0);
            if (!$tagId) {
                // If not, add the event to the tag list
                $addTagToDbStmt->execute(array('tag' => $tag));
                $checkForTagStmt->execute(array('tag' => $tag));
                $tagId = $checkForTagStmt->fetchColumn(0);
            }

            // Add an association with the event
            $addTagToEventStmt->execute(array('tag_id' => $tagId, 'event_id' => $event_id));
        }
    }

    /**
     * Approve a pending event
     *
     * @param  integer $event_id
     * @param  integer $reviewing_user_id The user that approved this event
     *
     * @return boolean
     */
    public function approve($event_id, $reviewing_user_id)
    {
        $sql      = "select ID from events where pending = 1 and active = 0 and ID = :event_id";
        $stmt     = $this->_db->prepare($sql);
        $response = $stmt->execute(["event_id" => $event_id]);
        $result   = $stmt->fetch();
        if ($result === false) {
            // Event either doesn't exist or is not pending
            return false;
        }

        $sql  = "update events set pending = 0, active = 1, reviewer_id = :reviewing_user_id where ID = :event_id";
        $stmt = $this->_db->prepare($sql);

        return $stmt->execute(["event_id" => $event_id, "reviewing_user_id" => $reviewing_user_id]);
    }

    /**
     * Reject a pending event
     *
     * @param  integer $event_id
     * @param  integer $reviewing_user_id The user who rejected the event
     *
     * @return boolean
     */
    public function reject($event_id, $reviewing_user_id)
    {
        $sql      = "select ID from events where pending = 1 and active = 0 and ID = :event_id";
        $stmt     = $this->_db->prepare($sql);
        $response = $stmt->execute(["event_id" => $event_id]);
        $result   = $stmt->fetch();
        if ($result === false) {
            return false;
        }

        $sql  = "update events set pending = 0, active = 0, reviewer_id = :reviewing_user_id where ID = :event_id";
        $stmt = $this->_db->prepare($sql);

        return $stmt->execute(["event_id" => $event_id, "reviewing_user_id" => $reviewing_user_id]);
    }

    /**
     * Fetch the available images for this event
     *
     * @param int $event_id
     *
     * @return array The images including metadata
     */
    protected function getImages($event_id)
    {
        $image_sql = 'select i.type, i.url, i.width, i.height'
                    . ' from event_images i '
                    . ' where i.event_id = :event_id';
        $image_stmt = $this->_db->prepare($image_sql);
        $image_stmt->execute(array("event_id" => $event_id));
        $images  = $image_stmt->fetchAll(PDO::FETCH_ASSOC);

        // add named keys so we can easily refer to these results
        $collection = [];
        if ($images && is_array($images)) {
            foreach ($images as $row) {
                $collection[$row['type']] = $row;
            }
        }
        return $collection;
    }

    /**
     * Remove all image records for this event
     *
     * Used when we are uploading new images
     * @param int $event_id the event to add an image to
     * @return bool whether the record was saved
     */
    public function removeImages($event_id)
    {
        $sql = 'delete from event_images'
            . ' where event_id = :event_id';
        $stmt = $this->_db->prepare($sql);
        $result = $stmt->execute(array("event_id" => $event_id));

        return $result;
    }

    /**
     * Add a database record regarding a new image file
     *
     * For legacy reasons, we'll add a "small" image to the events table also
     *
     * @param int $event_id the event to add an image to
     * @param string $filename the filename we saved the image as (the rest of
     *      the URL is hardcoded here for now because images don't work the
     *      same way on dev as they do on live)
     * @param int $width the width of the image
     * @param int $height the height of the image
     * @param string $type Freeform field for what sort of image it is, "orig" and "small" are our starter set
     * @return bool whether the record was saved
     */
    public function saveNewImage($event_id, $filename, $width, $height, $type)
    {
        $sql = 'insert into event_images set '
            . 'event_id = :event_id, width = :width, height = :height, '
            . 'url = :url, type = :type';
        $stmt = $this->_db->prepare($sql);
        $result = $stmt->execute([
            "event_id" => $event_id,
            "type" => $type,
            "width" => $width,
            "height" => $height,
            "url" => $this->website_url . "/inc/img/event_icons/" . $filename,
        ]);

        // for small images, update the old table too
        if ($type == "small") {
            $legacy_sql = 'update events set event_icon = :filename where ID = :event_id';
            $legacy_stmt = $this->_db->prepare($legacy_sql);
            $legacy_stmt->execute(["event_id" => $event_id, "filename" => $filename]);
        }
        return $result;
    }
}
