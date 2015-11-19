<?php

class EventCommentMapper extends ApiMapper
{
    public function getDefaultFields()
    {
        // warning, users added in build array
        $fields = array(
            'rating'       => 'rating',
            'comment'      => 'comment',
            'created_date' => 'date_made'
        );

        return $fields;
    }

    public function getVerboseFields()
    {
        $fields = array(
            'rating'       => 'rating',
            'comment'      => 'comment',
            'source'       => 'source',
            'created_date' => 'date_made',
        );

        return $fields;
    }

    public function getEventCommentsByEventId($event_id, $resultsperpage, $start, $verbose = false)
    {
        $sql = $this->getBasicSQL();
        $sql .= 'and event_id = :event_id order by date_made ';
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

    public function getCommentById($comment_id, $verbose = false)
    {
        $sql = $this->getBasicSQL();
        $sql .= 'and ec.ID = :comment_id ';
        $stmt     = $this->_db->prepare($sql);
        $response = $stmt->execute(array(
            ':comment_id' => $comment_id
        ));
        if ($response) {
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            if ($results) {
                $results['total'] = $this->getTotalCount($sql, array(':comment_id' => $comment_id));
                $retval           = $this->transformResults($results, $verbose);

                return $retval;
            }
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

        if (is_array($list) && count($list)) {
            foreach ($results as $key => $row) {
                if (true === $verbose) {
                    $list[$key]['gravatar_hash'] = md5(strtolower($row['email']));
                }
                // figure out user
                if ($row['user_id']) {
                    $list[$key]['user_display_name'] = $row['full_name'];
                    $list[$key]['user_uri']          = $base . '/' . $version . '/users/'
                                                         . $row['user_id'];
                } else {
                    $list[$key]['user_display_name'] = $row['cname'];
                }

                // useful links
                $list[$key]['comment_uri']         = $base . '/' . $version . '/event_comments/'
                                                       . $row['ID'];
                $list[$key]['verbose_comment_uri'] = $base . '/' . $version . '/event_comments/'
                                                       . $row['ID'] . '?verbose=yes';
                $list[$key]['event_uri']           = $base . '/' . $version . '/events/'
                                                       . $row['event_id'];
                $list[$key]['event_comments_uri']  = $base . '/' . $version . '/events/'
                                                       . $row['event_id'] . '/comments';
                $list[$key]['reported_uri']        = $base . '/' . $version . '/event_comments/'
                                                       . $row['ID'] . '/reported';
            }

        }
        $retval             = array();
        $retval['comments'] = $list;
        $retval['meta']     = $this->getPaginationLinks($list, $total);

        return $retval;
    }

    protected function getBasicSQL()
    {
        $sql = 'select ec.*, user.email, user.full_name, e.event_tz_cont, e.event_tz_place '
               . 'from event_comments ec '
               . 'left join user on user.ID = ec.user_id '
               . 'inner join events e on ec.event_id = e.ID '
               . 'where ec.active = 1 ';

        return $sql;

    }

    public function save($data)
    {
        // check for a duplicate first
        $dupe_sql = 'select ec.ID from event_comments ec '
                    . 'where event_id = :event_id and user_id = :user_id and comment = :comment';

        $dupe_stmt = $this->_db->prepare($dupe_sql);
        $dupe_stmt->execute(array(
            ':event_id' => $data['event_id'],
            ':comment'  => $data['comment'],
            ':user_id'  => $data['user_id'],
        ));

        // only proceed if we didn't already find a row like this
        if ($dupe_stmt->fetch()) {
            throw new Exception("Duplicate comment");
        }

        $sql = 'insert into event_comments (event_id, rating, comment, user_id, cname, '
               . 'source, date_made, active) '
               . 'values (:event_id, :rating, :comment, :user_id, :cname, :source, UNIX_TIMESTAMP(), 1)';

        $stmt     = $this->_db->prepare($sql);
        $response = $stmt->execute(array(
            ':event_id' => $data['event_id'],
            ':rating'   => $data['rating'],
            ':comment'  => $data['comment'],
            ':cname'    => $data['cname'],
            ':user_id'  => $data['user_id'],
            ':source'   => $data['source'],
        ));

        $comment_id = $this->_db->lastInsertId();

        return $comment_id;
    }

    /**
     * Has this user provided a rating for this event which is greater than zero?
     *
     * @param  integer $user_id
     *
     * @return boolean
     */
    public function hasUserRatedThisEvent($user_id, $event_id)
    {
        $sql = 'select ec.ID from event_comments ec '
               . 'where event_id = :event_id and user_id = :user_id and rating > 0';

        $stmt = $this->_db->prepare($sql);
        $stmt->execute(array(
            ':event_id' => $event_id,
            ':user_id'  => $user_id,
        ));

        if ($stmt->fetch()) {
            return true;
        }

        return false;
    }

    /**
     * Get the event ID this event comment belongs to
     *
     * @param int $comment_id The comment in question
     * @return array including event_id
     */
    public function getCommentInfo($comment_id)
    {
        $sql = "select ec.event_id
            from event_comments ec
            where ec.ID = :event_comment_id";

        $stmt = $this->_db->prepare($sql);
        $stmt->execute(["event_comment_id" => $comment_id]);

        if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            return $row;
        }

        return false;
    }

    /**
     * A comment has been reported.  Record the report and hide the comment
     * pending moderation
     *
     * @param int $comment_id the comment that was reported
     * @param int $user_id the user that reported it
     */
    public function userReportedComment($comment_id, $user_id)
    {
        $report_sql = "insert into reported_event_comments
            set event_comment_id = :event_comment_id,
            reporting_user_id = :user_id,
            reporting_date = NOW()";

        $report_stmt = $this->_db->prepare($report_sql);
        $result = $report_stmt->execute([
            "event_comment_id" => $comment_id,
            "user_id" => $user_id]);

        $hide_sql = "update event_comments
            set active = 0 where ID = :event_comment_id";
        $hide_stmt = $this->_db->prepare($hide_sql);
        $result = $hide_stmt->execute(["event_comment_id" => $comment_id]);
    }
}
