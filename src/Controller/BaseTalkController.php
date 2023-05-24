<?php

namespace Joindin\Api\Controller;

use Exception;
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
    protected PDO $db;
    protected Request $request;

    protected array $classMappings = [
        'talk'        => TalkMapper::class,
        'talkcomment' => TalkCommentMapper::class,
    ];

    protected array $classMaps = [];

    protected EventMapper $event_mapper;

    private TalkMapper $talk_mapper;

    private UserMapper $user_mapper;

    protected function checkLoggedIn(Request $request): void
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

    public function setTalkMapper(TalkMapper $talk_mapper): void
    {
        $this->talk_mapper = $talk_mapper;
    }

    public function getTalkMapper(PDO $db, Request $request): TalkMapper
    {
        if (!isset($this->talk_mapper)) {
            $this->talk_mapper = new TalkMapper($db, $request);
        }

        return $this->talk_mapper;
    }

    public function setEventMapper(EventMapper $event_mapper): void
    {
        $this->event_mapper = $event_mapper;
    }

    public function getEventMapper(PDO $db, Request $request): EventMapper
    {
        if (!isset($this->event_mapper)) {
            $this->event_mapper = new EventMapper($db, $request);
        }

        return $this->event_mapper;
    }

    public function setUserMapper(UserMapper $user_mapper): void
    {
        $this->user_mapper = $user_mapper;
    }

    public function getUserMapper(PDO $db, Request $request): UserMapper
    {
        if (!isset($this->user_mapper)) {
            $this->user_mapper = new UserMapper($db, $request);
        }

        return $this->user_mapper;
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
        int $talk_id = 0,
        bool $verbose = false
    ): TalkModel {
        $mapper = $this->getTalkMapper($db, $request);

        if (0 === $talk_id) {
            $talk_id = $this->getItemId($request);
        }

        if (false === $talk_id) {
            throw new Exception('Talk not found', Http::NOT_FOUND);
        }

        $talk = $mapper->getTalkById($talk_id, $verbose);

        if (false === $talk) {
            throw new Exception('Talk not found', Http::NOT_FOUND);
        }

        return $talk;
    }

    protected function setDbAndRequest(PDO $db, Request $request): void
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
    protected function getMapper(string $type, PDO $db = null, Request $request = null): ApiMapper
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

    public function setMapper(string $type, ApiMapper $object): void
    {
        $this->classMaps[$type] = $object;
    }
}
