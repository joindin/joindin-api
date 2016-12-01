<?php

class EventsController extends ApiController
{
    public function getAction($request, $db)
    {
        $event_id = $this->getItemId($request);

        // verbosity
        $verbose = $this->getVerbosity($request);

        // pagination settings
        $start          = $this->getStart($request);
        $resultsperpage = $this->getResultsPerPage($request);

        if (isset($request->url_elements[4])) {
            switch ($request->url_elements[4]) {
                case 'talks':
                    $talk_mapper = new TalkMapper($db, $request);
                    $talks = $talk_mapper->getTalksByEventId($event_id, $resultsperpage, $start);
                    $list = $talks->getOutputView($request, $verbose);
                    break;
                case 'comments':
                    $event_comment_mapper = new EventCommentMapper($db, $request);
                    $list                 = $event_comment_mapper->getEventCommentsByEventId(
                        $event_id,
                        $resultsperpage,
                        $start,
                        $verbose
                    );
                    break;
                case 'talk_comments':
                    $sort                = $this->getSort($request);
                    $talk_comment_mapper = new TalkCommentMapper($db, $request);
                    $list                = $talk_comment_mapper->getCommentsByEventId(
                        $event_id,
                        $resultsperpage,
                        $start,
                        $verbose,
                        $sort
                    );
                    break;
                case 'attendees':
                    $user_mapper = new UserMapper($db, $request);
                    $list        = $user_mapper->getUsersAttendingEventId($event_id, $resultsperpage, $start, $verbose);
                    break;
                case 'attending':
                    $mapper = new EventMapper($db, $request);
                    $list   = $mapper->getUserAttendance($event_id, $request->user_id);
                    break;
                case 'tracks':
                    $mapper = new TrackMapper($db, $request);
                    $list   = $mapper->getTracksByEventId($event_id, $resultsperpage, $start, $verbose);
                    break;
                default:
                    throw new InvalidArgumentException('Unknown Subrequest', 404);
                    break;
            }
        } else {
            $mapper           = new EventMapper($db, $request);
            $user_mapper      = new UserMapper($db, $request);
            $isSiteAdmin      = $user_mapper->isSiteAdmin($request->user_id);
            $activeEventsOnly = $isSiteAdmin ? false : true;

            if ($event_id) {
                $list = $mapper->getEventById($event_id, $verbose, $activeEventsOnly);
                if (count($list['events']) == 0) {
                    throw new Exception('Event not found', 404);
                }
            } else {
                // handle the filter parameters
                $params = array();

                // collection type filter
                $filters = array("hot", "upcoming", "past", "cfp", "pending", "all");
                if (isset($request->parameters['filter']) && in_array($request->parameters['filter'], $filters)) {
                    $params["filter"] = $request->parameters['filter'];

                    // for pending events we need a logged in user with the correct permissions
                    if ($params["filter"] == 'pending') {
                        if (! isset($request->user_id)) {
                            throw new Exception("You must be logged in to view pending events", 400);
                        }
                        $user_mapper      = new UserMapper($db, $request);
                        $canApproveEvents = $user_mapper->isSiteAdmin($request->user_id);
                        if (! $canApproveEvents) {
                            throw new Exception("You don't have permission to view pending events", 403);
                        }
                    }
                }

                if (isset($request->parameters['title'])) {
                    $title           = filter_var(
                        $request->parameters['title'],
                        FILTER_SANITIZE_STRING,
                        FILTER_FLAG_NO_ENCODE_QUOTES
                    );
                    $params["title"] = $title;
                }

                if (isset($request->parameters['stub'])) {
                    $stub           = filter_var(
                        $request->parameters['stub'],
                        FILTER_SANITIZE_STRING,
                        FILTER_FLAG_NO_ENCODE_QUOTES
                    );
                    $params["stub"] = $stub;
                }

                if (isset($request->parameters['tags'])) {
                    // if it isn't an array, make it one
                    if (is_array($request->parameters['tags'])) {
                        foreach ($request->parameters['tags'] as $t) {
                            $tags[] = filter_var(trim($t), FILTER_SANITIZE_STRING, FILTER_FLAG_NO_ENCODE_QUOTES);
                        }
                    } else {
                        $tags = array(
                            filter_var(
                                trim($request->parameters['tags']),
                                FILTER_SANITIZE_STRING,
                                FILTER_FLAG_NO_ENCODE_QUOTES
                            )
                        );
                    }
                    $params["tags"] = $tags;
                }

                if (isset($request->parameters['startdate'])) {
                    $start_datetime = new DateTime($request->parameters['startdate']);
                    if ($start_datetime) {
                        $params["startdate"] = $start_datetime->format("U");
                    }
                }

                if (isset($request->parameters['enddate'])) {
                    $end_datetime = new DateTime($request->parameters['enddate']);
                    if ($end_datetime) {
                        $params["enddate"] = $end_datetime->format("U");
                    }
                }

                $list = $mapper->getEventList($resultsperpage, $start, $params, $verbose);
            }
        }

        return $list;
    }

