<?php

class TalksController extends ApiController {
    public function handle(Request $request, $db) {
        switch ($request->getVerb()) {
            case 'GET':
                return $this->getAction($request, $db);
            case 'POST':
                return $this->postAction($request, $db);
            case 'PUT':
                return $this->putAction($request, $db);
            case 'DELETE':
                return $this->deleteAction($request, $db);
        }

        throw new Exception('method not supported');
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

        if($request->url_elements[2] == 'talks' && isset($request->url_elements[4])) {
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

                        // should rating be allowed?
                        if ($comment_mapper->hasUserRatedThisTalk($data['user_id'], $data['talk_id'])) {
                            $data['rating'] = 0;
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

            // Set the event-ID either via URL or via POST-Data depending on how the
            // method was called.
            // When it's called via a POST to /talks/ the event-id is part of the
            // POST-data, when it's called via POST to /events/<id>/talks/ the
            // event-id is part of the URL but will be overwritten by POST-data
            $event_id = null;
            if ($request->url_elements[2] == 'events' && isset($request->url_elements[3])) {
                $event_id = $request->url_elements[3];
            }
            var_dump($event_id);
            $event_id = filter_var($request->getParameter('event_id', $event_id), FILTER_SANITIZE_NUMBER_INT);
            $event_mapper = new EventMapper($db, $request);
            if (! $event_mapper->thisUserHasAdminOn($event_id)) {
                throw new Exception('You are not an host for this event', 403);
            }

            // incoming data
            $talk   = array();
            $errors = array();

            $talk['event_id'] = $event_id;
            if (empty($talk['event_id'])) {
                $errors[] = '"event_id" is a required field';
            }

            $talk['talk_title'] = filter_var($request->getParameter("talk_title"), FILTER_SANITIZE_STRING);
            if(empty($talk['talk_title'])) {
                $errors[] = "'talk_title' is a required field";
            }

            $talk['url_friendly_talk_title'] = filter_var($request->getParameter("ur_friendly_talk_title"), FILTER_SANITIZE_STRING);
            if(empty($talk['url_friendly_talk_title'])) {
                $talk['url_friendly_talk_title'] = $talk['talk_title'];
            }

            $talk['talk_description']  = filter_var($request->getParameter("talk_description"), FILTER_SANITIZE_STRING);
            if (empty($talk['talk_description'])) {
                $errors[] = "'talk_description' is a required field";
            }

            $talk['type']  = filter_var($request->getParameter("type"), FILTER_SANITIZE_STRING);
            if (empty($talk['type'])) {
                $errors[] = "'type' is a required field";
            }
            // TODO @heiglandreas check whether the type already exists in the database.

            if ($errors) {
                throw new Exception(implode(". ", $errors), 400);
            }


            $talk['start_date'] = (new \DateTime($request->getParameter("start_date")))->format('U');

            $talk['duration'] = filter_var(
                $request->getParameter('duration'),
                FILTER_SANITIZE_NUMBER_INT
            );
            if (empty($talk['duration'])) {
                $talk['duration'] = 60;
            }

            $talk['language'] = filter_var($request->getParameter('language'), FILTER_SANITIZE_STRING);
            if (empty($talk['language'])) {
                $talk['language'] = 'English - UK';
            }
            // TODO: @heiglandreas Check whether the language actually exists in the database
            // When the language doesn't exist, the talk will not be found

            $talk['slides_link'] = filter_var($request->getParameter('slides_link'), FILTER_SANITIZE_URL);

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

    /**
     * Edit a talk
     *
     * This action expects the following parameters:
     *
     * * int talk_id
     * * int event_id
     * * string title
     * * string url_friendly_talk_title
     * * string description
     * * string language (has to be a value from the langauges-table
     * * string slides_link (has to be a URL)
     * * string type (has to be a value from the categories-table
     * * int duration
     * * string date (will be parsed as date)
     * * string[] speakers (speaker names as the fullnames in the user-table)
     *
     * Users with admin-rights on the talk (the admins as well as the event-hosts)
     * can edit all fields, verified speakers will only be able to edit title,
     * description, language and slides-link.
     *
     * @param $request
     * @param $db
     *
     * @throws Exception
     */
    public function putAction($request, $db)
    {
        $errors = array();
        if(!isset($request->user_id)) {
            throw new Exception("You must be logged in to edit data", 400);
        }
        $talk_id = $this->getItemId($request);

        if (isset($request->url_elements[4])) {
            // Not editing the talk but something "below"
            throw new Exception('Operation not supported, sorry', 404);
        }

        $talk_mapper = new TalkMapper($db, $request);

        $existing_talk = $talk_mapper->getTalkById($talk_id, true);
        if (! $existing_talk || $existing_talk['meta']['count'] < 1) {
            throw new Exception(sprintf(
                'There is no talk with ID "%s"',
                $talk_id
            ), 404);
        }
        $existing_talk = $existing_talk['talks'][0];

        $isAdmin   = $talk_mapper->thisUserHasAdminOn($talk_id);
        $isSpeaker = $talk_mapper->thisUserIsSpeakerOn($talk_id);
        if (! $isAdmin && ! $isSpeaker) {
            throw new Exception('You are not entitled to edit this entry', 403);
        }

        $eUri = explode('/', $existing_talk['event_uri']);
        $existing_talk['event_id'] = $eUri[(count($eUri)-1)];

        $talk = array(
            'talk_id' => $talk_id,
            'event_id' => $existing_talk['event_id']
        );

        $talk['talk_title'] = filter_var($request->getParameter("talk_title"), FILTER_SANITIZE_STRING);
        if(empty($talk['talk_title'])) {
            $errors[] = "'title' is a required field";
        }

        $talk['talk_description']  = filter_var($request->getParameter("talk_description"), FILTER_SANITIZE_STRING);
        if (empty($talk['talk_description'])) {
            $errors[] = "'description' is a required field";
        }

        $talk['language'] = filter_var($request->getParameter('language'), FILTER_SANITIZE_STRING);
        if (empty($talk['language'])) {
            $talk['language'] = 'English - UK';
        }
        if (! in_array($talk['language'], $talk_mapper->getLanguages())) {
            $errors[] = sprintf('The language "%s" isn\'t known', $talk['language']);
        }
        $talk['slides_link'] = filter_var($request->getParameter('slides_link'), FILTER_SANITIZE_URL);

        if (! $isAdmin) {
            $talk['url_friendly_talk_title'] = $existing_talk['url_friendly_talk_title'];
            $talk['type']               = $existing_talk['type'];
            $talk['duration']           = $existing_talk['duration'];
            $talk['start_date']               = (new \DateTime($existing_talk['start_date']))->format('U');
        } else {

            $talk['url_friendly_talk_title'] = filter_var(
                $request->getParameter("url_friendly_talk_title"),
                FILTER_SANITIZE_STRING
            );
            if (empty($talk['url_friendly_talk_title'])) {
                $talk['url_friendly_talk_title'] = $talk['talk_title'];
            }

            $talk['type'] = filter_var(
                $request->getParameter("type"),
                FILTER_SANITIZE_STRING
            );
            if (empty($talk['type'])) {
                $errors[] = "'type' is a required field";
            }

            if (! in_array($talk['type'], $talk_mapper->getCategories())) {
                $errors[] = sprintf(
                    'The given talk-category "%s" isn\'t recognized',
                    $talk['type']
                );
            }

            $talk['start_date'] = (new \DateTime($request->getParameter("start_date")))->format('U');

            $talk['duration'] = filter_var(
                $request->getParameter('duration'),
                FILTER_SANITIZE_NUMBER_INT
            );

            if (empty($talk['duration'])) {
                $talk['duration'] = 60;
            }

            $incoming_speakers_list = $request->getParameter('speakers');
            if(is_array($incoming_speakers_list)) {
                $talk['speakers'] = array_map(function($speaker){
                    $speaker = filter_var($speaker, FILTER_SANITIZE_STRING);
                    $speaker = trim($speaker);
                    return $speaker;
                }, $incoming_speakers_list);
            }
        }

        if ($errors) {
            throw new Exception(implode(". ", $errors), 400);
        }

        $new_id = $talk_mapper->edit($talk, $talk_id);

        $uri = $request->base . '/' . $request->version . '/talks/' . $new_id;
        header("Location: " . $uri, true, 201);
        exit;
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
