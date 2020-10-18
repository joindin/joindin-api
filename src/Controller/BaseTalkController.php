<?php

namespace Joindin\Api\Controller;

use Exception;
use Joindin\Api\Factory\MapperFactory;
use Joindin\Api\Model\ApiMapper;
use Joindin\Api\Model\EventMapper;
use Joindin\Api\Model\TalkCommentMapper;
use Joindin\Api\Model\TalkMapper;
use Joindin\Api\Model\TalkModel;
use Joindin\Api\Model\UserMapper;
use PDO;
use Joindin\Api\Request;
use Teapot\StatusCode\Http;

class BaseTalkController extends BaseApiController
{
    /** @var PDO */
    protected $db;
    /** @var Request */
    protected $request;

    protected $classMappings = [
        'talk'        => TalkMapper::class,
        'talkcomment' => TalkCommentMapper::class,
    ];

    protected $classMaps = [];

    /**
     * @var EventMapper
     */
    protected $event_mapper;

    /**
     * @var TalkMapper
     */
    private $talk_mapper;

    /**
     * @var UserMapper
     */
    private $user_mapper;
    /**
     * @var MapperFactory|null
     */
    protected $mapperFactory;

    public function __construct(array $config = [], MapperFactory $mapperFactory = null)
    {
        parent::__construct($config);

        $this->mapperFactory = $mapperFactory ?? new MapperFactory();
    }

    protected function checkLoggedIn(Request $request)
    {
        $failMessages = [
            'POST'   => 'create data',
            'DELETE' => 'remove data',
            'GET'    => 'view data',
            'PUT'    => 'update data'
        ];

        if (!isset($request->user_id)) {
            throw new Exception(
                sprintf(
                    "You must be logged in to %s",
                    $failMessages[$request->getVerb()]
                ),
                Http::UNAUTHORIZED
            );
        }
    }

    public function setTalkMapper(TalkMapper $talk_mapper)
    {
        $this->mapperFactory->setMapper($talk_mapper);
    }

    public function getTalkMapper(PDO $db, Request $request)
    {
        return $this->mapperFactory->getMapper(TalkMapper::class, $db, $request);
    }

    public function setEventMapper(EventMapper $event_mapper)
    {
        $this->event_mapper = $event_mapper;
    }

    public function getEventMapper(PDO $db, Request $request)
    {
        return $this->mapperFactory->getMapper(EventMapper::class, $db, $request);
    }

    public function setUserMapper(UserMapper $user_mapper)
    {
        $this->user_mapper = $user_mapper;
    }

    public function getUserMapper(PDO $db, Request $request)
    {
        return $this->mapperFactory->getMapper(UserMapper::class, $db, $request);
    }

    /**
     * Get a single talk
     *
     * @param  Request $request
     * @param  PDO     $db
     * @param  integer $talk_id
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
            throw new Exception('Talk not found', Http::NOT_FOUND);
        }

        return $talk;
    }

    protected function setDbAndRequest(PDO $db, Request $request)
    {
        $this->db      = $db;
        $this->request = $request;
    }

    /**
     * @param string       $type
     * @param PDO|null     $db
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
            $this->classMaps[$type] = $this->mapperFactory->getMapper($type, $db, $request);
        }

        return $this->classMaps[$type];
    }

    public function setMapper($object)
    {
        $this->mapperFactory->setMapper($object);
    }
}
