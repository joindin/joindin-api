<?php

class TalksController extends ApiController {
    public function handle(Request $request, $db) {
        if($request->getVerb() == 'GET') {
            return $this->getAction($request, $db);
        } elseif($request->getVerb() == 'POST') {
            return $this->postAction($request, $db);
        } elseif ($request->getVerb() == 'DELETE') {
            return $this->deleteAction($request, $db);
        } else {
            throw new Exception("method not supported");
        }
        return false;
    }

	public function getAction($request, $db) {
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
            } else if (isset($request->parameters['title'])) {
                $keyword = filter_var($request->parameters['title'], FILTER_SANITIZE_STRING);

                $mapper = new TalkMapper($db, $request);
                $list = $mapper->getTalksByTitleSearch($keyword, $resultsperpage, $start, $verbose);
            } else {
                // listing makes no sense
                throw new Exception('Generic talks listing not supported', 405);
            }
        }

        return $list;
	}

    public function postAction($request, $db) {
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
                        if (isset($this->config['akismet']['apiKey'], $this->config['akismet']['blog'])) {
                            $spamCheckService = new SpamCheckService(
                                $this->config['akismet']['apiKey'],
                                $this->config['akismet']['blog']
                            );
                            $isValid = $spamCheckService->isCommentAcceptable(
                                $data,
                                $request->getClientIP(),
                                $request->getClientUserAgent()
                            );
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

            // incoming data
            $talk   = array();
            $errors = array();

            $talk['event_id'] = filter_var($request->getParameter('event_id'), FILTER_SANITIZE_NUMBER_INT);
            if (empty($talk['event_id'])) {
                $errors[] = '"event_id" is a required field';
            }

            $talk['title'] = filter_var($request->getParameter("title"), FILTER_SANITIZE_STRING);
            var_Dump($request->parameters);
            if(empty($talk['title'])) {
                $errors[] = "'title' is a required field";
            }

            $talk['url_friendly_title'] = filter_var($request->getParameter("ur_friendly_title"), FILTER_SANITIZE_STRING);
            if(empty($talk['url_friendly_title'])) {
                $talk['url_friendly_title'] = $talk['title'];
            }

            $talk['description']  = filter_var($request->getParameter("description"), FILTER_SANITIZE_STRING);
            if (empty($talk['description'])) {
                $errors[] = "'description' is a required field";
            }

            $talk['type']  = filter_var($request->getParameter("type"), FILTER_SANITIZE_STRING);
            if (empty($talk['type'])) {
                $errors[] = "'type' is a required field";
            }

            if ($errors) {
                throw new Exception(implode(". ", $errors), 400);
            }


            $talk['date'] = (new \DateTime($request->getParameter("start_date")))->format('U');

            $talk['duration'] = filter_var($request->getParameter('duration'), FILTER_SANITIZE_NUMBER_INT);
            if (empty($talk['type'])) {
                $talk['duration'] = 60;
            }

            $talk['language'] = filter_var($request->getParameter('language'), FILTER_SANITIZE_STRING);
            if (empty($talk['language'])) {
                $talk['language'] = 'english';
            }

            $incoming_speakers_list = $request->getParameter('speakers');
            if(is_array($incoming_speakers_list)) {
                $talk['speakers'] = array_map(function($speaker){
                    $speaker = filter_var($speaker, FILTER_SANITIZE_STRING);
                    $speaker = trim($speaker);
                    return $speaker;
                }, $incoming_speakers_list);
            }

            $talk_mapper = new TalkMapper($db, $request);
            $new_id = $talk_mapper->save($talk);

            $uri = $request->base . '/' . $request->version . '/talks/' . $new_id;
            header("Location: " . $uri, true, 201);
            exit;
        //    throw new Exception("method not yet supported - sorry");
        }
    }

    public function putAction($request, $db)
    {
        if(!isset($request->user_id)) {
            throw new Exception("You must be logged in to edit data", 400);
        }
        $talk_id = $this->getItemId($request);


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
