<?php

class TalksController extends ApiController {
    public function handle(Request $request, $db) {
        if($request->getVerb() == 'GET') {
            return $this->getAction($request, $db);
        } elseif($request->getVerb() == 'POST') {
            return $this->postAction($request, $db);
        } else {
            throw new Exception("method not supported");
        }
        return false;
    }

	protected function getAction($request, $db) {
        $talk_id = $this->getItemId($request);

        // verbosity
        $verbose = $this->getVerbosity($request);

        // pagination settings
        $start = $this->getStart($request);
        $resultsperpage = $this->getResultsPerPage($request);

        if(isset($request->url_elements[4])) {
            // sub elements
            if($request->url_elements[4] == "comments") {
                $comment_mapper = new TalkCommentMapper($db, $request);
                $list = $comment_mapper->getCommentsByTalkId($talk_id, $resultsperpage, $start, $verbose);
            }
        } else {
            if($talk_id) {
                $mapper = new TalkMapper($db, $request);
                $list = $mapper->getTalkById($talk_id, $verbose);
                if(false === $list) {
                    throw new Exception('Talk not found', 404);
                }
            } else {
                // listing makes no sense
                throw new Exception('Generic talks listing not supported', 405);
            }
        }

        return $list;
	}

    protected function postAction($request, $db) {
        $talk_id = $this->getItemId($request);

        if(isset($request->url_elements[4])) {
            // sub elements
            if($request->url_elements[4] == "comments") {
                // no anonymous comments over the API
                if(!isset($request->user_id) || empty($request->user_id)) {
                    throw new Exception('You must log in to comment');
                }

                $comment = $request->getParameter('comment');
                if(empty($comment)) {
                    throw new Exception('The field "comment" is required', 400);
                }

                $rating = $request->getParameter('rating');
                if(empty($rating)) {
                    throw new Exception('The field "rating" is required', 400);
                }

                $private = ($request->getParameter('private') ? 1 : 0);

                // Get the API key reference to save against the comment
                $oauth_model = $request->getOauthModel($db);
                $consumer_name = $oauth_model->getConsumerName($request->access_token);

                $comment_mapper = new TalkCommentMapper($db, $request);
                $data['user_id'] = $request->user_id;
                $data['talk_id'] = $talk_id;
                $data['comment'] = $comment;
                $data['rating'] = $rating;
                $data['private'] = $private;
                $data['source'] = $consumer_name;

                $new_id = $comment_mapper->save($data);
                $uri = $request->base . '/' . $request->version . '/talk_comments/' . $new_id;
                header("Location: " . $uri, true, 201);
                exit;
            }
        } else {
            throw new Exception("method not yet supported - sorry");
        }
    }
}
