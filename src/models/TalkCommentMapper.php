<?php

class TalkCommentMapper extends ApiMapper
{
    /**
     * @return array
     */
    public function getDefaultFields()
    {
        return array(
            'rating'            => 'rating',
            'comment'           => 'comment',
            'user_display_name' => 'full_name',
            'username'          => 'username',
            'talk_title'        => 'talk_title',
            'created_date'      => 'date_made'
        );
    }

    /**
     * @return array
     */
    public function getVerboseFields()
    {
        return array(
            'rating'            => 'rating',
            'comment'           => 'comment',
            'user_display_name' => 'full_name',
            'username'          => 'username',
            'talk_title'        => 'talk_title',
            'source'            => 'source',
            'created_date'      => 'date_made',
        );
    }

    /**
     * @param int $talk_id
     * @param int $resultsperpage
     * @param int $start
     * @param bool $verbose
     *
     * @return false|array
     */
    public function getCommentsByTalkId($talk_id, $resultsperpage, $start, $verbose = false)
    {
        $sql = $this->getBasicSQL();
        $sql .= 'and talk_id = :talk_id';
        $sql .= ' order by tc.date_made';

        $sql .= $this->buildLimit($resultsperpage, $start);
        $stmt     = $this->_db->prepare($sql);
        $response = $stmt->execute(array(
            ':talk_id' => $talk_id
        ));
        if ($response) {
            $results          = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $results['total'] = $this->getTotalCount($sql, array(':talk_id' => $talk_id));

            return $this->transformResults($results, $verbose);
        }

        return false;
    }

    /**
     * @param int $event_id
     * @param int $resultsperpage
     * @param int $start
     * @param bool $verbose
     *
     * @return false|array
     */
    public function getCommentsByEventId($event_id, $resultsperpage, $start, $verbose = false)
    {
        $sql = $this->getBasicSQL();
        $sql .= 'and event_id = :event_id ';

        // default to newest
        $sql .= ' order by tc.date_made desc';

        $sql .= $this->buildLimit($resultsperpage, $start);

        $stmt     = $this->_db->prepare($sql);
        $response = $stmt->execute(array(
            ':event_id' => $event_id
        ));
        if ($response) {
            $results          = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $results['total'] = $this->getTotalCount($sql, array(':event_id' => $event_id));

            return $this->transformResults($results, $verbose);
        }

        return false;
    }

    /**
     * @param int $user_id
     * @param int $resultsperpage
     * @param int $start
     * @param bool $verbose
     *
     * @return false|array
     */
    public function getCommentsByUserId($user_id, $resultsperpage, $start, $verbose = false)
    {
        $sql = $this->getBasicSQL();
        $sql .= 'and tc.user_id = :user_id ';

        // default to newest
        $sql .= ' order by tc.date_made desc';

        $sql .= $this->buildLimit($resultsperpage, $start);

        $stmt     = $this->_db->prepare($sql);
        $response = $stmt->execute(array(
            ':user_id' => $user_id
        ));
        if ($response) {
            $results          = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $results['total'] = $this->getTotalCount($sql, array(':user_id' => $user_id));

            return $this->transformResults($results, $verbose);
        }

        return false;
    }

