<?php

class EventsController extends ApiController {
    public function handle(Request $request, $db) {
        // only GET is implemented so far
        if($request->getVerb() == 'GET') {
            return $this->getAction($request, $db);
        } elseif ($request->getVerb() == 'POST') {
            return $this->postAction($request, $db);
        } elseif ($request->getVerb() == 'DELETE') {
            return $this->deleteAction($request, $db);
        }
        return false;
    }

	public function getAction($request, $db) {
        $event_id = $this->getItemId($request);

        // verbosity
        $verbose = $this->getVerbosity($request);

        // pagination settings
        $start = $this->getStart($request);
        $resultsperpage = $this->getResultsPerPage($request);

        if(isset($request->url_elements[4])) {
            switch($request->url_elements[4]) {
                case 'talks':
                            $talk_mapper = new TalkMapper($db, $request);
                            $list = $talk_mapper->getTalksByEventId($event_id, $resultsperpage, $start, $request, $verbose);
                            break;
                case 'comments':
                            $event_comment_mapper = new EventCommentMapper($db, $request);
                            $list = $event_comment_mapper->getEventCommentsByEventId($event_id, $resultsperpage, $start, $verbose);
                            break;
                case 'talk_comments':
                            $sort = $this->getSort($request);
                            $talk_comment_mapper = new TalkCommentMapper($db, $request);
                            $list = $talk_comment_mapper->getCommentsByEventId($event_id, $resultsperpage, $start, $verbose, $sort);
                            break;
                case 'attendees':
                            $user_mapper= new UserMapper($db, $request);
                            $list = $user_mapper->getUsersAttendingEventId($event_id, $resultsperpage, $start, $verbose);
                            break;
                case 'attending':
                            $mapper = new EventMapper($db, $request);
                            $list = $mapper->getUserAttendance($event_id, $request->user_id);
                            break;
                case 'tracks':
                            $mapper = new TrackMapper($db, $request);
                            $list = $mapper->getTracksByEventId($event_id, $resultsperpage, $start, $request, $verbose);
                            break;
                default:
                            throw new InvalidArgumentException('Unknown Subrequest', 404);
                            break;
            }
        } else {
            $mapper = new EventMapper($db, $request);
            if($event_id) {
                $list = $mapper->getEventById($event_id, $verbose);
                if(false === $list) {
                    throw new Exception('Event not found', 404);
                }
            } else {
                // check if we're filtering
                if(isset($request->parameters['filter'])) {
                    switch($request->parameters['filter']) {
                        case "hot":
                            $list = $mapper->getHotEventList($resultsperpage, $start, $verbose);
                            break;
                        case "upcoming":
                            $list = $mapper->getUpcomingEventList($resultsperpage, $start, $verbose);
                            break;
                        case "past":
                            $list = $mapper->getPastEventList($resultsperpage, $start, $verbose);
                            break;
                        case "cfp":
                            $list = $mapper->getOpenCfPEventList($resultsperpage, $start, $verbose);
                            break;
                        default:
                            throw new InvalidArgumentException('Unknown event filter', 404);
                            break;
                    }
                } elseif(isset($request->parameters['title'])) {
                    $title = filter_var($request->parameters['title'], FILTER_SANITIZE_STRING);
                    $list  = $mapper->getEventsByTitle($title, $resultsperpage, $start, $verbose);
                    if ($list === false) {
                        throw new Exception('Event not found', 404);
                    }
                } elseif(isset($request->parameters['stub'])) {
                    $stub = filter_var($request->parameters['stub'], FILTER_SANITIZE_STRING);
                    $list = $mapper->getEventByStub($stub, $verbose);
                    if ($list === false) {
                        throw new Exception('Stub not found', 404);
                    }
                } else {
                    $list = $mapper->getEventList($resultsperpage, $start, $verbose);
                }
            }
        }

        return $list;
	}

