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
                            $list = $talk_mapper->getTalksByEventId($event_id, $resultsperpage, $start, $verbose);
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
                            $list = $mapper->getTracksByEventId($event_id, $resultsperpage, $start, $verbose);
                            break;
                default:
                            throw new InvalidArgumentException('Unknown Subrequest', 404);
                            break;
            }
        } else {
            $mapper = new EventMapper($db, $request);
            if($event_id) {
                $list = $mapper->getEventById($event_id, $verbose);
                if(count($list['events']) == 0) {
                    throw new Exception('Event not found', 404);
                }
            } else {
                // handle the filter parameters
                $params = array();

                // collection type filter
                $filters = array("hot", "upcoming", "past", "cfp");
                if(isset($request->parameters['filter']) && in_array($request->parameters['filter'], $filters)) {
                    $params["filter"] = $request->parameters['filter'];
                }

                if(isset($request->parameters['title'])) {
                    $title = filter_var($request->parameters['title'], FILTER_SANITIZE_STRING);
                    $params["title"] = $title;
                }

                if(isset($request->parameters['stub'])) {
                    $stub = filter_var($request->parameters['stub'], FILTER_SANITIZE_STRING);
                    $params["stub"] = $stub;
                }

                if(isset($request->parameters['tags'])) {
                    // if it isn't an array, make it one
                    if(is_array($request->parameters['tags'])) {
                        foreach($request->parameters['tags'] as $t) {
                            $tags[] = filter_var(trim($t), FILTER_SANITIZE_STRING);
                        }
                    } else {
                        $tags = array(filter_var(trim($request->parameters['tags']), FILTER_SANITIZE_STRING));
                    }
                    $params["tags"] = $tags;
                }

                if(isset($request->parameters['startdate'])) {
                    $start_datetime = new DateTime($request->parameters['startdate']);
                    if($start_datetime) {
                        $params["startdate"] = $start_datetime->format("U");
                    }
                }

                if(isset($request->parameters['enddate'])) {
                    $end_datetime = new DateTime($request->parameters['enddate']);
                    if($end_datetime) {
                        $params["enddate"] = $end_datetime->format("U");
                    }
                }

                $list = $mapper->getEventList($resultsperpage, $start, $params, $verbose);
            }
        }

        return $list;
	}

    public function postAction($request, $db) {

        if(!isset($request->user_id)) {
            if ((isset($request->url_elements[4])) && ($request->url_elements[4] != "messages")) {
                throw new Exception("You must be logged in to create data", 400);
            }
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

                    // get the event so we can get the timezone info
                    $list = $event_mapper->getEventById($talk['event_id'], true);
                    if(count($list['events']) == 0) {
                        throw new Exception('Event not found', 404);
                    }
                    $event = $list['events'][0];

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

                    $talk_types = array("Talk", "Social event", "Keynote", "Workshop", "Event related");
                    if($request->getParameter("talk_type") && in_array($request->getParameter("talk_type"), $talk_types)) {
                        $talk['talk_type'] = $request->getParameter("talk_type");
                    } else {
                        $talk['talk_type'] = "Talk";
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
                    $tz = new DateTimeZone($event['tz_continent'] . '/' . $event['tz_place']);
                    $start_date = new DateTime($request->getParameter("start_date"), $tz);
                    $talk['date'] = $start_date->format('U');

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
                    $comment['cname']   = $thisUser['full_name'];
                    $comment['source']  = $consumer_name;

                    // run it by akismet if we have it
                    if(isset($this->config['akismet']['apiKey'])) {
                        $spamCheckService = new SpamCheckService($this->config['akismet']['apiKey']);
                        $isValid = $spamCheckService->isCommentAcceptable($comment);
                        if(!$isValid) {
                            throw new Exception("Comment failed spam check", 400);
                        }
                    }

                    $comment_mapper = new EventCommentMapper($db, $request);
                    try {
                        $new_id = $comment_mapper->save($comment);
                    } catch (Exception $e) {
                        // just throw this again but with a 400 status code
                        throw new Exception($e->getMessage(), 400);
                    }

                    // Update the cache count for the number of event comments on this event
                    $event_mapper = new EventMapper($db, $request);
                    $event_mapper->cacheCommentCount($comment['event_id']);

                    $uri = $request->base . '/' . $request->version . '/event_comments/' . $new_id;
                    header("Location: " . $uri, NULL, 201);
                    exit;

                case 'messages':
                    // Check that we've actually been given a feedback message to send
                    if (!isset($request->parameters['feedback'])) {
                        throw new Exception("No feedback message provided to send", 400);
                    }

                    // Make sure this event exists before we do anything
                    $event_mapper = new EventMapper($db, $request);
                    $list = $event_mapper->getEventById($this->getItemId($request), true);
                    if(count($list['events']) == 0) {
                        throw new Exception('Event not found', 404);
                    }
                    $event = $list['events'][0];

                    // Let's build our array to pass to EventMessageEmailService
                    $feedback = array();

                    // Add the feedback to the array
                    $feedback['feedback'] = $request->parameters['feedback'];

                    // If we're logged in, then add a little information about the user sending
                    // the message to the actual message.
                    if (isset($request->user_id)) {
                        $mapper = new UserMapper($db, $request);
                        $user = $mapper->getUserById($request->user_id, $this->getVerbosity($request));
                        $feedback['feedback'] .= "\n\n";
                        $feedback['feedback'] .= 'Sent by: '.$user[0]->full_name;
                        if (isset($user[0]->website_uri))
                        {
                            $feedback['feedback'] .= ' ('.$user[0]->website_uri.')';
                        }
                    }

                    // Could add another public method to replicate the below, easier to do SQL
                    // at this point. getHosts() on the event_mapper does exactly what we need
                    // so we'll use the query from there
                    $host_sql = '
                    SELECT
                      email

                    FROM
                      user_admin

                    LEFT JOIN
                      joindin.user ON (user_admin.uid = user.id)

                    WHERE
                      user_admin.rid = :event_id AND
                      user_admin.rtype = "event" AND
                      (user_admin.rcode != "pending" OR rcode IS NULL)';

                    $host_stmt = $db->prepare($host_sql);
                    $host_stmt->execute(array("event_id" => $this->getItemId($request)));
                    $hosts = $host_stmt->fetchAll(PDO::FETCH_ASSOC);


                    $recipients = array();

                    if(is_array($hosts)) {
                        foreach($hosts as $person) {
                            // This is trusting that the email address stored in the database is
                            // clean and valid, we'll assume so but could easily wrap this in a filter_var
                            // to be double sure
                            $recipients[] = $person['email'];
                        }

                        if (count($recipients)) {
                            // Send out the email to these event hosts
                            $emailService = new EventFeedbackEmailService($this->config, $recipients, $event, $feedback);
                            $emailService->sendEmail();

                            header("Location: " . $request->base . $request->path_info, NULL, 201);
                            exit;
                        } else {
                            // We don't have any email addresses to send to
                            throw new Exception("No hosts for this event", 400);
                        }
                    } else {
                        throw new Exception("No hosts for this event", 400);
                    }

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

            $event['location']  = filter_var($request->getParameter("location"), FILTER_SANITIZE_STRING);
            if (empty($event['location'])) {
                $errors[] = "'location' is a required field (for virtual events, 'online' works)";
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

            // optional fields - only check if we have no errors as we may need
            // access to $tz.
            if (!$errors) {
                $href  = filter_var($request->getParameter("href"), FILTER_VALIDATE_URL);
                if($href) {
                    $event['href'] = $href;
                }
                $cfp_url = filter_var($request->getParameter("cfp_url"), FILTER_VALIDATE_URL);
                if($cfp_url) {
                    $event['cfp_url'] = $cfp_url;
                }
                $cfp_start_date = strtotime($request->getParameter("cfp_start_date"));
                if ($cfp_start_date) {
                    $cfp_start_date = new DateTime($request->getParameter("cfp_start_date"), $tz);
                    $event['cfp_start_date'] = $cfp_start_date->format('U');
                }
                $cfp_end_date = strtotime($request->getParameter("cfp_end_date"));
                if ($cfp_end_date) {
                    $cfp_end_date = new DateTime($request->getParameter("cfp_end_date"), $tz);
                    $event['cfp_end_date'] = $cfp_end_date->format('U');
                }
                $latitude  = filter_var($request->getParameter("latitude"), FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
                if ($latitude) {
                    $event['latitude'] = $latitude;
                }
                $longitude  = filter_var($request->getParameter("longitude"), FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
                if ($longitude) {
                    $event['longitude'] = $longitude;
                }
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

                    // redirect to event listing
                    header("Location: " . $request->base . $request->path_info . '/' . $event_id, NULL, 201);
                } else {
                    $event_id = $event_mapper->createEvent($event);

                    // set status to accepted; a pending event won't be visible
                    header("Location: " . $request->base . $request->path_info, NULL, 202);
                }

                // now set the current user as host and attending
                $event_mapper->addUserAsHost($event_id, $request->user_id);
                $event_mapper->setUserAttendance($event_id, $request->user_id);

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
