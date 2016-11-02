<?php
// @codingStandardsIgnoreStart
class Event_hostsController extends ApiController
// @codingStandardsIgnoreEnd
{
    /** @var EventHostMapper */
    protected $eventHostMapper = null;

    /** @var EventMapper */
    protected $eventMapper = null;

    /** @var UserMapper  */
    protected $userMapper = null;

    /**
     * @param Request $request
     * @param PDO     $db
     *
     * @throws Exception
     * @return array|bool
     */
    public function listHosts(Request $request, PDO $db)
    {
        $event_id = $this->getItemId($request);

        // verbosity
        $verbose = $this->getVerbosity($request);

        // pagination settings
        $start          = $this->getStart($request);
        $resultsperpage = $this->getResultsPerPage($request);

        $mapper = $this->getEventHostMapper($request, $db);
        if (! $event_id) {
            throw new Exception('Event not found', 404);
        }

        $list = $mapper->getHostsByEventId($event_id, $verbose, $start, $resultsperpage);

        if (false === $list) {
            throw new Exception('Event not found', 404);
        }

        return $list;
    }

    /**
     * @param Request $request
     * @param PDO     $db
     *
     * @uses host_name
     * @throws Exception
     * @return int|false
     */
    public function addHost(Request $request, PDO $db)
    {
        if (! isset($request->user_id)) {
            throw new Exception("You must be logged in to create data", 401);
        }

        $event_id = $this->getItemId($request);

        $eventMapper = $this->getEventMapper($request, $db);
        $event = $eventMapper->getEventById($event_id);
        if (false === $event) {
            throw new Exception('Event not found', 404);
        }

        $isAdmin = $eventMapper->thisUserHasAdminOn($event_id);
        if (!$isAdmin) {
            throw new Exception("You do not have permission to add hosts to this event", 403);
        }
        $username = filter_var(
            $request->getParameter('host_name'),
            FILTER_SANITIZE_STRING,
            FILTER_FLAG_NO_ENCODE_QUOTES
        );
        $userMapper = $this->getUserMapper($request, $db);
        $user_id = $userMapper->getUserIdFromUsername($username);
        if (false === $user_id) {
            throw new Exception('No User found', 404);
        }

        $mapper = $this->getEventHostMapper($request, $db);

        $uid = $mapper->addHostToEvent($event_id, $user_id);
        if (false === $uid) {
            throw new Exception('Something went wrong', 400);
        }

        $uri = sprintf(
            '%1$s/%2$s/events/%3$s/hosts',
            $request->base,
            $request->version,
            $event_id
        );

        $request->getView()->setHeader('Location', $uri);
        $request->getView()->setResponseCode(201);
        $request->getView()->setNoRender(true);

        return;
    }

    /**
     * @param Request $request
     * @param PDO     $db
     *
     * @throws Exception
     * @return bool
     */
    public function removeHostFromEvent(Request $request, PDO $db)
    {
        if (! isset($request->user_id)) {
            throw new Exception("You must be logged in to create data", 401);
        }

        $user_id = $request->url_elements[5];
        if ($user_id === $request->user_id) {
            throw new Exception('You are not allowed to remove yourself from the host-list', 403);
        }

        $event_id = $this->getItemId($request);

        $eventMapper = $this->getEventMapper($request, $db);
        $event = $eventMapper->getEventById($event_id);
        if (false === $event) {
            throw new Exception('Event not found', 404);
        }

        $isAdmin = $eventMapper->thisUserHasAdminOn($event_id);
        if (!$isAdmin) {
            throw new Exception("You do not have permission to add hosts to this event", 403);
        }

        $userMapper = $this->getUserMapper($request, $db);
        $user = $userMapper->getUserById($user_id);
        if (false === $user) {
            throw new Exception('No User found', 404);
        }

        $mapper = $this->getEventHostMapper($request, $db);

        $uid = $mapper->removeHostFromEvent($user_id, $event_id);
        if (false === $uid) {
            throw new Exception('Something went wrong', 400);
        }

        $uri = sprintf(
            '%1$s/%2$s/events/%3$s/hosts',
            $request->base,
            $request->version,
            $event_id
        );

        $request->getView()->setHeader('Location', $uri);
        $request->getView()->setResponseCode(204);
        $request->getView()->setNoRender(true);

        return;

    }

    /**
     * @param Request $request
     * @param PDO     $db
     *
     * @return EventHostMapper
     */
    public function getEventHostMapper(Request $request, PDO $db)
    {
        if ($this->eventHostMapper === null) {
            $this->eventHostMapper = new EventHostMapper($db, $request);
        }

        return $this->eventHostMapper;
    }

    public function setEventHostMapper(EventHostMapper $mapper)
    {
        $this->eventHostMapper = $mapper;
    }

    public function getEventMapper($request, $db)
    {
        if ($this->eventMapper === null) {
            $this->eventMapper = new EventMapper($db, $request);
        }

        return $this->eventMapper;
    }

    public function setEventMapper(EventMapper $eventMapper)
    {
        $this->eventMapper = $eventMapper;
    }

    public function getUserMapper($request, $db)
    {
        if ($this->userMapper === null) {
            $this->userMapper = new UserMapper($db, $request);
        }

        return $this->userMapper;
    }

    public function setUserMapper(UserMapper $userMapper)
    {
        $this->userMapper = $userMapper;
    }
}
