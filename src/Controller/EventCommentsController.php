<?php

// @codingStandardsIgnoreStart
namespace Joindin\Api\Controller;

use Exception;
use Joindin\Api\Model\EventCommentMapper;
use Joindin\Api\Model\EventMapper;
use Joindin\Api\Model\UserMapper;
use Joindin\Api\Service\EventCommentReportedEmailService;
use Joindin\Api\Service\SpamCheckServiceInterface;
use PDO;
use Joindin\Api\Request;
use Teapot\StatusCode\Http;
use UnexpectedValueException;

class EventCommentsController extends BaseApiController
    // @codingStandardsIgnoreEnd
{
    private SpamCheckServiceInterface $spamCheckService;

    public function __construct(SpamCheckServiceInterface $spamCheckService, array $config = [])
    {
        parent::__construct($config);

        $this->spamCheckService = $spamCheckService;
    }

    public function getComments(Request $request, PDO $db): false|array
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
                throw new Exception('Comment not found', Http::NOT_FOUND);
            }

            return $list;
        }

        return false;
    }

    public function getReported(Request $request, PDO $db): array
    {
        $event_id = $this->getItemId($request);

        if (empty($event_id)) {
            throw new UnexpectedValueException("Event not found", Http::NOT_FOUND);
        }

        // verbosity
        $verbose = $this->getVerbosity($request);

        $event_mapper   = new EventMapper($db, $request);
        $comment_mapper = new EventCommentMapper($db, $request);

        if (!isset($request->user_id) || empty($request->user_id)) {
            throw new Exception("You must log in to do that", Http::UNAUTHORIZED);
        }

        if (!$event_mapper->thisUserHasAdminOn($event_id)) {
            throw new Exception("You don't have permission to do that", Http::FORBIDDEN);
        }

        $list = $comment_mapper->getReportedCommentsByEventId($event_id);

        return $list->getOutputView($request);
    }

    public function createComment(Request $request, PDO $db): void
    {
        $comment             = [];
        $comment['event_id'] = $this->getItemId($request);

        if (empty($comment['event_id'])) {
            throw new Exception(
                "POST expects a comment representation sent to a specific event URL",
                Http::BAD_REQUEST
            );
        }

        // no anonymous comments over the API
        if (!isset($request->user_id) || empty($request->user_id)) {
            throw new Exception('You must log in to comment');
        }
        $user_mapper = new UserMapper($db, $request);
        $users       = $user_mapper->getUserById($request->user_id);

        if (!$users) {
            throw new Exception("Unable retrieve users data", Http::BAD_REQUEST);
        }
        $thisUser    = $users['users'][0];

        $rating = $request->getParameter('rating', '');

        if ('' === $rating) {
            throw new Exception('The field "rating" is required', Http::BAD_REQUEST);
        }

        if (false === is_numeric($rating)) {
            throw new Exception('The field "rating" must be a number', Http::BAD_REQUEST);
        }

        $commentText = $request->getParameter('comment');

        if (! is_string($commentText) || $commentText === '') {
            throw new Exception('The field "comment" is required', Http::BAD_REQUEST);
        }

        // Get the API key reference to save against the comment
        $oauth_model   = $request->getOauthModel($db);
        $consumer_name = $oauth_model->getConsumerName($request->getAccessToken());

        $comment['user_id'] = $request->user_id;
        $comment['comment'] = $commentText;
        $comment['rating']  = $rating;
        $comment['cname']   = $thisUser['full_name'];
        $comment['source']  = $consumer_name;

        $clientIp = $request->getClientIP();
        $userAgent = $request->getClientUserAgent();

        if (
            ! is_string($clientIp)
            || ! is_string($userAgent)
            || ! $this->spamCheckService->isCommentAcceptable($commentText, $clientIp, $userAgent)
        ) {
            throw new Exception("Comment failed spam check", Http::BAD_REQUEST);
        }

        $event_mapper   = new EventMapper($db, $request);
        $comment_mapper = new EventCommentMapper($db, $request);

        // should rating be allowed?
        if (
            $comment_mapper->hasUserRatedThisEvent($comment['user_id'], $comment['event_id']) ||
            $event_mapper->isUserAHostOn($comment['user_id'], $comment['event_id'])
        ) {
            // a user can only rate once and event hosts cannot rate their own event
            $comment['rating'] = 0;
        } else {
            // if a user has never rated before and is not an event host the rating should be between 1 and 5
            if ($rating < 1 || $rating > 5) {
                throw new Exception('The field "rating" must be a number (1-5)', Http::BAD_REQUEST);
            }
        }

        try {
            $new_id = $comment_mapper->save($comment);
        } catch (Exception $e) {
            // just throw this again but with a 400 status code
            throw new Exception($e->getMessage(), Http::BAD_REQUEST);
        }

        // Update the cache count for the number of event comments on this event
        $event_mapper->cacheCommentCount($comment['event_id']);

        $uri = $request->base . '/' . $request->version . '/event_comments/' . $new_id;

        $view = $request->getView();
        $view->setHeader('Location', $uri);
        $view->setResponseCode(Http::CREATED);
    }

    public function reportComment(Request $request, PDO $db): void
    {
        // must be logged in to report a comment
        if (empty($request->user_id)) {
            throw new Exception('You must log in to report a comment', Http::UNAUTHORIZED);
        }

        $comment_mapper = new EventCommentMapper($db, $request);

        $commentId   = $this->getItemId($request);

        if (
            false === $commentId
            || false === ($commentInfo = $comment_mapper->getCommentInfo($commentId))
        ) {
            throw new Exception('Comment not found', Http::NOT_FOUND);
        }

        $eventId = $commentInfo['event_id'];

        $comment_mapper->userReportedComment($commentId, $request->user_id);

        // notify event admins
        $comment      = $comment_mapper->getCommentById($commentId, true, true);

        if (! $comment) {
            throw new Exception('Comment not found', Http::NOT_FOUND);
        }
        $event_mapper = new EventMapper($db, $request);
        $recipients   = $event_mapper->getHostsEmailAddresses($eventId);
        $event        = $event_mapper->getEventById($eventId, true, true);

        if (! $event) {
            throw new Exception('Event not found', Http::NOT_FOUND);
        }

        $emailService = new EventCommentReportedEmailService($this->config, $recipients, $comment, $event);
        $emailService->sendEmail();

        // send them to the comments collection
        $uri = $request->base . '/' . $request->version . '/events/' . $eventId . "/comments";

        $view = $request->getView();
        $view->setHeader('Location', $uri);
        $view->setResponseCode(Http::ACCEPTED);
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
     * @param PDO     $db      the database adapter
     *
     * @throws Exception
     */
    public function moderateReportedComment(Request $request, PDO $db): void
    {
        // must be logged in
        if (empty($request->user_id)) {
            throw new Exception('You must log in to moderate a comment', Http::UNAUTHORIZED);
        }

        $comment_mapper = new EventCommentMapper($db, $request);
        $commentId   = $this->getItemId($request);

        if (
            false === $commentId
            || false === ($commentInfo = $comment_mapper->getCommentInfo($commentId))
        ) {
            throw new Exception('Comment not found', Http::NOT_FOUND);
        }

        $event_mapper = new EventMapper($db, $request);
        $event_id     = $commentInfo['event_id'];

        if (! $event_mapper->thisUserHasAdminOn($event_id)) {
            throw new Exception("You don't have permission to do that", Http::FORBIDDEN);
        }

        $decision = $request->getParameter('decision');

        if (!is_string($decision) || !in_array($decision, ['approved', 'denied'])) {
            throw new Exception('Unexpected decision', Http::BAD_REQUEST);
        }

        $comment_mapper->moderateReportedComment($decision, $commentId, $request->user_id);

        $uri = $request->base . '/' . $request->version . '/events/' . $event_id . "/comments";

        $view = $request->getView();
        $view->setHeader('Location', $uri);
        $view->setResponseCode(Http::NO_CONTENT);
    }
}
