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

return [
    [
        'path'       => '/events(/[^/]+)*/hosts',
        'controller' => EventHostsController::class,
        'action'     => 'listHosts',
        'verbs'      =>
            [
                'GET',
            ],
    ],
    [
        'path'       => '/events/[\\d]+/hosts',
        'controller' => EventHostsController::class,
        'action'     => 'addHost',
        'verbs'      =>
            [
                'POST',
            ],
    ],
    [
        'path'       => '/events/[\\d]+/hosts/[\\d]+',
        'controller' => EventHostsController::class,
        'action'     => 'removeHostFromEvent',
        'verbs'      =>
            [
                'DELETE',
            ],
    ],
    [
        'path'       => '/events(/[^/]+)*/comments/reported',
        'controller' => EventCommentsController::class,
        'action'     => 'getReported',
        'verbs'      =>
            [
                'GET',
            ],
    ],
    [
        'path'       => '/events(/[\\d]+)/talk_comments/reported',
        'controller' => TalkCommentsController::class,
        'action'     => 'getReported',
        'verbs'      =>
            [
                'GET',
            ],
    ],
    [
        'path'       => '/events/(\\d+)/claims$',
        'controller' => EventsController::class,
        'action'     => 'pendingClaims',
        'verbs'      =>
            [
                'GET',
            ],
    ],
    [
        'path'       => '/events(/[^/]+)*/?$',
        'controller' => EventsController::class,
        'action'     => 'getAction',
        'verbs'      =>
            [
                'GET',
            ],
    ],
    [
        'path'       => '/events/(\\d+)/tracks$',
        'controller' => EventsController::class,
        'action'     => 'createTrack',
        'verbs'      =>
            [
                'POST',
            ],
    ],
    [
        'path'       => '/events/(\\d+)/approval$',
        'controller' => EventsController::class,
        'action'     => 'approveAction',
        'verbs'      =>
            [
                'POST',
            ],
    ],
    [
        'path'       => '/events/(\\d+)/approval$',
        'controller' => EventsController::class,
        'action'     => 'rejectAction',
        'verbs'      =>
            [
                'DELETE',
            ],
    ],
    [
        'path'       => '/events(/[^/]+)*/comments',
        'controller' => EventCommentsController::class,
        'action'     => 'createComment',
        'verbs'      =>
            [
                'POST',
            ],
    ],
    [
        'path'       => '/events(/[\\d]+)/images',
        'controller' => EventImagesController::class,
        'action'     => 'createImage',
        'verbs'      =>
            [
                'POST',
            ],
    ],
    [
        'path'       => '/events(/[\\d]+)/images',
        'controller' => EventImagesController::class,
        'action'     => 'deleteImage',
        'verbs'      =>
            [
                'DELETE',
            ],
    ],
    [
        'path'       => '/events(/[\\d]+)/talks',
        'controller' => TalksController::class,
        'action'     => 'createTalkAction',
        'verbs'      =>
            [
                'POST',
            ],
    ],
    [
        'path'       => '/events(/[^/]+)*/?$',
        'controller' => EventsController::class,
        'action'     => 'postAction',
        'verbs'      =>
            [
                'POST',
            ],
    ],
    [
        'path'       => '/events(/[^/]+)*/?$',
        'controller' => EventsController::class,
        'action'     => 'putAction',
        'verbs'      =>
            [
                'PUT',
            ],
    ],
    [
        'path'       => '/events(/[^/]+)*/?$',
        'controller' => EventsController::class,
        'action'     => 'deleteAction',
        'verbs'      =>
            [
                'DELETE',
            ],
    ],
    [
        'path'       => '/talks(/[\\d]+)/links/?$',
        'controller' => TalkLinkController::class,
        'action'     => 'getTalkLinks',
        'verbs'      =>
            [
                'GET',
            ],
    ],
    [
        'path'       => '/talks(/[\\d]+)/links/?$',
        'controller' => TalkLinkController::class,
        'action'     => 'addTalkLink',
        'verbs'      =>
            [
                'POST',
            ],
    ],
    [
        'path'       => '/talks(/[\\d]+)/links(/[\\d]+)$',
        'controller' => TalkLinkController::class,
        'action'     => 'getTalkLink',
        'verbs'      =>
            [
                'GET',
            ],
    ],
    [
        'path'       => '/talks(/[\\d]+)/links(/[\\d]+)$',
        'controller' => TalkLinkController::class,
        'action'     => 'removeTalkLink',
        'verbs'      =>
            [
                'DELETE',
            ],
    ],
    [
        'path'       => '/talks(/[\\d]+)/links(/[\\d]+)$',
        'controller' => TalkLinkController::class,
        'action'     => 'updateTalkLink',
        'verbs'      =>
            [
                'PUT',
            ],
    ],
    [
        'path'       => '/talks(/[\\d]+)/speakers/?$',
        'controller' => TalksController::class,
        'action'     => 'getSpeakersForTalk',
        'verbs'      =>
            [
                'GET',
            ],
    ],
    [
        'path'       => '/talks(/[\\d]+)/speakers/?$',
        'controller' => TalksController::class,
        'action'     => 'setSpeakerForTalk',
        'verbs'      =>
            [
                'POST',
            ],
    ],
    [
        'path'       => '/talks(/[\\d]+)/speakers/?$',
        'controller' => TalksController::class,
        'action'     => 'removeSpeakerForTalk',
        'verbs'      =>
            [
                'DELETE',
            ],
    ],
    [
        'path'       => '/talks(/[\\d]+)$',
        'controller' => TalksController::class,
        'action'     => 'editTalk',
        'verbs'      =>
            [
                'PUT',
            ],
    ],
    [
        'path'       => '/talks/?$',
        'controller' => TalksController::class,
        'action'     => 'getTalkByKeyWord',
        'verbs'      =>
            [
                'GET',
            ],
    ],
    [
        'path'       => '/talks/[\\d]+/comments/?$',
        'controller' => TalksController::class,
        'action'     => 'getTalkComments',
        'verbs'      =>
            [
                'GET',
            ],
    ],
    [
        'path'       => '/talks/[\\d]+/starred/?$',
        'controller' => TalksController::class,
        'action'     => 'getTalkStarred',
        'verbs'      =>
            [
                'GET',
            ],
    ],
    [
        'path'       => '/talks/[\\d]+/?$',
        'controller' => TalksController::class,
        'action'     => 'getAction',
        'verbs'      =>
            [
                'GET',
            ],
    ],
    [
        'path'       => '/talks(/[\\d]+)/tracks$',
        'controller' => TalksController::class,
        'action'     => 'addTrackToTalk',
        'verbs'      =>
            [
                'POST',
            ],
    ],
    [
        'path'       => '/talks(/[\\d]+)/tracks(/[\\d]+)$',
        'controller' => TalksController::class,
        'action'     => 'removeTrackFromTalk',
        'verbs'      =>
            [
                'DELETE',
            ],
    ],
    [
        'path'       => '/talks(/[^/]+)*/?$',
        'controller' => TalksController::class,
        'action'     => 'postAction',
        'verbs'      =>
            [
                'POST',
            ],
    ],
    [
        'path'       => '/talks(/[\\d]+)/speakers(/[\\d]+)$',
        'controller' => TalksController::class,
        'action'     => 'removeApprovedSpeakerFromTalk',
        'verbs'      =>
            [
                'DELETE',
            ],
    ],
    [
        'path'       => '/talks/[\\d]+/starred/?$',
        'controller' => TalksController::class,
        'action'     => 'removeStarFromTalk',
        'verbs'      =>
            [
                'DELETE',
            ],
    ],
    [
        'path'       => '/talks/[\\d]+/?$',
        'controller' => TalksController::class,
        'action'     => 'deleteTalk',
        'verbs'      =>
            [
                'DELETE',
            ],
    ],
    [
        'path'       => '/token(/[^/]+)*/?$',
        'controller' => TokenController::class,
        'action'     => 'postAction',
        'verbs'      =>
            [
                'POST',
            ],
    ],
    [
        'path'       => '/token/?$',
        'controller' => TokenController::class,
        'action'     => 'listTokensForUser',
        'verbs'      =>
            [
                'GET',
            ],
    ],
    [
        'path'       => '/token(/[^/]+)/?$',
        'controller' => TokenController::class,
        'action'     => 'getToken',
        'verbs'      =>
            [
                'GET',
            ],
    ],
    [
        'path'       => '/token(/[^/]+)*/?$',
        'controller' => TokenController::class,
        'action'     => 'revokeToken',
        'verbs'      =>
            [
                'DELETE',
            ],
    ],
    [
        'path'       => '/tracks(/[^/]+)*/?$',
        'controller' => TracksController::class,
        'action'     => 'getAction',
        'verbs'      =>
            [
                'GET',
            ],
    ],
    [
        'path'       => '/tracks(/[\\d]+)/?$',
        'controller' => TracksController::class,
        'action'     => 'editTrack',
        'verbs'      =>
            [
                'PUT',
            ],
    ],
    [
        'path'       => '/tracks(/[\\d]+)/?$',
        'controller' => TracksController::class,
        'action'     => 'deleteTrack',
        'verbs'      =>
            [
                'DELETE',
            ],
    ],
    [
        'path'       => '/users/passwords$',
        'controller' => UsersController::class,
        'action'     => 'passwordReset',
        'verbs'      =>
            [
                'POST',
            ],
    ],
    [
        'path'       => '/users(/[0-9]+)/trusted?$',
        'controller' => UsersController::class,
        'action'     => 'setTrusted',
        'verbs'      =>
            [
                'POST',
            ],
    ],
    [
        'path'       => '/users(/[^/]+)*/?$',
        'controller' => UsersController::class,
        'action'     => 'getAction',
        'verbs'      =>
            [
                'GET',
            ],
    ],
    [
        'path'       => '/users(/[^/]+)*/?$',
        'controller' => UsersController::class,
        'action'     => 'postAction',
        'verbs'      =>
            [
                'POST',
            ],
    ],
    [
        'path'       => '/users(/[^/]+)*/?$',
        'controller' => UsersController::class,
        'action'     => 'deleteUser',
        'verbs'      =>
            [
                'DELETE',
            ],
    ],
    [
        'path'       => '/users(/[0-9]+)/?$',
        'controller' => UsersController::class,
        'action'     => 'updateUser',
        'verbs'      =>
            [
                'PUT',
            ],
    ],
    [
        'path'       => '/event_comments(/[^/]+)*/?$',
        'controller' => EventCommentsController::class,
        'action'     => 'getComments',
        'verbs'      =>
            [
                'GET',
            ],
    ],
    [
        'path'       => '/event_comments(/[^/]+)*/reported?$',
        'controller' => EventCommentsController::class,
        'action'     => 'reportComment',
        'verbs'      =>
            [
                'POST',
            ],
    ],
    [
        'path'       => '/event_comments(/[^/]+)*/reported?$',
        'controller' => EventCommentsController::class,
        'action'     => 'moderateReportedComment',
        'verbs'      =>
            [
                'PUT',
            ],
    ],
    [
        'path'       => '/talk_comments(/[^/]+)*/?$',
        'controller' => TalkCommentsController::class,
        'action'     => 'getComments',
        'verbs'      =>
            [
                'GET',
            ],
    ],
    [
        'path'       => '/talk_comments(/[^/]+)*/reported?$',
        'controller' => TalkCommentsController::class,
        'action'     => 'reportComment',
        'verbs'      =>
            [
                'POST',
            ],
    ],
    [
        'path'       => '/talk_comments(/[^/]+)*/reported?$',
        'controller' => TalkCommentsController::class,
        'action'     => 'moderateReportedComment',
        'verbs'      =>
            [
                'PUT',
            ],
    ],
    [
        'path'       => '/talk_comments(/[^/]+)*/?$',
        'controller' => TalkCommentsController::class,
        'action'     => 'updateComment',
        'verbs'      =>
            [
                'PUT',
            ],
    ],
    [
        'path'       => '/emails/verifications',
        'controller' => EmailsController::class,
        'action'     => 'verifications',
        'verbs'      =>
            [
                'POST',
            ],
    ],
    [
        'path'       => '/emails/reminders/username',
        'controller' => EmailsController::class,
        'action'     => 'usernameReminder',
        'verbs'      =>
            [
                'POST',
            ],
    ],
    [
        'path'       => '/emails/reminders/password',
        'controller' => EmailsController::class,
        'action'     => 'passwordReset',
        'verbs'      =>
            [
                'POST',
            ],
    ],
    [
        'path'       => '/twitter/request_token',
        'controller' => TwitterController::class,
        'action'     => 'getRequestToken',
        'verbs'      =>
            [
                'POST',
            ],
    ],
    [
        'path'       => '/twitter/token',
        'controller' => TwitterController::class,
        'action'     => 'logUserIn',
        'verbs'      =>
            [
                'POST',
            ],
    ],
    [
        'path'       => '/languages?$',
        'controller' => LanguagesController::class,
        'action'     => 'getAllLanguages',
        'verbs'      =>
            [
                'GET',
            ],
    ],
    [
        'path'       => '/languages(/[0-9]+)?/?$',
        'controller' => LanguagesController::class,
        'action'     => 'getLanguage',
        'verbs'      =>
            [
                'GET',
            ],
    ],
    [
        'path'       => '/contact',
        'controller' => ContactController::class,
        'action'     => 'contact',
        'verbs'      =>
            [
                'POST',
            ],
    ],
    [
        'path'       => '/talk_types/?$',
        'controller' => TalkTypesController::class,
        'action'     => 'getAllTalkTypes',
        'verbs'      =>
            [
                'GET',
            ],
    ],
    [
        'path'       => '/talk_types(/[0-9]+)?/?$',
        'controller' => TalkTypesController::class,
        'action'     => 'getTalkType',
        'verbs'      =>
            [
                'GET',
            ],
    ],
    [
        'path'       => '/facebook/token',
        'controller' => FacebookController::class,
        'action'     => 'logUserIn',
        'verbs'      =>
            [
                'POST',
            ],
    ],
    [
        'path'       => '/applications$',
        'controller' => ApplicationsController::class,
        'action'     => 'listApplications',
        'verbs'      =>
            [
                'GET',
            ],
    ],
    [
        'path'       => '/applications$',
        'controller' => ApplicationsController::class,
        'action'     => 'createApplication',
        'verbs'      =>
            [
                'POST',
            ],
    ],
    [
        'path'       => '/applications(/[0-9]+)$',
        'controller' => ApplicationsController::class,
        'action'     => 'getApplication',
        'verbs'      =>
            [
                'GET',
            ],
    ],
    [
        'path'       => '/applications(/[0-9]+)$',
        'controller' => ApplicationsController::class,
        'action'     => 'editApplication',
        'verbs'      =>
            [
                'PUT',
            ],
    ],
    [
        'path'       => '/applications(/[0-9]+)$',
        'controller' => ApplicationsController::class,
        'action'     => 'deleteApplication',
        'verbs'      =>
            [
                'DELETE',
            ],
    ],
    [
        'path'       => '/?$',
        'controller' => DefaultController::class,
        'action'     => 'handle',
    ],
];
