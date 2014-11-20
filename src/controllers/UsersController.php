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

        // verbosity
        $verbose = $this->getVerbosity($request);

        // pagination settings
        $start = $this->getStart($request);
        $resultsperpage = $this->getResultsPerPage($request);

        if(isset($request->url_elements[4])) {
            switch($request->url_elements[4]) {
                case 'talks':
                            $talk_mapper = new TalkMapper($db, $request);
                            $list = $talk_mapper->getTalksBySpeaker($user_id, $resultsperpage, $start, $verbose);
                            break;
                case 'hosted':
                            $event_mapper = new EventMapper($db, $request);
                            $list = $event_mapper->getEventsHostedByUser($user_id, $resultsperpage, $start, $verbose);
                            break;
                case 'attended':
                            $event_mapper = new EventMapper($db, $request);
                            $list = $event_mapper->getEventsAttendedByUser($user_id, $resultsperpage, $start, $verbose);
                            break;
                case 'talk_comments':
                            $talkComment_mapper = new TalkCommentMapper($db, $request);
                            $list = $talkComment_mapper->getCommentsByUserId($user_id, $resultsperpage, $start, $verbose);
                            break;
                default:
                            throw new InvalidArgumentException('Unknown Subrequest', 404);
                            break;
            }
        } else {
            $mapper = new UserMapper($db, $request);
            if($user_id) {
                $list = $mapper->getUserById($user_id, $verbose);
                if(count($list['users']) == 0) {
                    throw new Exception('User not found', 404);
                }
            } else {
                if(isset($request->parameters['username'])) {
                    $username = filter_var($request->parameters['username'], FILTER_SANITIZE_STRING);
                    $list = $mapper->getUserByUsername($username, $verbose);
                    if ($list === false) {
                        throw new Exception('Username not found', 404);
                    }
                } else {
                    $list = $mapper->getUserList($resultsperpage, $start, $verbose);
                }
            }
        }

        return $list;
	}
}
