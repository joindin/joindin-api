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
}