    /**
     * @param int $comment_id
     * @param bool $verbose
     * @param bool $include_hidden
     *
     * @return false|array
     */
    public function getCommentById($comment_id, $verbose = false, $include_hidden = false)
    {
        $sql = $this->getBasicSQL($include_hidden);
        $sql .= ' and tc.ID = :comment_id ';
        $stmt     = $this->_db->prepare($sql);
        $response = $stmt->execute(array(
            ':comment_id' => $comment_id
        ));
        if ($response) {
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            if ($results) {
                $results['total'] = $this->getTotalCount($sql, array(':comment_id' => $comment_id));

                return $this->transformResults($results, $verbose);
            }
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

        // add per-item links
        if (is_array($list) && count($list)) {
            foreach ($results as $key => $row) {
                $list[$key] = array_merge(
                    $list[$key],
                    $this->formatOneComment($row, $verbose)
                );
            }
        }

        return [
            'comments' => $list,
            'meta' => $this->getPaginationLinks($list, $total),
        ];
    }

    /**
     * @param array $row
     * @param bool $verbose
     *
     * @return array
     */
    protected function formatOneComment(array $row, $verbose)
    {
        $base    = $this->_request->base;
        $version = $this->_request->version;

        $result = []; // we're building up a value to return

        if (true === $verbose) {
            $result['gravatar_hash'] = md5(strtolower($row['email']));
        }
        $result['uri'] = $base . '/' . $version . '/talk_comments/' . $row['ID'];
        $result['verbose_uri'] = $base . '/' . $version . '/talk_comments/' . $row['ID'] . '?verbose=yes';
        $result['talk_uri'] = $base . '/' . $version . '/talks/'. $row['talk_id'];
        $result['talk_comments_uri'] = $base . '/' . $version . '/talks/' . $row['talk_id'] . '/comments';
        $result['reported_uri'] = $base . '/' . $version . '/talk_comments/' . $row['ID'] . '/reported';
        if ($row['user_id']) {
            $result['user_uri'] = $base . '/' . $version . '/users/' . $row['user_id'];
        }
        return $result;
    }

    /**
     * Template SQL for all comment-fetching queries
     *
     * @param bool $include_hidden If set to true, will return inactive and private comments also
     *
     * @return string
     */
    protected function getBasicSQL($include_hidden = false)
    {
        $sql = 'select tc.*, '
               . 'user.username, user.email, user.full_name, t.talk_title, '
               . 'e.event_tz_cont, e.event_tz_place, t.date_given '
               . 'from talk_comments tc '
               . 'inner join talks t on t.ID = tc.talk_id '
               . 'inner join events e on t.event_id = e.ID '
               . 'left join user on tc.user_id = user.ID '
               . 'where 1 ';

        if (!$include_hidden) {
            $sql .= 'and tc.active = 1 and tc.private <> 1 ';
        }

        return $sql;
    }

    /**
     * @param array $data
     *
     * @throws Exception
     * @return string
     */
    public function save(array $data)
    {
        // check for a duplicate first
        $dupe_sql = 'select tc.ID from talk_comments tc '
                    . 'where talk_id = :talk_id and user_id = :user_id and comment = :comment';

        $dupe_stmt = $this->_db->prepare($dupe_sql);
        $dupe_stmt->execute(array(
            ':talk_id' => $data['talk_id'],
            ':comment' => $data['comment'],
            ':user_id' => $data['user_id'],
        ));

        // only proceed if we didn't already find a row like this
        if ($dupe_stmt->fetch()) {
            throw new Exception("Duplicate comment");
        }

        $sql = 'insert into talk_comments (talk_id, rating, comment, user_id, '
               . 'source, date_made, private, active) '
               . 'values (:talk_id, :rating, :comment, :user_id, :source, UNIX_TIMESTAMP(), '
               . ':private, 1)';

        $stmt     = $this->_db->prepare($sql);
        $response = $stmt->execute(array(
            ':talk_id' => $data['talk_id'],
            ':rating'  => $data['rating'],
            ':comment' => $data['comment'],
            ':user_id' => $data['user_id'],
            ':private' => $data['private'],
            ':source'  => $data['source'],
        ));

        return $this->_db->lastInsertId();
    }

    /**
     * Has this user provided a rating for this talk which is greater than zero?
     *
     * @param  integer $user_id
     * @param  integer $talk_id
     *
     * @return boolean
     */
    public function hasUserRatedThisTalk($user_id, $talk_id)
    {
        $sql = 'select ID from talk_comments '
               . 'where talk_id = :talk_id and user_id = :user_id and rating > 0';

        $stmt = $this->_db->prepare($sql);
        $stmt->execute(array(
            ':talk_id' => $talk_id,
            ':user_id' => $user_id,
        ));

        if ($stmt->fetch()) {
            return true;
        }

        return false;
    }

    /**
     * Get the event and talk ID this talk comment belongs to
     *
     * @param int $comment_id The comment in question
     *
     * @return false|array Some info
     */
    public function getCommentInfo($comment_id)
    {
        $sql = "select t.event_id, tc.talk_id
            from talk_comments tc
            join talks t on t.ID = tc.talk_id
            where tc.ID = :talk_comment_id";

        $stmt = $this->_db->prepare($sql);
        $stmt->execute(["talk_comment_id" => $comment_id]);

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
        $report_sql = "insert into reported_talk_comments
            set talk_comment_id = :talk_comment_id,
            reporting_user_id = :user_id,
            reporting_date = NOW()";

        $report_stmt = $this->_db->prepare($report_sql);
        $result = $report_stmt->execute([
            "talk_comment_id" => $comment_id,
            "user_id" => $user_id]);

        $hide_sql = "update talk_comments
            set active = 0 where ID = :talk_comment_id";
        $hide_stmt = $this->_db->prepare($hide_sql);
        $result = $hide_stmt->execute(["talk_comment_id" => $comment_id]);
    }

    /**
     * Get all the talk comments that have been reported for this event
     *
     * Includes verbose nested comment info
     *
     * @param $event_id int    The event whose comments should be returned
     * @param $moderated bool  Whether to include comments that have been moderated
     *
     * @return TalkCommentReportModelCollection
     */
    public function getReportedCommentsByEventId($event_id, $moderated = false)
    {
        $sql = "select rc.reporting_user_id, rc.deciding_user_id, rc.decision,
            rc.talk_comment_id, t.event_id, tc.talk_id,
            ru.username as reporting_username,
            du.username as deciding_username,
            UNIX_TIMESTAMP(rc.reporting_date) as reporting_date,
            UNIX_TIMESTAMP(rc.deciding_date) as deciding_date
            from reported_talk_comments rc
            join talk_comments tc on tc.ID = rc.talk_comment_id
            join talks t on t.ID = tc.talk_id
            left join user ru on ru.ID = rc.reporting_user_id
            left join user du on du.ID = rc.deciding_user_id
            where t.event_id = :event_id";

        if (false === $moderated) {
            $sql .= " and rc.decision is null";
        }

        $stmt = $this->_db->prepare($sql);
        $result = $stmt->execute(['event_id' => $event_id]);

        // need to also set the comment info
        $list = [];
        $total = 0;
        $comment_sql = $this->getBasicSQL(true)
            . " and tc.ID = :comment_id";
        $comment_stmt = $this->_db->prepare($comment_sql);
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $total++;
            $comment_result = $comment_stmt->execute(['comment_id' => $row['talk_comment_id']]);
            if ($comment_result && $comment = $comment_stmt->fetch(PDO::FETCH_ASSOC)) {
                // work around the existing transform logic
                $comment_array = [$comment];
                $comment_array = parent::transformResults($comment_array, true);
                $item = current($comment_array);
                $row['comment'] = array_merge($item, $this->formatOneComment($comment, true));
            }
            $list[] = new TalkCommentReportModel($row);
        }
        return new TalkCommentReportModelCollection($list, $total);
    }

