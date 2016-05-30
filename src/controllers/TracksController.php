<?php

class TracksController extends ApiController
{
    public function getAction($request, $db)
    {
        $track_id = $this->getItemId($request);

        // verbosity
        $verbose = $this->getVerbosity($request);

        if ($track_id) {
            $mapper = new TrackMapper($db, $request);
            $list   = $mapper->getTrackById($track_id, $verbose);
            if (false === $list) {
                throw new Exception('Track not found', 404);
            }
        } else {
            // listing makes no sense
            throw new Exception('Generic tracks listing not supported', 405);
        }

        return $list;
    }

    public function editTrack($request, $db)
    {
        $track_id = $this->getItemId($request);

        $track_mapper = new TrackMapper($db, $request);
        $tracks = $track_mapper->getTrackById($track_id, true);
        if (!$tracks) {
            throw new Exception("Track not found", 404);
        }

        $event_mapper = new EventMapper($db, $request);
        $events = $event_mapper->getEventByTrackId($track_id, true, false, false);
        if (!$events || !$events[0]['ID']) {
            throw new Exception("Associated event not found", 404);
        }
        $event_id = $events[0]['ID'];
        if (!$event_mapper->thisUserHasAdminOn($event_id)) {
            throw new Exception('You do not have permission to edit this track', 403);
        }

        // validate fields
        $errors = [];
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
            throw new Exception(implode(". ", $errors), 400);
        }

        $track_mapper->editEventTrack($track, $track_id);

        $uri = $request->base  . '/' . $request->version . '/tracks/' . $track_id;
        header("Location: $uri", true, 204);
        exit;
    }

    public function deleteTrack($request, $db)
    {
        $track_id = $this->getItemId($request);

        $track_mapper = new TrackMapper($db, $request);
        $tracks = $track_mapper->getTrackById($track_id, true);
        if (!$tracks) {
            throw new Exception("Track not found", 404);
        }

        $event_mapper = new EventMapper($db, $request);
        $events = $event_mapper->getEventByTrackId($track_id, true, false, false);
        if (!$events || !$events[0]['ID']) {
            throw new Exception("Associated event not found", 404);
        }
        $event_id = $events[0]['ID'];
        if (!$event_mapper->thisUserHasAdminOn($event_id)) {
            throw new Exception('You do not have permission to delete this track', 403);
        }

        $track_mapper->deleteEventTrack($track_id);

        header("Content-Length: 0", null, 204);
        exit;
    }
}
