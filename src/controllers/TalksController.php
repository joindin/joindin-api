<?php

class TalksController extends ApiController {

    /**
     * {@inheritdoc}
     */
    public function handle(Request $request, $db) {
        if($request->getVerb() == 'GET') {
            return $this->getAction($request, $db);
        } elseif($request->getVerb() == 'POST') {
            return $this->postAction($request, $db);
        } elseif ($request->getVerb() == 'DELETE') {
            return $this->deleteAction($request, $db);
        } else {
            throw new Exception("method not supported", 415);
        }
    }

	protected function getAction($request, $db) {
        $talk_id = $this->getItemId($request);

        // verbosity
        $verbose = $this->getVerbosity($request);

        // pagination settings
        $start = $this->getStart($request);
        $resultsperpage = $this->getResultsPerPage($request);

        $list = array();
        if(isset($request->url_elements[4])) {
            switch ($request->url_elements[4]) {
                case 'comments':
                    $comment_mapper = new TalkCommentMapper($db, $request);
                    $list = $comment_mapper->getCommentsByTalkId($talk_id, $resultsperpage, $start, $verbose);
                    break;
                case 'starred':
                    $mapper = new TalkMapper($db, $request);
                    $list = $mapper->getUserStarred($talk_id, $request->user_id);
                    break;
            }
        } else {
            if($talk_id) {
                $list = $this->getTalkById($db, $request, $talk_id, $verbose);
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
        if(!isset($request->user_id)) {
            throw new Exception("You must be logged in to create data", 400);
        }
        $talk_id = $this->getItemId($request);

        if(isset($request->url_elements[4])) {
            switch($request->url_elements[4]) {
                case "comments":
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
                    $consumer_name = $oauth_model->getConsumerName($request->getAccessToken());

                    $comment_mapper = new TalkCommentMapper($db, $request);
                    $data['user_id'] = $request->user_id;
                    $data['talk_id'] = $talk_id;
                    $data['comment'] = $comment;
                    $data['rating'] = $rating;
                    $data['private'] = $private;
                    $data['source'] = $consumer_name;

                    try {
                        // run it by akismet if we have it
                        if (isset($this->config['akismet']['apiKey'])) {
                            $spamCheckService = new SpamCheckService($this->config['akismet']['apiKey']);
                            $isValid = $spamCheckService->isCommentAcceptable($data);
                            if (!$isValid) {
                                throw new Exception("Comment failed spam check", 400);
                            }
                        }

                        $new_id = $comment_mapper->save($data);
                    } catch (Exception $e) {
                        // just throw this again but with a 400 status code
                        throw new Exception($e->getMessage(), 400);
                    }

                    if($new_id) {
                        $comment = $comment_mapper->getCommentById($new_id);
                        $talk_mapper = new TalkMapper($db, $request);
                        $talk = $talk_mapper->getTalkById($talk_id);
                        $speakers = $talk_mapper->getSpeakerEmailsByTalkId($talk_id);
                        $recipients = array();
                        foreach($speakers as $person) {
                            $recipients[] = $person['email'];
                        }
                        $emailService = new TalkCommentEmailService($this->config, $recipients, $talk, $comment);
                        $emailService->sendEmail();
                        $uri = $request->base . '/' . $request->version . '/talk_comments/' . $new_id;
                        header("Location: " . $uri, true, 201);
                        exit;
                    } else {
                        throw new Exception("The comment could not be stored", 400);
                    }
                case 'starred':
                    // the body of this request is completely irrelevant
                    // The logged in user *is* attending the talk.  Use DELETE to unattend
                    $talk_mapper = new TalkMapper($db, $request);
                    $talk_mapper->setUserStarred($talk_id, $request->user_id);
                    header("Location: " . $request->base . $request->path_info, NULL, 201);
                    exit;
                default:
                    throw new Exception("Operation not supported, sorry", 404);
            }
        } else {
            throw new Exception("method not yet supported - sorry");
        }
    }

    public function deleteAction($request, $db) {
        if(!isset($request->user_id)) {
            throw new Exception("You must be logged in to delete data", 400);
        }
        if(isset($request->url_elements[4])) {
            switch($request->url_elements[4]) {
                case 'starred':
                    $talk_id = $this->getItemId($request);
                    $talk_mapper = new TalkMapper($db, $request);
                    $talk_mapper->setUserNonStarred($talk_id, $request->user_id);
                    header("Location: " . $request->base . $request->path_info, NULL, 200);
                    exit;
                default:
                    throw new Exception("Operation not supported, sorry", 404);
            }
        } else {
            // delete the talk
            $talk_id = $this->getItemId($request);
            $talk_mapper = new TalkMapper($db, $request);
            $list = $talk_mapper->getTalkById($talk_id);
            if(false === $list) {
                // talk isn't there so it's as good as deleted
                header("Content-Length: 0", NULL, 204);
                exit; // no more content
            }

            $is_admin = $talk_mapper->thisUserHasAdminOn($talk_id);
            if(!$is_admin) {
                throw new Exception("You do not have permission to do that", 400);
            }

            $talk_mapper->delete($talk_id);
            header("Content-Length: 0", NULL, 204);
            exit; // no more content
        }
    }

    protected function getTalkById($db, $request, $talk_id, $verbose = false)
    {
        $mapper = new TalkMapper($db, $request);
        $list = $mapper->getTalkById($talk_id, $verbose);
        if(false === $list) {
            throw new Exception('Talk not found', 404);
        }

        return $list;
    }

}
