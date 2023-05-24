<?php

namespace Joindin\Api\Model;

use Exception;
use PDO;

class EventCommentMapper extends ApiMapper
{
    public function getDefaultFields(): array
    {
        // warning, users added in build array
        return [
            'rating'       => 'rating',
            'comment'      => 'comment',
            'created_date' => 'date_made'
        ];
    }

    public function getVerboseFields(): array
    {
        return [
            'rating'       => 'rating',
            'comment'      => 'comment',
            'source'       => 'source',
            'created_date' => 'date_made',
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
    public function getEventCommentsByEventId($event_id, $resultsperpage, $start, $verbose = false)
    {
        $sql      = $this->getBasicSQL();
        $sql      .= 'and event_id = :event_id order by date_made desc ';
        $sql      .= $this->buildLimit($resultsperpage, $start);
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
     * @param int  $comment_id
     * @param bool $verbose
     * @param bool $include_hidden
     *
     * @return false|array
     */
    public function getCommentById($comment_id, $verbose = false, $include_hidden = false)
    {
        $sql      = $this->getBasicSQL($include_hidden);
        $sql      .= 'and ec.ID = :comment_id ';
        $stmt     = $this->_db->prepare($sql);
        $response = $stmt->execute([
            ':comment_id' => $comment_id
        ]);

        if ($response) {
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if ($results) {
                $results['total'] = $this->getTotalCount($sql, [':comment_id' => $comment_id]);

                return $this->transformResults($results, $verbose);
            }
        }

        return false;
    }

    /**
     * @inheritdoc
     */
    public function transformResults(array $results, $verbose): array
    {
        $total = $results['total'];
        unset($results['total']);

        $list = parent::transformResults($results, $verbose);

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
            'meta'     => $this->getPaginationLinks($list, $total),
        ];
    }

    /**
     * Add a way to just format one comment with its meta data etc
     *
     * This is used so we can nest comments inside other not-list settings
     *
     * @param array $row     The database row with the comment result
     * @param bool  $verbose The verbosity level
     *
     * @return array The extra fields to add to the existing data for this record
     */
    protected function formatOneComment($row, $verbose)
    {
        $base    = $this->_request->base;
        $version = $this->_request->version;
        $result  = []; // store formatted item here

        if (true === $verbose) {
            $result['gravatar_hash'] = md5(strtolower($row['email']));
        }
        // figure out user
        if ($row['user_id']) {
            $result['user_display_name'] = $row['full_name'];
            $result['username']          = $row['username'];
            $result['user_uri']          = $base . '/' . $version . '/users/'
                                           . $row['user_id'];
        } else {
            $result['user_display_name'] = $row['cname'];
        }

        // useful links
        $result['comment_uri']         = $base . '/' . $version . '/event_comments/'
                                         . $row['ID'];
        $result['verbose_comment_uri'] = $base . '/' . $version . '/event_comments/'
                                         . $row['ID'] . '?verbose=yes';
        $result['event_uri']           = $base . '/' . $version . '/events/'
                                         . $row['event_id'];
        $result['event_comments_uri']  = $base . '/' . $version . '/events/'
                                         . $row['event_id'] . '/comments';
        $result['reported_uri']        = $base . '/' . $version . '/event_comments/'
                                         . $row['ID'] . '/reported';

        return $result;
    }

    protected function getBasicSQL(bool $include_hidden = false): string
    {
        $sql = 'select ec.*, user.username, user.email, user.full_name, e.event_tz_cont, e.event_tz_place '
               . 'from event_comments ec '
               . 'left join user on user.ID = ec.user_id '
               . 'inner join events e on ec.event_id = e.ID '
               . 'where 1 ';

        if (!$include_hidden) {
            $sql .= 'and ec.active = 1 ';
        }

        return $sql;
    }

    public function save(array $data)
    {
        // check for a duplicate first
        $dupe_sql = 'select ec.ID from event_comments ec '
                    . 'where event_id = :event_id and user_id = :user_id and comment = :comment';

        $dupe_stmt = $this->_db->prepare($dupe_sql);
        $dupe_stmt->execute([
            ':event_id' => $data['event_id'],
            ':comment'  => $data['comment'],
            ':user_id'  => $data['user_id'],
        ]);

        // only proceed if we didn't already find a row like this
        if ($dupe_stmt->fetch()) {
            throw new Exception("Duplicate comment");
        }

        $sql = 'insert into event_comments (event_id, rating, comment, user_id, cname, '
               . 'source, date_made, active) '
               . 'values (:event_id, :rating, :comment, :user_id, :cname, :source, UNIX_TIMESTAMP(), 1)';

        $stmt     = $this->_db->prepare($sql);
        $response = $stmt->execute([
            ':event_id' => $data['event_id'],
            ':rating'   => $data['rating'],
            ':comment'  => $data['comment'],
            ':cname'    => $data['cname'],
            ':user_id'  => $data['user_id'],
            ':source'   => $data['source'],
        ]);

        return $this->_db->lastInsertId();
    }

    /**
     * Has this user provided a rating for this event which is greater than zero?
     *
     * @param int $user_id
     * @param int $event_id
     *
     * @return bool
     */
    public function hasUserRatedThisEvent($user_id, $event_id)
    {
        $sql = 'select ec.ID from event_comments ec '
               . 'where event_id = :event_id and user_id = :user_id and rating > 0';

        $stmt = $this->_db->prepare($sql);
        $stmt->execute([
            ':event_id' => $event_id,
            ':user_id'  => $user_id,
        ]);

        if ($stmt->fetch()) {
            return true;
        }

        return false;
    }

    /**
     * Get the event ID this event comment belongs to
     *
     * @param int $comment_id The comment in question
     *
     * @return false|array{event_id: int} including event_id
     */
    public function getCommentInfo($comment_id): false|array
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
     * @param int $user_id    the user that reported it
     */
    public function userReportedComment($comment_id, $user_id)
    {
        $report_sql = "insert into reported_event_comments
            set event_comment_id = :event_comment_id,
            reporting_user_id = :user_id,
            reporting_date = NOW()";

        $report_stmt = $this->_db->prepare($report_sql);
        $result      = $report_stmt->execute([
            "event_comment_id" => $comment_id,
            "user_id"          => $user_id
        ]);

        $hide_sql  = "update event_comments
            set active = 0 where ID = :event_comment_id";
        $hide_stmt = $this->_db->prepare($hide_sql);
        $result    = $hide_stmt->execute(["event_comment_id" => $comment_id]);
    }

    /**
     * Get all the comments that have been reported for this event
     *
     * Includes verbose nested comment info
     *
     * @param int  $event_id  The event whose comments should be returned
     * @param bool $moderated Whether to include comments that have been moderated
     *
     * @return EventCommentReportModelCollection
     */
    public function getReportedCommentsByEventId($event_id, $moderated = false)
    {
        $sql = "select rc.reporting_user_id, rc.deciding_user_id, rc.decision,
            rc.event_comment_id, ec.event_id,
            ru.username as reporting_username,
            du.username as deciding_username,
            UNIX_TIMESTAMP(rc.reporting_date) as reporting_date,
            UNIX_TIMESTAMP(rc.deciding_date) as deciding_date
            from reported_event_comments rc
            join event_comments ec on ec.ID = rc.event_comment_id
            left join user ru on ru.ID = rc.reporting_user_id
            left join user du on du.ID = rc.deciding_user_id
            where ec.event_id = :event_id";

        if (false === $moderated) {
            $sql .= " and rc.decision is null";
        }

        $stmt   = $this->_db->prepare($sql);
        $result = $stmt->execute(['event_id' => $event_id]);

        // need to also set the comment info
        $list         = [];
        $total        = 0;
        $comment_sql  = $this->getBasicSQL(true)
                        . " and ec.ID = :comment_id";
        $comment_stmt = $this->_db->prepare($comment_sql);

        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $total++;
            $comment_result = $comment_stmt->execute(['comment_id' => $row['event_comment_id']]);

            if ($comment_result && $comment = $comment_stmt->fetch(PDO::FETCH_ASSOC)) {
                // work around the existing transform logic
                $comment_array  = [$comment];
                $comment_array  = parent::transformResults($comment_array, true);
                $item           = current($comment_array);
                $row['comment'] = array_merge($item, $this->formatOneComment($comment, true));
            }
            $list[] = new EventCommentReportModel($row);
        }

        return new EventCommentReportModelCollection($list, $total);
    }

    /**
     * A comment has been moderated.  Record the decision and if the decision
     * is 'denied' then set the comment back to active.
     *
     * @param string $decision   the decision: 'approved' or 'denied'
     * @param int    $comment_id the comment that was reported
     * @param int    $user_id    the user that reported it
     */
    public function moderateReportedComment($decision, $comment_id, $user_id)
    {
        if (in_array($decision, ['approved', 'denied'])) {
            // record the decision
            $sql  = 'update reported_event_comments set
                        decision = :decision,
                        deciding_user_id = :user_id,
                        deciding_date = NOW()
                    where event_comment_id = :comment_id';
            $stmt = $this->_db->prepare($sql);
            $stmt->execute([
                'decision'   => $decision,
                'user_id'    => $user_id,
                'comment_id' => $comment_id,
            ]);

            if ($decision == 'denied') {
                // the report is denied, therefore make the comment active again
                $show_sql  = "update event_comments set active = 1 where ID = :comment_id";
                $show_stmt = $this->_db->prepare($show_sql);
                $show_stmt->execute(["comment_id" => $comment_id]);
            }
        }
    }
}
