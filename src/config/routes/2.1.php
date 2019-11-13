<?php

use Joindin\Api\Controller\FacebookController;
use Joindin\Api\Controller\TalkTypesController;
use Joindin\Api\Controller\ContactController;
use Joindin\Api\Controller\LanguagesController;
use Joindin\Api\Controller\TwitterController;
use Joindin\Api\Controller\EmailsController;
use Joindin\Api\Controller\UsersController;
use Joindin\Api\Controller\TracksController;
use Joindin\Api\Controller\TokenController;
use Joindin\Api\Controller\TalkLinkController;
use Joindin\Api\Controller\TalksController;
use Joindin\Api\Controller\EventImagesController;
use Joindin\Api\Controller\EventsController;
use Joindin\Api\Controller\TalkCommentsController;
use Joindin\Api\Controller\EventCommentsController;
use Joindin\Api\Controller\EventHostsController;
use Joindin\Api\Controller\ApplicationsController;
use Joindin\Api\Controller\DefaultController;
use Joindin\Api\Request;

return [
    [
        'path'       => '/events(/[^/]+)*/hosts',
        'controller' => EventHostsController::class,
        'action'     => 'listHosts',
        'verbs'      => [
            Request::HTTP_GET,
        ],
    ],
    [
        'path'       => '/events/[\\d]+/hosts',
        'controller' => EventHostsController::class,
        'action'     => 'addHost',
        'verbs'      => [
            Request::HTTP_POST,
        ],
    ],
    [
        'path'       => '/events/[\\d]+/hosts/[\\d]+',
        'controller' => EventHostsController::class,
        'action'     => 'removeHostFromEvent',
        'verbs'      => [
            Request::HTTP_DELETE,
        ],
    ],
    [
        'path'       => '/events(/[^/]+)*/comments/reported',
        'controller' => EventCommentsController::class,
        'action'     => 'getReported',
        'verbs'      => [
            Request::HTTP_GET,
        ],
    ],
    [
        'path'       => '/events(/[\\d]+)/talk_comments/reported',
        'controller' => TalkCommentsController::class,
        'action'     => 'getReported',
        'verbs'      => [
            Request::HTTP_GET,
        ],
    ],
    [
        'path'       => '/events/(\\d+)/claims$',
        'controller' => EventsController::class,
        'action'     => 'pendingClaims',
        'verbs'      => [
            Request::HTTP_GET,
        ],
    ],
    [
        'path'       => '/events(/[\\d]+)/images',
        'controller' => EventImagesController::class,
        'action'     => 'listImages',
        'verbs'      => [
            Request::HTTP_GET,
        ],
    ],
    [
        'path'       => '/events(/[^/]+)*/?$',
        'controller' => EventsController::class,
        'action'     => 'getAction',
        'verbs'      => [
            Request::HTTP_GET,
        ],
    ],
    [
        'path'       => '/events/(\\d+)/tracks$',
        'controller' => EventsController::class,
        'action'     => 'createTrack',
        'verbs'      => [
            Request::HTTP_POST,
        ],
    ],
    [
        'path'       => '/events/(\\d+)/approval$',
        'controller' => EventsController::class,
        'action'     => 'approveAction',
        'verbs'      => [
            Request::HTTP_POST,
        ],
    ],
    [
        'path'       => '/events/(\\d+)/approval$',
        'controller' => EventsController::class,
        'action'     => 'rejectAction',
        'verbs'      => [
            Request::HTTP_DELETE,
        ],
    ],
    [
        'path'       => '/events(/[^/]+)*/comments',
        'controller' => EventCommentsController::class,
        'action'     => 'createComment',
        'verbs'      => [
            Request::HTTP_POST,
        ],
    ],
    [
        'path'       => '/events(/[\\d]+)/images',
        'controller' => EventImagesController::class,
        'action'     => 'createImage',
        'verbs'      => [
            Request::HTTP_POST,
        ],
    ],
    [
        'path'       => '/events(/[\\d]+)/images',
        'controller' => EventImagesController::class,
        'action'     => 'deleteImage',
        'verbs'      => [
            Request::HTTP_DELETE,
        ],
    ],
    [
        'path'       => '/events(/[\\d]+)/talks',
        'controller' => TalksController::class,
        'action'     => 'createTalkAction',
        'verbs'      => [
            Request::HTTP_POST,
        ],
    ],
    [
        'path'       => '/events(/[^/]+)*/?$',
        'controller' => EventsController::class,
        'action'     => 'postAction',
        'verbs'      => [
            Request::HTTP_POST,
        ],
    ],
    [
        'path'       => '/events(/[^/]+)*/?$',
        'controller' => EventsController::class,
        'action'     => 'putAction',
        'verbs'      => [
            Request::HTTP_PUT,
        ],
    ],
    [
        'path'       => '/events(/[^/]+)*/?$',
        'controller' => EventsController::class,
        'action'     => 'deleteAction',
        'verbs'      => [
            Request::HTTP_DELETE,
        ],
    ],
    [
        'path'       => '/talks(/[\\d]+)/links/?$',
        'controller' => TalkLinkController::class,
        'action'     => 'getTalkLinks',
        'verbs'      => [
            Request::HTTP_GET,
        ],
    ],
    [
        'path'       => '/talks(/[\\d]+)/links/?$',
        'controller' => TalkLinkController::class,
        'action'     => 'addTalkLink',
        'verbs'      => [
            Request::HTTP_POST,
        ],
    ],
    [
        'path'       => '/talks(/[\\d]+)/links(/[\\d]+)$',
        'controller' => TalkLinkController::class,
        'action'     => 'getTalkLink',
        'verbs'      => [
            Request::HTTP_GET,
        ],
    ],
    [
        'path'       => '/talks(/[\\d]+)/links(/[\\d]+)$',
        'controller' => TalkLinkController::class,
        'action'     => 'removeTalkLink',
        'verbs'      => [
            Request::HTTP_DELETE,
        ],
    ],
    [
        'path'       => '/talks(/[\\d]+)/links(/[\\d]+)$',
        'controller' => TalkLinkController::class,
        'action'     => 'updateTalkLink',
        'verbs'      => [
            Request::HTTP_PUT,
        ],
    ],
    [
        'path'       => '/talks(/[\\d]+)/speakers/?$',
        'controller' => TalksController::class,
        'action'     => 'getSpeakersForTalk',
        'verbs'      => [
            Request::HTTP_GET,
        ],
    ],
    [
        'path'       => '/talks(/[\\d]+)/speakers/?$',
        'controller' => TalksController::class,
        'action'     => 'setSpeakerForTalk',
        'verbs'      => [
            Request::HTTP_POST,
        ],
    ],
    [
        'path'       => '/talks(/[\\d]+)/speakers/?$',
        'controller' => TalksController::class,
        'action'     => 'removeSpeakerForTalk',
        'verbs'      => [
            Request::HTTP_DELETE,
        ],
    ],
    [
        'path'       => '/talks(/[\\d]+)$',
        'controller' => TalksController::class,
        'action'     => 'editTalk',
        'verbs'      => [
            Request::HTTP_PUT,
        ],
    ],
    [
        'path'       => '/talks/?$',
        'controller' => TalksController::class,
        'action'     => 'getTalkByKeyWord',
        'verbs'      => [
            Request::HTTP_GET,
        ],
    ],
    [
        'path'       => '/talks/[\\d]+/comments/?$',
        'controller' => TalksController::class,
        'action'     => 'getTalkComments',
        'verbs'      => [
            Request::HTTP_GET,
        ],
    ],
    [
        'path'       => '/talks/[\\d]+/starred/?$',
        'controller' => TalksController::class,
        'action'     => 'getTalkStarred',
        'verbs'      => [
            Request::HTTP_GET,
        ],
    ],
    [
        'path'       => '/talks/[\\d]+/?$',
        'controller' => TalksController::class,
        'action'     => 'getAction',
        'verbs'      => [
            Request::HTTP_GET,
        ],
    ],
    [
        'path'       => '/talks(/[\\d]+)/tracks$',
        'controller' => TalksController::class,
        'action'     => 'addTrackToTalk',
        'verbs'      => [
            Request::HTTP_POST,
        ],
    ],
    [
        'path'       => '/talks(/[\\d]+)/tracks(/[\\d]+)$',
        'controller' => TalksController::class,
        'action'     => 'removeTrackFromTalk',
        'verbs'      => [
            Request::HTTP_DELETE,
        ],
    ],
    [
        'path'       => '/talks(/[^/]+)*/?$',
        'controller' => TalksController::class,
        'action'     => 'postAction',
        'verbs'      => [
                Request::HTTP_POST,
            ],
    ],
    [
        'path'       => '/talks(/[\\d]+)/speakers(/[\\d]+)$',
        'controller' => TalksController::class,
        'action'     => 'removeApprovedSpeakerFromTalk',
        'verbs'      => [
            Request::HTTP_DELETE,
        ],
    ],
    [
        'path'       => '/talks/[\\d]+/starred/?$',
        'controller' => TalksController::class,
        'action'     => 'removeStarFromTalk',
        'verbs'      => [
            Request::HTTP_DELETE,
        ],
    ],
    [
        'path'       => '/talks/[\\d]+/?$',
        'controller' => TalksController::class,
        'action'     => 'deleteTalk',
        'verbs'      => [
            Request::HTTP_DELETE,
        ],
    ],
    [
        'path'       => '/token(/[^/]+)*/?$',
        'controller' => TokenController::class,
        'action'     => 'postAction',
        'verbs'      => [
            Request::HTTP_POST,
        ],
    ],
    [
        'path'       => '/token/?$',
        'controller' => TokenController::class,
        'action'     => 'listTokensForUser',
        'verbs'      => [
            Request::HTTP_GET,
        ],
    ],
    [
        'path'       => '/token(/[^/]+)/?$',
        'controller' => TokenController::class,
        'action'     => 'getToken',
        'verbs'      => [
            Request::HTTP_GET,
        ],
    ],
    [
        'path'       => '/token(/[^/]+)*/?$',
        'controller' => TokenController::class,
        'action'     => 'revokeToken',
        'verbs'      => [
            Request::HTTP_DELETE,
        ],
    ],
    [
        'path'       => '/tracks(/[^/]+)*/?$',
        'controller' => TracksController::class,
        'action'     => 'getAction',
        'verbs'      => [
            Request::HTTP_GET,
        ],
    ],
    [
        'path'       => '/tracks(/[\\d]+)/?$',
        'controller' => TracksController::class,
        'action'     => 'editTrack',
        'verbs'      => [
            Request::HTTP_PUT,
        ],
    ],
    [
        'path'       => '/tracks(/[\\d]+)/?$',
        'controller' => TracksController::class,
        'action'     => 'deleteTrack',
        'verbs'      => [
            Request::HTTP_DELETE,
        ],
    ],
    [
        'path'       => '/users/passwords$',
        'controller' => UsersController::class,
        'action'     => 'passwordReset',
        'verbs'      => [
            Request::HTTP_POST,
        ],
    ],
    [
        'path'       => '/users(/[0-9]+)/trusted?$',
        'controller' => UsersController::class,
        'action'     => 'setTrusted',
        'verbs'      => [
            Request::HTTP_POST,
        ],
    ],
    [
        'path'       => '/users(/[^/]+)*/?$',
        'controller' => UsersController::class,
        'action'     => 'getAction',
        'verbs'      => [
            Request::HTTP_GET,
        ],
    ],
    [
        'path'       => '/users(/[^/]+)*/?$',
        'controller' => UsersController::class,
        'action'     => 'postAction',
        'verbs'      => [
            Request::HTTP_POST,
        ],
    ],
    [
        'path'       => '/users(/[^/]+)*/?$',
        'controller' => UsersController::class,
        'action'     => 'deleteUser',
        'verbs'      => [
            Request::HTTP_DELETE,
        ],
    ],
    [
        'path'       => '/users(/[0-9]+)/?$',
        'controller' => UsersController::class,
        'action'     => 'updateUser',
        'verbs'      => [
            Request::HTTP_PUT,
        ],
    ],
    [
        'path'       => '/users/[0-9]+/talk_comments',
        'controller' => UsersController::class,
        'action'     => 'deleteTalkComments',
        'verbs'      => [
            Request::HTTP_DELETE,
        ],
    ],
    [
        'path'       => '/event_comments(/[^/]+)*/?$',
        'controller' => EventCommentsController::class,
        'action'     => 'getComments',
        'verbs'      => [
            Request::HTTP_GET,
        ],
    ],
    [
        'path'       => '/event_comments(/[^/]+)*/reported?$',
        'controller' => EventCommentsController::class,
        'action'     => 'reportComment',
        'verbs'      => [
            Request::HTTP_POST,
        ],
    ],
    [
        'path'       => '/event_comments(/[^/]+)*/reported?$',
        'controller' => EventCommentsController::class,
        'action'     => 'moderateReportedComment',
        'verbs'      => [
            Request::HTTP_PUT,
        ],
    ],
    [
        'path'       => '/talk_comments(/[^/]+)*/?$',
        'controller' => TalkCommentsController::class,
        'action'     => 'getComments',
        'verbs'      => [
            Request::HTTP_GET,
        ],
    ],
    [
        'path'       => '/talk_comments(/[^/]+)*/?$',
        'controller' => TalkCommentsController::class,
        'action'     => 'updateComment',
        'verbs'      => [
            Request::HTTP_PUT,
        ],
    ],
    [
        'path'       => '/emails/verifications',
        'controller' => EmailsController::class,
        'action'     => 'verifications',
        'verbs'      => [
            Request::HTTP_POST,
        ],
    ],
    [
        'path'       => '/emails/reminders/username',
        'controller' => EmailsController::class,
        'action'     => 'usernameReminder',
        'verbs'      => [
            Request::HTTP_POST,
        ],
    ],
    [
        'path'       => '/emails/reminders/password',
        'controller' => EmailsController::class,
        'action'     => 'passwordReset',
        'verbs'      => [
            Request::HTTP_POST,
        ],
    ],
    [
        'path'       => '/twitter/request_token',
        'controller' => TwitterController::class,
        'action'     => 'getRequestToken',
        'verbs'      => [
            Request::HTTP_POST,
        ],
    ],
    [
        'path'       => '/twitter/token',
        'controller' => TwitterController::class,
        'action'     => 'logUserIn',
        'verbs'      => [
            Request::HTTP_POST,
        ],
    ],
    [
        'path'       => '/languages?$',
        'controller' => LanguagesController::class,
        'action'     => 'getAllLanguages',
        'verbs'      => [
            Request::HTTP_GET,
        ],
    ],
    [
        'path'       => '/languages(/[0-9]+)?/?$',
        'controller' => LanguagesController::class,
        'action'     => 'getLanguage',
        'verbs'      => [
            Request::HTTP_GET,
        ],
    ],
    [
        'path'       => '/contact',
        'controller' => ContactController::class,
        'action'     => 'contact',
        'verbs'      => [
            Request::HTTP_POST,
        ],
    ],
    [
        'path'       => '/talk_types/?$',
        'controller' => TalkTypesController::class,
        'action'     => 'getAllTalkTypes',
        'verbs'      => [
            Request::HTTP_GET,
        ],
    ],
    [
        'path'       => '/talk_types(/[0-9]+)?/?$',
        'controller' => TalkTypesController::class,
        'action'     => 'getTalkType',
        'verbs'      => [
            Request::HTTP_GET,
        ],
    ],
    [
        'path'       => '/facebook/token',
        'controller' => FacebookController::class,
        'action'     => 'logUserIn',
        'verbs'      => [
            Request::HTTP_POST,
        ],
    ],
    [
        'path'       => '/applications$',
        'controller' => ApplicationsController::class,
        'action'     => 'listApplications',
        'verbs'      => [
            Request::HTTP_GET,
        ],
    ],
    [
        'path'       => '/applications$',
        'controller' => ApplicationsController::class,
        'action'     => 'createApplication',
        'verbs'      => [
            Request::HTTP_POST,
        ],
    ],
    [
        'path'       => '/applications(/[0-9]+)$',
        'controller' => ApplicationsController::class,
        'action'     => 'getApplication',
        'verbs'      => [
            Request::HTTP_GET,
        ],
    ],
    [
        'path'       => '/applications(/[0-9]+)$',
        'controller' => ApplicationsController::class,
        'action'     => 'editApplication',
        'verbs'      => [
            Request::HTTP_PUT,
        ],
    ],
    [
        'path'       => '/applications(/[0-9]+)$',
        'controller' => ApplicationsController::class,
        'action'     => 'deleteApplication',
        'verbs'      => [
            Request::HTTP_DELETE,
        ],
    ],
    [
        'path'       => '/?$',
        'controller' => DefaultController::class,
        'action'     => 'handle',
    ],
];
