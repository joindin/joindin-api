<?php
// @codingStandardsIgnoreStart
class Event_commentsController extends BaseApiController
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

        $mapper = new EventCommentMapper($db, $request);
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
        if (empty($event_id)) {
            throw new UnexpectedValueException("Event not found", 404);
        }

        // verbosity
        $verbose = $this->getVerbosity($request);

        $event_mapper   = new EventMapper($db, $request);
        $comment_mapper = new EventCommentMapper($db, $request);

        if (! isset($request->user_id) || empty($request->user_id)) {
            throw new Exception("You must log in to do that", 401);
        }

        if ($event_mapper->thisUserHasAdminOn($event_id)) {
            $list = $comment_mapper->getReportedCommentsByEventId($event_id);
            return $list->getOutputView($request);
        } else {
            throw new Exception("You don't have permission to do that", 403);
        }
    }

    public function createComment($request, $db)
    {
        $comment             = array();
        $comment['event_id'] = $this->getItemId($request);
        if (empty($comment['event_id'])) {
            throw new Exception(
                "POST expects a comment representation sent to a specific event URL",
                400
            );
        }

        // no anonymous comments over the API
        if (! isset($request->user_id) || empty($request->user_id)) {
            throw new Exception('You must log in to comment');
        }
        $user_mapper = new UserMapper($db, $request);
        $users       = $user_mapper->getUserById($request->user_id);
        $thisUser    = $users['users'][0];

        $rating = $request->getParameter('rating', false);
        if (false === $rating) {
            throw new Exception('The field "rating" is required', 400);
        } elseif (false === is_numeric($rating)) {
            throw new Exception('The field "rating" must be a number', 400);
        }

        $commentText = $request->getParameter('comment');
        if (empty($commentText)) {
            throw new Exception('The field "comment" is required', 400);
        }

        // Get the API key reference to save against the comment
        $oauth_model   = $request->getOauthModel($db);
        $consumer_name = $oauth_model->getConsumerName($request->getAccessToken());

        $comment['user_id'] = $request->user_id;
        $comment['comment'] = $commentText;
        $comment['rating']  = $rating;
        $comment['cname']   = $thisUser['full_name'];
        $comment['source']  = $consumer_name;

        // run it by akismet if we have it
        if (isset($this->config['akismet']['apiKey'], $this->config['akismet']['blog'])) {
            $spamCheckService = new SpamCheckService(
                $this->config['akismet']['apiKey'],
                $this->config['akismet']['blog']
            );
            $isValid          = $spamCheckService->isCommentAcceptable(
                $comment,
                $request->getClientIP(),
                $request->getClientUserAgent()
            );
            if (! $isValid) {
                throw new Exception("Comment failed spam check", 400);
            }
        }

        $event_mapper   = new EventMapper($db, $request);
        $comment_mapper = new EventCommentMapper($db, $request);

        // should rating be allowed?
        if ($comment_mapper->hasUserRatedThisEvent($comment['user_id'], $comment['event_id']) ||
            $event_mapper->isUserAHostOn($comment['user_id'], $comment['event_id'])) {
            // a user can only rate once and event hosts cannot rate their own event
            $comment['rating'] = 0;
        } else {
            // if a user has never rated before and is not an event host the rating should be between 1 and 5
            if ($rating < 1 || $rating > 5) {
                throw new Exception('The field "rating" must be a number (1-5)', 400);
            }
        }

        try {
            $new_id = $comment_mapper->save($comment);
        } catch (Exception $e) {
            // just throw this again but with a 400 status code
            throw new Exception($e->getMessage(), 400);
        }

        // Update the cache count for the number of event comments on this event
        $event_mapper->cacheCommentCount($comment['event_id']);

        $uri = $request->base . '/' . $request->version . '/event_comments/' . $new_id;

        $view = $request->getView();
        $view->setHeader('Location', $uri);
        $view->setResponseCode(201);
    }

    public function reportComment($request, $db)
    {
        // must be logged in to report a comment
        if (! isset($request->user_id) || empty($request->user_id)) {
            throw new Exception('You must log in to report a comment', 401);
        }

        $comment_mapper = new EventCommentMapper($db, $request);

        $commentId = $this->getItemId($request);
        $commentInfo = $comment_mapper->getCommentInfo($commentId);
        if (false === $commentInfo) {
            throw new Exception('Comment not found', 404);
        }
        
        $eventId = $commentInfo['event_id'];

        $comment_mapper->userReportedComment($commentId, $request->user_id);

        // notify event admins
        $comment      = $comment_mapper->getCommentById($commentId, true, true);
        $event_mapper = new EventMapper($db, $request);
        $recipients   = $event_mapper->getHostsEmailAddresses($eventId);
        $event        = $event_mapper->getEventById($eventId, true, true);

        $emailService = new EventCommentReportedEmailService($this->config, $recipients, $comment, $event);
        $emailService->sendEmail();

        // send them to the comments collection
        $uri = $request->base . '/' . $request->version . '/events/' . $eventId . "/comments";

        $view = $request->getView();
        $view->setHeader('Location', $uri);
        $view->setResponseCode(202);
    }

    /**
     * Moderate a reported comment.
     *
     * This action is performed by a user that has administrative rights to the
     * event that this comment is for. The user provides a decision on the
     * report. That is, the user can approve the report which means that the
     * comment remains hidden from view or the user can deny the report which
     * means that the comment is viewable again.
     *
     * @param Request $request the request
     * @param PDO $db the database adapter
     */
    public function moderateReportedComment($request, $db)
    {
        // must be logged in
        if (! isset($request->user_id) || empty($request->user_id)) {
            throw new Exception('You must log in to moderate a comment', 401);
        }

        $comment_mapper = new EventCommentMapper($db, $request);

        $commentId = $this->getItemId($request);
        $commentInfo = $comment_mapper->getCommentInfo($commentId);
        if (false === $commentInfo) {
            throw new Exception('Comment not found', 404);
        }

        $event_mapper = new EventMapper($db, $request);
        $event_id = $commentInfo['event_id'];
        if (false == $event_mapper->thisUserHasAdminOn($event_id)) {
            throw new Exception("You don't have permission to do that", 403);
        }

        $decision = $request->getParameter('decision');
        if (!in_array($decision, ['approved', 'denied'])) {
            throw new Exception('Unexpected decision', 400);
        }

        $comment_mapper->moderateReportedComment($decision, $commentId, $request->user_id);

        $uri = $request->base  . '/' . $request->version . '/events/' . $event_id . "/comments";

        $view = $request->getView();
        $view->setHeader('Location', $uri);
        $view->setResponseCode(204);
    }
}