    /**
     * A comment has been moderated.  Record the decision and if the decision
     * is 'denied' then set the comment back to active.
     *
     * @param string $decision the decision: 'approved' or 'denied'
     * @param int $comment_id the comment that was reported
     * @param int $user_id the user that reported it
     */
    public function moderateReportedComment($decision, $comment_id, $user_id)
    {
        if (in_array($decision, ['approved', 'denied'])) {
            // record the decision
            $sql = 'update reported_talk_comments set 
                        decision = :decision,
                        deciding_user_id = :user_id,
                        deciding_date = NOW()
                    where talk_comment_id = :comment_id';
            $stmt = $this->_db->prepare($sql);
            $stmt->execute([
                'decision' => $decision,
                'user_id' => $user_id,
                'comment_id' => $comment_id,
            ]);

            if ($decision == 'denied') {
                // the report is denied, therefore make the comment active again
                $show_sql = "update talk_comments set active = 1 where ID = :comment_id";
                $show_stmt = $this->_db->prepare($show_sql);
                $show_stmt->execute(["comment_id" => $comment_id]);
            }
        }
    }

    /**
     * @param integer $comment_id
     *
     * @return false|array
     */
    public function getRawComment($comment_id)
    {
        $sql = "select tc.* from talk_comments tc where tc.ID = :comment_id";
        $stmt = $this->_db->prepare($sql);
        $response = $stmt->execute(array(':comment_id' => $comment_id));
        if ($response) {
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            if (isset($results[0])) {
                return $results[0];
            }
        }

        return false;
    }

    /**
     * @param integer $comment_id
     * @param string $new_comment_body
     *
     * @return bool
     */
    public function updateCommentBody($comment_id, $new_comment_body)
    {
        $sql = "update talk_comments set comment = :new_comment_body where ID = :comment_id";
        $stmt = $this->_db->prepare($sql);

        return $stmt->execute([
            "new_comment_body" => $new_comment_body,
            "comment_id" => $comment_id
        ]);
    }
}
