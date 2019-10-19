<?php

namespace Joindin\Api\Controller;

use Exception;
use Joindin\Api\Model\EventMapper;
use Joindin\Api\Model\TrackMapper;
use PDO;
use Joindin\Api\Request;
use Teapot\StatusCode\Http;

class TracksController extends BaseApiController
{
    public function getAction(Request $request, PDO $db)
    {
        $track_id = $this->getItemId($request);

        // verbosity
        $verbose = $this->getVerbosity($request);

        if ($track_id) {
            $mapper = new TrackMapper($db, $request);
            $list   = $mapper->getTrackById($track_id, $verbose);
            if (false === $list) {
                throw new Exception('Track not found', Http::NOT_FOUND);
            }
        } else {
            // listing makes no sense
            throw new Exception('Generic tracks listing not supported', Http::METHOD_NOT_ALLOWED);
        }

        return $list;
    }

    public function editTrack(Request $request, PDO $db)
    {
        // Check for login
        if (!isset($request->user_id)) {
            throw new Exception("You must be logged in to edit this track", Http::UNAUTHORIZED);
        }

        $track_id = $this->getItemId($request);

        $track_mapper = new TrackMapper($db, $request);
        $tracks       = $track_mapper->getTrackById($track_id, true);
        if (!$tracks) {
            throw new Exception("Track not found", Http::NOT_FOUND);
        }

        $event_mapper = new EventMapper($db, $request);
        $events       = $event_mapper->getEventByTrackId($track_id, true, false, false);
        if (!$events || ! $events[0]['ID']) {
            throw new Exception("Associated event not found", Http::NOT_FOUND);
        }
        $event_id = $events[0]['ID'];
        if (!$event_mapper->thisUserHasAdminOn($event_id)) {
            throw new Exception('You do not have permission to edit this track', Http::FORBIDDEN);
        }

        // validate fields
        $errors              = [];
        $track['track_name'] = filter_var(
            $request->getParameter("track_name"),
            FILTER_SANITIZE_STRING,
            FILTER_FLAG_NO_ENCODE_QUOTES
        );
        if (empty($track['track_name'])) {
            $errors[] = "'track_name' is a required field";
        }
        $track['track_description'] = filter_var(
            $request->getParameter("track_description"),
            FILTER_SANITIZE_STRING,
            FILTER_FLAG_NO_ENCODE_QUOTES
        );
        if (empty($track['track_description'])) {
            $errors[] = "'track_description' is a required field";
        }
        if ($errors) {
            throw new Exception(implode(". ", $errors), Http::BAD_REQUEST);
        }

        $track_mapper->editEventTrack($track, $track_id);

        $uri = $request->base . '/' . $request->version . '/tracks/' . $track_id;

        $view = $request->getView();
        $view->setHeader('Location', $uri);
        $view->setResponseCode(Http::NO_CONTENT);
    }

    public function deleteTrack(Request $request, PDO $db)
    {
        // Check for login
        if (!isset($request->user_id)) {
            throw new Exception("You must be logged in to delete this track", Http::UNAUTHORIZED);
        }

        $track_id = $this->getItemId($request);

        $track_mapper = new TrackMapper($db, $request);
        $tracks       = $track_mapper->getTrackById($track_id, true);
        if (!$tracks) {
            throw new Exception("Track not found", Http::NOT_FOUND);
        }

        $event_mapper = new EventMapper($db, $request);
        $events       = $event_mapper->getEventByTrackId($track_id, true, false, false);
        if (!$events || ! $events[0]['ID']) {
            throw new Exception("Associated event not found", Http::NOT_FOUND);
        }
        $event_id = $events[0]['ID'];
        if (!$event_mapper->thisUserHasAdminOn($event_id)) {
            throw new Exception('You do not have permission to delete this track', Http::FORBIDDEN);
        }

        $track_mapper->deleteEventTrack($track_id);

        $view = $request->getView();
        $view->setHeader('Content-Length', 0);
        $view->setResponseCode(Http::NO_CONTENT);
    }
}