    public function postAction($request, $db) {
        if(!isset($request->user_id)) {
            throw new Exception("You must be logged in to create data", 400);
        }
        if(isset($request->url_elements[4])) {
            switch($request->url_elements[4]) {
                case 'attending':
                    // the body of this request is completely irrelevant
                    // The logged in user *is* attending the event.  Use DELETE to unattend
                    $event_id = $this->getItemId($request);
                    $event_mapper = new EventMapper($db, $request);
                    $event_mapper->setUserAttendance($event_id, $request->user_id);
                    header("Location: " . $request->base . $request->path_info, NULL, 201);
                    return;
                case 'talks':
                    $talk['event_id'] = $this->getItemId($request);
                    if(empty($talk['event_id'])) {
                        throw new Exception(
                            "POST expects a talk representation sent to a specific event URL", 
                            400
                        );
                    }
                    $event_mapper = new EventMapper($db, $request);
                    $is_admin = $event_mapper->thisUserHasAdminOn($talk['event_id']);
                    if(!$is_admin) {
                        throw new Exception("You do not have permission to add talks to this event", 400);
                    }

                    $talk['title'] = filter_var(
                        $request->getParameter('talk_title'), 
                        FILTER_SANITIZE_STRING
                    );
                    if(empty($talk['title'])) {
                        throw new Exception("The talk title field is required", 400);
                    }
                    $talk['description'] = filter_var(
                        $request->getParameter('talk_description'), 
                        FILTER_SANITIZE_STRING
                    );
                    if(empty($talk['description'])) {
                        throw new Exception("The talk description field is required", 400);
                    }

                    $talk['language'] = filter_var($request->getParameter('language'), FILTER_SANITIZE_STRING);
                    if(empty($talk['language'])) {
                        // default to UK English
                        $talk['language'] = 'English - UK';
                    }

                    $start_date = $request->getParameter('start_date');
                    if(empty($start_date)) {
                        throw new Exception("Please give the date and time of the talk", 400);
                    }
                    $talk['date'] = new DateTime($start_date);

                    $speakers = $request->getParameter('speakers');
                    if(is_array($speakers)) {
                        foreach($speakers as $speaker) {
                            $talk['speakers'][] = filter_var($speaker, FILTER_SANITIZE_STRING);
                        }
                    }
                        
                    $talk_mapper = new TalkMapper($db, $request);
                    $new_id = $talk_mapper->save($talk);

                    // Update the cache count for the number of talks at this event
                    $event_mapper->cacheTalkCount($talk['event_id']);

                    header("Location: " . $request->base . $request->path_info .'/' . $new_id, NULL, 201);
                    $new_talk = $talk_mapper->getTalkById($new_id);
                    return $new_talk;
                case 'comments':
                    $comment = array();
                    $comment['event_id'] = $this->getItemId($request);
                    if(empty($comment['event_id'])) {
                        throw new Exception(
                            "POST expects a comment representation sent to a specific event URL",
                            400
                        );
                    }
                    // no anonymous comments over the API
                    if(!isset($request->user_id) || empty($request->user_id)) {
                        throw new Exception('You must log in to comment');
                    }
                    $user_mapper = new UserMapper($db, $request);
                    $users = $user_mapper->getUserById($request->user_id);
                    $thisUser = $users['users'][0];

                    $commentText = filter_var($request->getParameter('comment'), FILTER_SANITIZE_STRING);
                    if(empty($commentText)) {
                        throw new Exception('The field "comment" is required', 400);
                    }

                    // Get the API key reference to save against the comment
                    $oauth_model = $request->getOauthModel($db);
                    $consumer_name = $oauth_model->getConsumerName($request->getAccessToken());

                    $comment['user_id'] = $request->user_id;
                    $comment['comment'] = $commentText;
                    $comment['cname'] = $thisUser['full_name'];
                    $comment['source'] = $consumer_name;

                    $comment_mapper = new EventCommentMapper($db, $request);
                    $new_id = $comment_mapper->save($comment);

                    // Update the cache count for the number of event comments on this event
                    $event_mapper = new EventMapper($db, $request);
                    $event_mapper->cacheCommentCount($comment['event_id']);

                    $uri = $request->base . '/' . $request->version . '/event_comments/' . $new_id;
                    header("Location: " . $uri, NULL, 201);
                    exit;
                default:
                    throw new Exception("Operation not supported, sorry", 404);
            }
        } else {
            // Create a new event, pending unless user has privs

            // incoming data
            $event = array();
            $errors = array();

            $event['name'] = filter_var($request->getParameter("name"), FILTER_SANITIZE_STRING);
            if(empty($event['name'])) {
                $errors[] = "'name' is a required field";
            }

            $event['description'] = filter_var($request->getParameter("description"), FILTER_SANITIZE_STRING);
            if(empty($event['description'])) {
                $errors[] = "'description' is a required field";
            }

            $start_date = strtotime($request->getParameter("start_date"));
            $end_date = strtotime($request->getParameter("end_date"));
            if(!$start_date || !$end_date) {
                $errors[] = "Both 'start_date' and 'end_date' must be supplied in a recognised format";
            } else {
                // if the dates are okay, sort out timezones
                
                $event['tz_continent'] = filter_var($request->getParameter("tz_continent"), FILTER_SANITIZE_STRING);
                $event['tz_place'] = filter_var($request->getParameter("tz_place"), FILTER_SANITIZE_STRING);
                try {
                    // make the timezone, and read in times with respect to that
                    $tz = new DateTimeZone($event['tz_continent'] . '/' . $event['tz_place']);
                    $start_date = new DateTime($request->getParameter("start_date"), $tz);
                    $end_date = new DateTime($request->getParameter("end_date"), $tz);
                    $event['start_date'] = $start_date->format('U');
                    $event['end_date'] = $end_date->format('U');
                } catch(Exception $e) {
                    // the time zone isn't right
                    $errors[] = "The fields 'tz_continent' and 'tz_place' must be supplied and valid (e.g. Europe and London)";
                }
            }

            // optional fields
            $href  = filter_var($request->getParameter("href"), FILTER_VALIDATE_URL);
            if($href) {
                $event['href'] = $href;
            }
            $cfp_url = filter_var($request->getParameter("cfp_url"), FILTER_VALIDATE_URL);
            if($cfp_url) {
                $event['cfp_url'] = $cfp_url;
            }
            
            $cfp_start_date = strtotime($request->getParameter("cfp_start_date"));
            if($cfp_start_date) {
                $event['cfp_start_date'] = new DateTime($request->getParameter("cfp_start_date"), $tz);
            }
            $cfp_end_date = strtotime($request->getParameter("cfp_end_date"));
            if($cfp_end_date) {
                $event['cfp_end_date'] = new DateTime($request->getParameter("cfp_end_date"), $tz);
            }

            // How does it look?  With no errors, we can proceed
            if($errors) {
                throw new Exception(implode(". ", $errors), 400);
            } else {
                // site admins get their events auto approved
                $user_mapper= new UserMapper($db, $request);
                $event_mapper = new EventMapper($db, $request);

                if($user_mapper->isSiteAdmin($request->user_id)) {
                    $event_id = $event_mapper->createEvent($event, true);
                } else {
                    $event_id = $event_mapper->createEvent($event);
                }

                // now set the current user as host and attending
                $event_mapper->addUserAsHost($event_id, $request->user_id);
                $event_mapper->setUserAttendance($event_id, $request->user_id);

                // redirect to event listing; a pending event won't be visible
                header("Location: " . $request->base . $request->path_info, NULL, 201);
                exit;

            }

        }

    }

    public function deleteAction($request, $db) {
        if(!isset($request->user_id)) {
            throw new Exception("You must be logged in to delete data", 400);
        }
        if(isset($request->url_elements[4])) {
            switch($request->url_elements[4]) {
                case 'attending':
                    $event_id = $this->getItemId($request);
                    $event_mapper = new EventMapper($db, $request);
                    $event_mapper->setUserNonAttendance($event_id, $request->user_id);
                    header("Location: " . $request->base . $request->path_info, NULL, 200);
                    return;
                    break;
                default:
                    throw new Exception("Operation not supported, sorry", 404);
            }
        } else {
            throw new Exception("Operation not supported, sorry", 404);
        }
    }
}
