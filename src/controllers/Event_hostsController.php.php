<?php
// @codingStandardsIgnoreStart
class Event_hostsController extends ApiController
// @codingStandardsIgnoreEnd
{
    protected $eventHostMapper = null;

    public function listHosts($request, $db)
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
            throw new Exception('Comment not found', 404);
        }

        return $list;
    }

    public function getEventHostMapper($request, $db)
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
}
