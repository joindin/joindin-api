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
            throw new Exception("You must be logged in to create data", 401);
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

                    $talk_mapper = new TalkMapper($db, $request);
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

                        // should rating be allowed?
                        if ($comment_mapper->hasUserRatedThisTalk($data['user_id'], $data['talk_id'])) {
                            $data['rating'] = 0;
                        }
                        if ($talk_mapper->isUserASpeakerOnTalk($data['talk_id'], $data['user_id'])) {
                            // speakers cannot cannot rate their own talk
                            $data['rating'] = 0;
                        }

                        $new_id = $comment_mapper->save($data);
                    } catch (Exception $e) {
                        // just throw this again but with a 400 status code
                        throw new Exception($e->getMessage(), 400);
                    }

                    if($new_id) {
                        $comment = $comment_mapper->getCommentById($new_id);
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
            throw new Exception('This should never have happened, sorry', 500);
        }
    }

    /**
     * Create a new Talk.
     *
     * This method creates a new talk after being called via the URL
     * "/events/<ID>/talks" or "/talks" with the event-id being part of the
     * POST-values
     *
     * @param Request $request
     * @param DB $db
     *
     * @throws Exception on different occasions
     * @return array|bool
     */
    public function postAction($request, $db)
    {
        // Set the event-ID either via URL or via POST-Data depending on how the
        // method was called.
        // When it's called via a POST to /talks/ the event-id is part of the
        // POST-data, when it's called via POST to /events/<id>/talks/ the
        // event-id is part of the URL but will be overwritten by POST-data
        $event_id = null;
        if ($request->url_elements[2] == 'events' && isset($request->url_elements[3])) {
            $event_id = $request->url_elements[3];
        }
        $event_id = filter_var(
            $request->getParameter('event_id', $event_id),
            FILTER_SANITIZE_NUMBER_INT
        );

        $event_mapper = new EventMapper($db, $request);
        if (! $event_mapper->thisUserHasAdminOn($event_id)) {
            throw new Exception('You do not have permissions to add a talk to this event', 403);
        }
        $eventlist = $event_mapper->getEventById($event_id);
        if (count($eventlist['events']) < 1) {
            throw new Exception('Event not found', 404);
        }
        $event = $eventlist['events'][0];

        // incoming data
        $talk   = array();
        $errors = array();

        $talk['event_id'] = $event_id;
        if (empty($talk['event_id'])) {
            $errors[] = '"event_id" is a required field';
        }

        $talk['talk_title'] = filter_var(
            $request->getParameter("talk_title"),
            FILTER_SANITIZE_STRING
        );
        if(empty($talk['talk_title'])) {
            $errors[] = "The 'talk_title' field is required";
        }

        $talk['talk_description']  = filter_var(
            $request->getParameter("talk_description"),
            FILTER_SANITIZE_STRING
        );
        if (empty($talk['talk_description'])) {
            $errors[] = "The 'talk_description' field is required";
        }

        $talk['type']  = filter_var(
            $request->getParameter("type"),
            FILTER_SANITIZE_STRING
        );
        if (empty($talk['type'])) {
            $errors[] = "The 'type' field is required";
        }

        $talk_mapper = new TalkMapper($db, $request);
        if ($talk['type'] && ! in_array($talk['type'], $talk_mapper->getCategories())) {
            $errors[] = sprintf(
                'The given talk-category "%s" isn\'t recognized',
                $talk['type']
            );
        }

        if ($errors) {
            throw new Exception(implode(". ", $errors), 400);
        }

        $talk['language'] = filter_var(
            $request->getParameter('language'),
            FILTER_SANITIZE_STRING
        );
        if (empty($talk['language'])) {
            $talk['language'] = 'English - UK';
        }
        // When the language doesn't exist, the talk will not be found
        $language_mapper = new LanguageMapper($db, $request);
        if (! in_array($talk['language'], $language_mapper->getLanguageList(100))) {
            $errors[] = sprintf('The language "%s" isn\'t known', $talk['language']);
        }

        $start_date = filter_var(
            $request->getParameter('start_date'),
            FILTER_SANITIZE_STRING
        );
        if (empty($start_date)) {
            throw new Exception('Please give the date and time of the talk', 400);
        }
        $tz = new DateTimeZone($event['tz_continent'] . '/' . $event['tz_place']);
        $talk['start_date'] = (new DateTime($start_date, $tz))->format('U');

        $talk['duration'] = filter_var(
            $request->getParameter('duration'),
            FILTER_SANITIZE_NUMBER_INT
        );
        if (empty($talk['duration'])) {
            $talk['duration'] = 60;
        }

        $talk['slides_link'] = filter_var(
            $request->getParameter('slides_link'),
            FILTER_SANITIZE_URL
        );

        $new_id = $talk_mapper->createTalk($talk);
        $event_mapper->cacheTalkCount($talk['event_id']);

        $incoming_speakers_list = (array) $request->getParameter('speakers');
        $speakers = array_map(function($speaker){
            $speaker = filter_var($speaker, FILTER_SANITIZE_STRING);
            $speaker = trim($speaker);
            return $speaker;
        }, $incoming_speakers_list);

        foreach($speakers as $speaker) {
            $talk['speakers'][] = filter_var($speaker, FILTER_SANITIZE_STRING);
        }

        $uri = $request->base . '/' . $request->version . '/talks/' . $new_id;
        header("Location: " . $uri, true, 201);

        $new_talk = $talk_mapper->getTalkById($new_id);
        return $new_talk;
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