    public function postAction($request, $db, \Joindin\Pubsub\EventCoordinator $ec)
    {
        if (! isset($request->user_id)) {
            throw new Exception("You must be logged in to create data", 401);
        }
        if (isset($request->url_elements[4])) {
            switch ($request->url_elements[4]) {
                case 'attending':
                    // the body of this request is completely irrelevant
                    // The logged in user *is* attending the event.  Use DELETE to unattend
                    $event_id     = $this->getItemId($request);
                    $event_mapper = new EventMapper($db, $request);
                    $event_mapper->setUserAttendance($event_id, $request->user_id);
                    header("Location: " . $request->base . $request->path_info, null, 201);

                    return;
                default:
                    throw new Exception("Operation not supported, sorry", 404);
            }
        } else {
            // Create a new event, pending unless user has privs

            // incoming data
            $event  = array();
            $errors = array();

            $event['name'] = filter_var(
                $request->getParameter("name"),
                FILTER_SANITIZE_STRING,
                FILTER_FLAG_NO_ENCODE_QUOTES
            );
            if (empty($event['name'])) {
                $errors[] = "'name' is a required field";
            }

            $event['description'] = filter_var(
                $request->getParameter("description"),
                FILTER_SANITIZE_STRING,
                FILTER_FLAG_NO_ENCODE_QUOTES
            );
            if (empty($event['description'])) {
                $errors[] = "'description' is a required field";
            }

            $event['location'] = filter_var(
                $request->getParameter("location"),
                FILTER_SANITIZE_STRING,
                FILTER_FLAG_NO_ENCODE_QUOTES
            );
            if (empty($event['location'])) {
                $errors[] = "'location' is a required field (for virtual events, 'online' works)";
            }

            $start_date = strtotime($request->getParameter("start_date"));
            $end_date   = strtotime($request->getParameter("end_date"));
            if (! $start_date || ! $end_date) {
                $errors[] = "Both 'start_date' and 'end_date' must be supplied in a recognised format";
            } elseif ($start_date > $end_date) {
                $errors[] = "The event start date must be before its end date";
            } else {
                // if the dates are okay, sort out timezones

                $event['tz_continent'] = filter_var(
                    $request->getParameter("tz_continent"),
                    FILTER_SANITIZE_STRING,
                    FILTER_FLAG_NO_ENCODE_QUOTES
                );
                $event['tz_place']     = filter_var(
                    $request->getParameter("tz_place"),
                    FILTER_SANITIZE_STRING,
                    FILTER_FLAG_NO_ENCODE_QUOTES
                );
                try {
                    // make the timezone, and read in times with respect to that
                    $tz                  = new DateTimeZone($event['tz_continent'] . '/' . $event['tz_place']);
                    $start_date          = new DateTime($request->getParameter("start_date"), $tz);
                    $end_date            = new DateTime($request->getParameter("end_date"), $tz);
                    $event['start_date'] = $start_date->format('U');
                    $event['end_date']   = $end_date->format('U');
                } catch (Exception $e) {
                    // the time zone isn't right
                    $errors[] = "The fields 'tz_continent' and 'tz_place' must be supplied and valid ".
                                "(e.g. Europe and London)";
                }
            }

            // optional fields - only check if we have no errors as we may need
            // access to $tz.
            if (! $errors) {
                $href = filter_var($request->getParameter("href"), FILTER_VALIDATE_URL);
                if ($href) {
                    $event['href'] = $href;
                }
                $cfp_url = filter_var($request->getParameter("cfp_url"), FILTER_VALIDATE_URL);
                if ($cfp_url) {
                    $event['cfp_url'] = $cfp_url;
                }
                $cfp_start_date = strtotime($request->getParameter("cfp_start_date"));
                if ($cfp_start_date) {
                    $cfp_start_date          = new DateTime($request->getParameter("cfp_start_date"), $tz);
                    $event['cfp_start_date'] = $cfp_start_date->format('U');
                }
                $cfp_end_date = strtotime($request->getParameter("cfp_end_date"));
                if ($cfp_end_date) {
                    $cfp_end_date          = new DateTime($request->getParameter("cfp_end_date"), $tz);
                    $event['cfp_end_date'] = $cfp_end_date->format('U');
                }
                $latitude = filter_var(
                    $request->getParameter("latitude"),
                    FILTER_SANITIZE_NUMBER_FLOAT,
                    FILTER_FLAG_ALLOW_FRACTION
                );
                if ($latitude) {
                    $event['latitude'] = $latitude;
                }
                $longitude = filter_var(
                    $request->getParameter("longitude"),
                    FILTER_SANITIZE_NUMBER_FLOAT,
                    FILTER_FLAG_ALLOW_FRACTION
                );
                if ($longitude) {
                    $event['longitude'] = $longitude;
                }
                $incoming_tag_list = $request->getParameter('tags');
                if (is_array($incoming_tag_list)) {
                    $tags = array_map(
                        function ($tag) {
                            $tag = filter_var($tag, FILTER_SANITIZE_STRING, FILTER_FLAG_NO_ENCODE_QUOTES);
                            $tag = trim($tag);
                            $tag = strtolower($tag);

                            return $tag;
                        },
                        $incoming_tag_list
                    );
                }

            }

            $event_mapper = new EventMapper($db, $request, $ec);

            // Make sure they only have a maximum of $max_pending_events
            // unapproved event submissions at any time
            $max_pending_events = 3;
            if (isset($this->config['limits']['max_pending_events'])) {
                $max_pending_events = $this->config['limits']['max_pending_events'];
            }

            $current_pending = $event_mapper->getPendingEventsCountByUser($request->user_id);

            if ($current_pending >= $max_pending_events) {
                $suffix = $max_pending_events == 1 ? '' : 's';
                $errors[] = sprintf('You may only have %d pending event%s at one time', $max_pending_events, $suffix);
            }

            // How does it look?  With no errors, we can proceed
            if ($errors) {
                throw new Exception(implode(". ", $errors), 400);
            } else {
                $user_mapper  = new UserMapper($db, $request);

                $event_owner           = $user_mapper->getUserById($request->user_id);
                $event['contact_name'] = $event_owner['users'][0]['full_name'];

                // When a site admin creates an event, we want to approve it immediately
                $approveEventOnCreation = $user_mapper->isSiteAdmin($request->user_id);

                // Do we want to automatically approve when testing?
                if (isset($this->config['features']['allow_auto_approve_events'])
                    && $this->config['features']['allow_auto_approve_events']
                ) {
                    if ($request->getParameter("auto_approve_event") == "true") {
                        // The test suite sends this extra field, if we got
                        // this far then this platform supports this
                        $approveEventOnCreation = true;
                    }
                }

                if ($approveEventOnCreation) {
                    $event_id = $event_mapper->createEvent($event, true);

                    // redirect to event listing
                    header("Location: " . $request->base . $request->path_info . '/' . $event_id, null, 201);
                } else {
                    $event_id = $event_mapper->createEvent($event);

                    // set status to accepted; a pending event won't be visible
                    header("Location: " . $request->base . $request->path_info, null, 202);
                }

                // now set the current user as host and attending
                $event_mapper->addUserAsHost($event_id, $request->user_id);
                $event_mapper->setUserAttendance($event_id, $request->user_id);
                if (isset($tags)) {
                    $event_mapper->setTags($event_id, $tags);
                }

                // The Email is sent via the Notification System
                exit;
            }
        }
    }

