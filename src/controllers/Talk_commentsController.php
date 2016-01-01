<?php

// @codingStandardsIgnoreStart
class Talk_commentsController extends ApiController
// @codingStandardsIgnoreEnd
{
    public function getComments($request, $db)
    {
        $comment_id = $this->getItemId($request);

        // verbosity
        $verbose = $this->getVerbosity($request);

        // pagination settings
        $start          = $this->getStart($request);
        $resultsperpage = $this->getResultsPerPage($request);

        $mapper = new TalkCommentMapper($db, $request);
        if ($comment_id) {
            $list = $mapper->getCommentById($comment_id, $verbose);
            if (false === $list) {
                throw new Exception('Comment not found', 404);
            }

            return $list;
        }

        return false;
    }

    public function getReported($request, $db)
    {
        $event_id = $this->getItemId($request);
        if(empty($event_id)) {
            throw new UnexpectedValueException("Event not found", 404);
        }

        $event_mapper   = new EventMapper($db, $request);
        $comment_mapper = new TalkCommentMapper($db, $request);

        if (! isset($request->user_id) || empty($request->user_id)) {
            throw new Exception("You must log in to do that", 401);
        }

        if($event_mapper->thisUserHasAdminOn($event_id)) {
            $list = $comment_mapper->getReportedCommentsByEventId($event_id);
            return $list;
        } else {
            throw new Exception("You don't have permission to do that", 403);
        }
    }

    public function reportComment($request, $db)
    {
        // must be logged in to report a comment
        if (! isset($request->user_id) || empty($request->user_id)) {
            throw new Exception('You must log in to report a comment');
        }

        $comment_mapper = new TalkCommentMapper($db, $request);

        $commentId   = $this->getItemId($request);
        $commentInfo = $comment_mapper->getCommentInfo($commentId);
        if (false === $commentInfo) {
            throw new Exception('Comment not found', 404);
        }

        $talkId  = $commentInfo['talk_id'];
        $eventId = $commentInfo['event_id'];

        $comment_mapper->userReportedComment($commentId, $request->user_id);

        // notify event admins
        $comment      = $comment_mapper->getCommentById($commentId, true, true);
        $event_mapper = new EventMapper($db, $request);
        $recipients   = $event_mapper->getHostsEmailAddresses($eventId);

        $emailService = new CommentReportedEmailService($this->config, $recipients, $comment);
        $emailService->sendEmail();

        // send them to the comments collection
        $uri = $request->base . '/' . $request->version . '/talks/' . $talkId . "/comments";
        header("Location: " . $uri, true, 202);
        exit;
    }
}
