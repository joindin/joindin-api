<?php

class Event_commentsController extends ApiController {

    /**
     * {@inheritdoc}
     */
    public function handle(Request $request, $db) {
        // only GET is implemented so far
        if($request->getVerb() == 'GET') {
            return $this->getAction($request, $db);
        }
        throw new Exception('Endpoint not found', 404);
    }

	public function getAction($request, $db) {
        $comment_id = $this->getItemId($request);

        // verbosity
        $verbose = $this->getVerbosity($request);

        // pagination settings
        $start = $this->getStart($request);
        $resultsperpage = $this->getResultsPerPage($request);

        $mapper = new EventCommentMapper($db, $request);
        if($comment_id) {
            $list = $mapper->getCommentById($comment_id, $verbose);
            if(false === $list) {
                throw new Exception('Comment not found', 404);
            }
            return $list;
        } 

        return false;
	}
}