    public function deleteAction($request, $db)
    {
        if (! isset($request->user_id)) {
            throw new Exception("You must be logged in to delete data", 401);
        }
        if (isset($request->url_elements[4])) {
            switch ($request->url_elements[4]) {
                case 'attending':
                    $event_id     = $this->getItemId($request);
                    $event_mapper = new EventMapper($db, $request);
                    $event_mapper->setUserNonAttendance($event_id, $request->user_id);
                    header("Location: " . $request->base . $request->path_info, null, 200);

                    return;
                    break;
                default:
                    throw new Exception("Operation not supported, sorry", 404);
            }
        } else {
            throw new Exception("Operation not supported, sorry", 404);
        }
    }

    public function putAction($request, $db)
    {
        if (! isset($request->user_id)) {
            throw new Exception('You must be logged in to edit data', 401);
        }

        $event_id = $this->getItemId($request);
        if (! isset($request->url_elements[4])) {
            // Edit an Event
            $event_mapper   = new EventMapper($db, $request);
            $existing_event = $event_mapper->getEventById($event_id, true);
            if (! $existing_event) {
                throw new Exception(sprintf(
                    'There is no event with ID "%s"',
                    $event_id
                ));
            }

            if (!$event_mapper->thisUserHasAdminOn($event_id)) {
                throw new Exception('You are not an host for this event', 403);
            }

            // initialise a new set of fields to save
            $event  = array("event_id" => $event_id);
            $errors = array();

            $event['name'] = filter_var(
                $request->getParameter("name"),
                FILTER_SANITIZE_STRING,
                FILTER_FLAG_NO_ENCODE_QUOTES
            );
            if (empty($event['name'])) {
                $errors[] = "'name' is a required field";
            }

            $event['description'] = filter_var(
                $request->getParameter("description"),
                FILTER_SANITIZE_STRING,
                FILTER_FLAG_NO_ENCODE_QUOTES
            );
            if (empty($event['description'])) {
                $errors[] = "'description' is a required field";
            }

            $event['location'] = filter_var(
                $request->getParameter("location"),
                FILTER_SANITIZE_STRING,
                FILTER_FLAG_NO_ENCODE_QUOTES
            );
            if (empty($event['location'])) {
                $errors[] = "'location' is a required field (for virtual events, 'online' works)";
            }

            $start_date = strtotime($request->getParameter("start_date"));
            $end_date   = strtotime($request->getParameter("end_date"));
            if (! $start_date || ! $end_date) {
                $errors[] = "Both 'start_date' and 'end_date' must be supplied in a recognised format";
            } elseif ($start_date > $end_date) {
                $errors[] = "The event start date must be before its end date";
            } else {
                // if the dates are okay, sort out timezones
                $event['tz_continent'] = filter_var(
                    $request->getParameter("tz_continent"),
                    FILTER_SANITIZE_STRING,
                    FILTER_FLAG_NO_ENCODE_QUOTES
                );
                $event['tz_place']     = filter_var(
                    $request->getParameter("tz_place"),
                    FILTER_SANITIZE_STRING,
                    FILTER_FLAG_NO_ENCODE_QUOTES
                );
                try {
                    // make the timezone, and read in times with respect to that
                    $tz                  = new DateTimeZone($event['tz_continent'] . '/' . $event['tz_place']);
                    $start_date          = new DateTime($request->getParameter("start_date"), $tz);
                    $end_date            = new DateTime($request->getParameter("end_date"), $tz);
                    $event['start_date'] = $start_date->format('U');
                    $event['end_date']   = $end_date->format('U');
                } catch (Exception $e) {
                    // the time zone isn't right
                    $errors[] = "The fields 'tz_continent' and 'tz_place' must be supplied and valid ".
                                "(e.g. Europe and London)";
                }
            }
            // How does it look?  With no errors, we can proceed
            if ($errors) {
                throw new Exception(implode(". ", $errors), 400);
            }

            // optional fields - only check if we have no errors as we may need $tz
            // also only update supplied fields - but DO allow saving empty ones
            $href = $request->getParameter("href", false); // returns false if the value was not supplied
            if (false !== $href) {
                // we got a value, filter and save it
                $event['href'] = filter_var($href, FILTER_VALIDATE_URL);
            }
            $cfp_url = $request->getParameter("cfp_url", false);
            if (false !== $cfp_url) {
                // we got a value, filter and save it
                $event['cfp_url'] = filter_var($cfp_url, FILTER_VALIDATE_URL);
            }

            $event['cfp_start_date'] = null;
            $cfp_start_date = $request->getParameter("cfp_start_date", false);
            if (false !== $cfp_start_date && strtotime($cfp_start_date)) {
                $cfp_start_date          = new DateTime($cfp_start_date, $tz);
                $event['cfp_start_date'] = $cfp_start_date->format('U');
            }
            $event['cfp_end_date'] = null;
            $cfp_end_date = $request->getParameter("cfp_end_date", false);
            if (false !== $cfp_end_date && strtotime($cfp_end_date)) {
                $cfp_end_date          = new DateTime($cfp_end_date, $tz);
                $event['cfp_end_date'] = $cfp_end_date->format('U');
            }
            $latitude = $request->getParameter("latitude", false);
            if (false !== $latitude) {
                $latitude = filter_var($latitude, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
                if ($latitude) {
                    $event['latitude'] = $latitude;
                }
            }
            $longitude = $request->getParameter("longitude", false);
            if (false !== $longitude) {
                $longitude          = filter_var($longitude, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
                $event['longitude'] = $longitude;
            }
            $incoming_tag_list = $request->getParameter('tags');
            if (is_array($incoming_tag_list)) {
                $tags = array_map(
                    function ($tag) {
                        $tag = filter_var($tag, FILTER_SANITIZE_STRING, FILTER_FLAG_NO_ENCODE_QUOTES);
                        $tag = trim($tag);
                        $tag = strtolower($tag);

                        return $tag;
                    },
                    $incoming_tag_list
                );
            }

            $event_mapper->editEvent($event, $event_id);
            if (isset($tags)) {
                $event_mapper->setTags($event_id, $tags);
            }

            header("Location: " . $request->base . $request->path_info, null, 204);
            exit;
        }
    }

    public function pendingClaims($request, $db)
    {
        // Check for login
        if (! isset($request->user_id)) {
            throw new Exception("You must be logged in to view pending claims", 401);
        }

        $event_id = $this->getItemId($request);
        $event_mapper = $this->getEventMapper($db, $request);

        $pending_talk_claim_mapper = $this->getPendingTalkClaimMapper($db, $request);
        if (! $event_mapper->thisUserHasAdminOn($event_id)) {
            throw new Exception('You do not have permission to edit this track', 403);
        }

        // verbosity
        $verbose = $this->getVerbosity($request);

        if (!$list = $pending_talk_claim_mapper->getPendingClaimsByEventId($event_id)) {
            $list = new PendingTalkClaimModelCollection([], 0);
        }
        return $list->getOutputView($request, $verbose);

    }

    /**
     * Create track
     *
     * @param  Request $request
     * @param  PDO $db
     *
     * @return void
     */
    public function createTrack($request, $db)
    {
        // Check for login
        if (! isset($request->user_id)) {
            throw new Exception("You must be logged in to create a track", 401);
        }

        $track = array();
        $event_id = $this->getItemId($request);
        $track['event_id']= $event_id;
        if (empty($track['event_id'])) {
            throw new Exception(
                "POST expects a track representation sent to a specific event URL",
                400
            );
        }

        $event_mapper = new EventMapper($db, $request);
        $events = $event_mapper->getEventById($event_id, true);
        if (!$events || $events['meta']['count'] == 0) {
            throw new Exception("Associated event not found", 404);
        }
        if (!$event_mapper->thisUserHasAdminOn($event_id)) {
            throw new Exception('You do not have permission to edit this track', 403);
        }

        // validate fields
        $errors = [];
        $track['track_name'] = filter_var(
            $request->getParameter("track_name"),
            FILTER_SANITIZE_STRING,
            FILTER_FLAG_NO_ENCODE_QUOTES
        );
        if (empty($track['track_name'])) {
            $errors[] = "'track_name' is a required field";
        }
        $track['track_description'] = filter_var(
            $request->getParameter("track_description"),
            FILTER_SANITIZE_STRING,
            FILTER_FLAG_NO_ENCODE_QUOTES
        );
        if (empty($track['track_description'])) {
            $errors[] = "'track_description' is a required field";
        }
        if ($errors) {
            throw new Exception(implode(". ", $errors), 400);
        }

        $track_mapper = new TrackMapper($db, $request);
        $track_id = $track_mapper->createEventTrack($track, $event_id);

        $uri = $request->base  . '/' . $request->version . '/tracks/' . $track_id;
        header("Location: " . $uri, null, 201);
        exit;
    }

    /**
     * Approve a pending event by POSTing to /events/{id}/approval
     *
     * The body of this request is completely irrelevant, simply POSTing to this
     * endpoint is all that's needed to approve an pending event
     *
     * @param  Request $request
     * @param  PDO $db
     *
     * @return void
     */
    public function approveAction($request, $db)
    {
        if (! isset($request->user_id)) {
            throw new Exception("You must be logged in to create data", 401);
        }

        $event_id     = $this->getItemId($request);
        $event_mapper = new EventMapper($db, $request);

        if (! $event_mapper->thisUserCanApproveEvents()) {
            throw new Exception("You are not allowed to approve this event", 403);
        }

        $result = $event_mapper->approve($event_id, $request->user_id);
        if (! $result) {
            throw new Exception("This event cannot be approved", 400);
        }

        if ($result) {
            // Send a notification email as we have approved
            $event        = $event_mapper->getEventById($event_id, true)['events'][0];
            $recipients   = $event_mapper->getHostsEmailAddresses($event_id);
            $emailService = new EventApprovedEmailService($this->config, $recipients, $event);
            $emailService->sendEmail();
        }

        $location = $request->base . '/' . $request->version . '/events/' . $event_id;
        header('Location: ' . $location, null, 204);

        return;
    }

    /**
     * Reject a pending event by DELETEing to /events/{id}/approval
     *
     * @param Request $request
     * @param PDO $db
     *
     * @return void
     */
    public function rejectAction($request, $db)
    {
        if (! isset($request->user_id)) {
            throw new Exception("You must be logged in to create data", 401);
        }

        $event_id     = $this->getItemId($request);
        $event_mapper = new EventMapper($db, $request);

        if (! $event_mapper->thisUserCanApproveEvents()) {
            throw new Exception("You are not allowed to reject this event", 403);
        }

        $result = $event_mapper->reject($event_id, $request->user_id);
        if (! $result) {
            throw new Exception("This event cannot be rejected", 400);
        }

        header("Content-Length: 0", null, 204);

        return;
    }


    public function setEventMapper(EventMapper $event_mapper)
    {
        $this->event_mapper = $event_mapper;
    }

    public function getEventMapper($db, $request)
    {
        if (! isset($this->event_mapper)) {
            $this->event_mapper = new EventMapper($db, $request);
        }

        return $this->event_mapper;
    }

    public function setPendingTalkClaimMapper(PendingTalkClaimMapper $pending_talk_claim_mapper)
    {
        $this->pending_talk_claim_mapper = $pending_talk_claim_mapper;
    }

    public function getPendingTalkClaimMapper($db, $request)
    {
        if (! isset($this->pending_talk_claim_mapper)) {
            $this->pending_talk_claim_mapper = new PendingTalkClaimMapper($db, $request);
        }

        return $this->pending_talk_claim_mapper;
    }
}
