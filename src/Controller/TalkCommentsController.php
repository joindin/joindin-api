<?php

// @codingStandardsIgnoreStart
namespace Joindin\Api\Controller;

use Exception;
use Joindin\Api\Model\EventMapper;
use Joindin\Api\Model\TalkCommentMapper;
use Joindin\Api\Service\CommentReportedEmailService;
use PDO;
use Joindin\Api\Request;
use Teapot\StatusCode\Http;

class TalkCommentsController extends BaseApiController
    // @codingStandardsIgnoreEnd
{
    /**
     * @var TalkCommentMapper
     */
    private $commentMapper;

    public function getComments(Request $request, PDO $db): false|array
    {
        $commentId = $this->getItemId($request, 'Comment not found');

        // verbosity
        $verbose = $this->getVerbosity($request);

        if (!$commentId) {
            return false;
        }

        $mapper = $this->getCommentMapper($request, $db);

        $list = $mapper->getCommentById($commentId, $verbose);

        if (false === $list) {
            throw new Exception('Comment not found', Http::NOT_FOUND);
        }

        return $list;
    }

    public function getReported(Request $request, PDO $db): array
    {
        $eventId = $this->getItemId($request, 'Event not found');

        $eventMapper   = new EventMapper($db, $request);
        $commentMapper = $this->getCommentMapper($request, $db);

        if (!isset($request->user_id) || empty($request->user_id)) {
            throw new Exception("You must log in to do that", Http::UNAUTHORIZED);
        }

        if (!$eventMapper->thisUserHasAdminOn($eventId)) {
            throw new Exception("You don't have permission to do that", Http::FORBIDDEN);
        }

        $list = $commentMapper->getReportedCommentsByEventId($eventId);

        return $list->getOutputView($request);
    }

    public function reportComment(Request $request, PDO $db): void
    {
        // must be logged in to report a comment
        if (!isset($request->user_id) || empty($request->user_id)) {
            throw new Exception('You must log in to report a comment', Http::UNAUTHORIZED);
        }

        $commentMapper = $this->getCommentMapper($request, $db);

        $commentId   = $this->getItemId($request, 'Comment not found');
        $commentInfo = $commentMapper->getCommentInfo($commentId);

        if (false === $commentInfo) {
            throw new Exception('Comment not found', Http::NOT_FOUND);
        }

        $talkId  = $commentInfo['talk_id'];
        $eventId = $commentInfo['event_id'];

        $commentMapper->userReportedComment($commentId, $request->user_id);

        // notify event admins
        $comment = $commentMapper->getCommentById($commentId, true, true);
        if (!$comment) {
            throw new Exception('Reported comment not found');
        }
        $eventMapper  = new EventMapper($db, $request);
        $recipients   = $eventMapper->getHostsEmailAddresses($eventId);
        $event        = $eventMapper->getEventById($eventId, true, true);
        if (!$event) {
            throw new Exception('Reported event not found');
        }

        $emailService = new CommentReportedEmailService($this->config, $recipients, $comment, $event);
        $emailService->sendEmail();

        // send them to the comments collection
        $uri = $request->base . '/' . $request->version . '/talks/' . $talkId . "/comments";

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
        if (!isset($request->user_id) || empty($request->user_id)) {
            throw new Exception('You must log in to moderate a comment', Http::UNAUTHORIZED);
        }

        $commentMapper = $this->getCommentMapper($request, $db);

        $commentId   = $this->getItemId($request, 'Comment not found');
        $commentInfo = $commentMapper->getCommentInfo($commentId);

        if (false === $commentInfo) {
            throw new Exception('Comment not found', Http::NOT_FOUND);
        }

        $eventMapper = new EventMapper($db, $request);
        $eventId     = $commentInfo['event_id'];

        if (false == $eventMapper->thisUserHasAdminOn($eventId)) {
            throw new Exception("You don't have permission to do that", Http::FORBIDDEN);
        }

        $decision = $request->getStringParameter('decision');

        if (!in_array($decision, ['approved', 'denied'])) {
            throw new Exception('Unexpected decision', Http::BAD_REQUEST);
        }

        $commentMapper->moderateReportedComment($decision, $commentId, $request->user_id);

        $talkId  = $commentInfo['talk_id'];
        $uri     = $request->base . '/' . $request->version . '/talks/' . $talkId . "/comments";

        $view = $request->getView();
        $view->setHeader('Location', $uri);
        $view->setResponseCode(Http::NO_CONTENT);
    }

    public function updateComment(Request $request, PDO $db): false|array
    {
        // must be logged in
        if (!isset($request->user_id) || empty($request->user_id)) {
            throw new Exception('You must log in to edit a comment', Http::UNAUTHORIZED);
        }

        $newCommentBody = $request->getStringParameter('comment');

        if (empty($newCommentBody)) {
            throw new Exception('The field "comment" is required', Http::BAD_REQUEST);
        }

        $commentId     = $this->getItemId($request, 'Comment not found');
        $commentMapper = $this->getCommentMapper($request, $db);
        $comment       = $commentMapper->getRawComment($commentId);

        if (false === $comment) {
            throw new Exception('Comment not found', Http::NOT_FOUND);
        }

        if ($comment['user_id'] != $request->user_id) {
            throw new Exception('You are not the comment author', Http::FORBIDDEN);
        }

        $maxCommentEditMinutes = 15;

        if (isset($this->config['limits']['max_comment_edit_minutes'])) {
            $maxCommentEditMinutes = $this->config['limits']['max_comment_edit_minutes'];
        }

        $interval = sprintf('PT%dM', $maxCommentEditMinutes);
        $edit_after_time = (new \DateTimeImmutable())->sub(new \DateInterval($interval));

        if ($comment['created_at'] < $edit_after_time) {
            throw new Exception(
                'Cannot edit the comment after ' . $maxCommentEditMinutes . ' minutes',
                Http::BAD_REQUEST
            );
        }

        $updateSuccess = $commentMapper->updateCommentBody($commentId, $newCommentBody);

        if (false === $updateSuccess) {
            throw new Exception('Comment update failed', Http::INTERNAL_SERVER_ERROR);
        }

        return $commentMapper->getCommentById($commentId);
    }

    private function getCommentMapper(Request $request, PDO $db): TalkCommentMapper
    {
        if ($this->commentMapper === null) {
            $this->commentMapper = new TalkCommentMapper($db, $request);
        }

        return $this->commentMapper;
    }
}
