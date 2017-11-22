<?php

class BaseTalkController extends ApiController
{
    /** @var PDO */
    protected $db;
    /** @var Request */
    protected $request;

    protected $classMappings = [
        'talk' => TalkMapper::class,
        'talkcomment' => TalkCommentMapper::class,
    ];

    protected $classMaps = [];

    protected function checkLoggedIn(Request $request)
    {
        $failMessages = [
            'POST' => 'create data',
            'DELETE' => 'remove data',
            'GET' => 'view data',
            'PUT' => 'update data'
        ];

        if (!isset($request->user_id)) {
            throw new Exception(
                sprintf(
                    "You must be logged in to %s",
                    $failMessages[$request->getVerb()]
                ),
                401
            );
        }
    }

    public function setTalkMapper(TalkMapper $talk_mapper)
    {
        $this->talk_mapper = $talk_mapper;
    }

    public function getTalkMapper(PDO $db, Request $request)
    {
        if (!isset($this->talk_mapper)) {
            $this->talk_mapper = new TalkMapper($db, $request);
        }

        return $this->talk_mapper;
    }

    public function setEventMapper(EventMapper $event_mapper)
    {
        $this->event_mapper = $event_mapper;
    }

    public function getEventMapper(PDO $db, Request $request)
    {
        if (! isset($this->event_mapper)) {
            $this->event_mapper = new EventMapper($db, $request);
        }

        return $this->event_mapper;
    }


    public function setUserMapper(UserMapper $user_mapper)
    {
        $this->user_mapper = $user_mapper;
    }

    public function getUserMapper(PDO $db, Request $request)
    {
        if (! isset($this->user_mapper)) {
            $this->user_mapper = new UserMapper($db, $request);
        }

        return $this->user_mapper;
    }


    /**
     * Get a single talk
     *
     * @param  Request  $request
     * @param  PDO      $db
     * @param  integer  $talk_id
     * @param  boolean $verbose
     *
     * @throws Exception if the talk is not found
     * @return TalkModel
     */
    protected function getTalkById(
        Request $request,
        PDO $db,
        $talk_id = 0,
        $verbose = false
    ) {
        $mapper = $this->getTalkMapper($db, $request);
        if (0 === $talk_id) {
            $talk_id = $this->getItemId($request);
        }

        $talk = $mapper->getTalkById($talk_id, $verbose);
        if (false === $talk) {
            throw new Exception('Talk not found', 404);
        }

        return $talk;
    }

    protected function setDbAndRequest(PDO $db, Request $request)
    {
        $this->db = $db;
        $this->request = $request;
    }

    /**
     * @param string $type
     * @param PDO|null $db
     * @param Request|null $request
     *
     * @return ApiMapper
     */
    protected function getMapper($type, PDO $db = null, Request $request = null)
    {
        if (is_null($db)) {
            $db = $this->db;
        }

        if (is_null($request)) {
            $request = $this->request;
        }

        if (!isset($this->classMaps[$type])) {
            $this->classMaps[$type] = new $this->classMappings[$type]($db, $request);
        }

        return $this->classMaps[$type];
    }

    public function setMapper($type, $object)
    {
        $this->classMaps[$type] = $object;
    }
}
