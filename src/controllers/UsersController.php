<?php

class UsersController extends ApiController {
    public function handle(Request $request, $db) {
        // only GET is implemented so far
        if($request->getVerb() == 'GET') {
            return $this->getAction($request, $db);
        }
        return false;
    }

	public function getAction($request, $db) {
        $user_id = $this->getItemId($request);

        // Are we checking the currently-logged-in user?
        if (!$user_id && !empty($request->url_elements[3]) && $request->url_elements[3] == "me") {
            if ($request->user_id) {
                $user_id = $request->user_id;
            } else {
                // User isn't signed in
                throw new Exception("You must be logged in to request your own user record", 400);
            }
        }

        // verbosity
        $verbose = $this->getVerbosity($request);

        // pagination settings
        $start = $this->getStart($request);
        $resultsperpage = $this->getResultsPerPage($request);

        if(isset($request->url_elements[4])) {
            switch($request->url_elements[4]) {
                case 'talks':
                            $talk_mapper = new TalkMapper($db, $request);
                            $list = $talk_mapper->getTalksBySpeaker($user_id, $resultsperpage, $start, $request, $verbose);
                            break;
                case 'attended':
                            $event_mapper = new EventMapper($db, $request);
                            $list = $event_mapper->getEventsAttendedByUser($user_id, $resultsperpage, $start, $request, $verbose);
                            break;
                default:
                            throw new InvalidArgumentException('Unknown Subrequest', 404);
                            break;
            }
        } else {
            $mapper = new UserMapper($db, $request);
            if($user_id) {
                $list = $mapper->getUserById($user_id, $verbose);
                if(false === $list) {
                    throw new Exception('User not found', 404);
                }
            } else {
                $list = $mapper->getUserList($resultsperpage, $start, $verbose);
            }
        }

        return $list;
	}
}
