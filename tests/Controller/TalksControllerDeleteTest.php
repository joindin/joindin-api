<?php

namespace Joindin\Api\Test\Controller;

use Exception;
use Joindin\Api\Controller\TalksController;
use Joindin\Api\Request;
use Joindin\Api\Service\NullSpamCheckService;
use Joindin\Api\View\ApiView;
use Joindin\Api\Test\Mock\mockPDO;
use Teapot\StatusCode\Http;

final class TalksControllerDeleteTest extends TalkBase
{
    private $talkMapper;

    /**
     * @group uses_pdo
     */
    public function testRemoveStarFromTalkFailsWhenNotLoggedIn(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('You must be logged in to remove data');
        $this->expectExceptionCode(Http::UNAUTHORIZED);

        $request = new Request(
            [],
            [
                'REQUEST_URI' => 'http://api.dev.joind.in/v2.1/talks/79/starred',
                'REQUEST_METHOD' => 'DELETE'
            ]
        );

        $db = $this->getMockBuilder(mockPDO::class)->getMock();

        $talks_controller = new TalksController(new NullSpamCheckService());

        $talks_controller->removeStarFromTalk($request, $db);
    }

    public function testRemoveStarFromTalksWhenLoggedIn(): void
    {
        $request = new Request(
            [],
            [
                'REQUEST_URI' => 'http://api.dev.joind.in/v2.1/talks/79/starred',
                'REQUEST_METHOD' => 'DELETE'
            ]
        );
        $request->user_id = 2;
        $request->parameters = [
            'username'      => 'psherman',
            'display_name'  => 'P Sherman',
        ];

        $db = $this->getMockBuilder(mockPDO::class)->getMock();

        $this->talkMapper = $this->createTalkMapper($db, $request, 0);
        $this->talkMapper->method('setUserNonStarred')
            ->willReturn(true);

        $talks_controller = new TalksController(new NullSpamCheckService());

        $talks_controller->setTalkMapper($this->talkMapper);

        $talks_controller->removeStarFromTalk($request, $db);
    }

    /**
     * @group uses_pdo
     */
    public function testDeleteTalkWhenNotLoggedIn(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('You must be logged in to remove data');
        $this->expectExceptionCode(Http::UNAUTHORIZED);

        $request = new Request(
            [],
            [
                'REQUEST_URI' => 'http://api.dev.joind.in/v2.1/talks/79',
                'REQUEST_METHOD' => 'DELETE'
            ]
        );

        $db = $this->getMockBuilder(mockPDO::class)->getMock();

        $talks_controller = new TalksController(new NullSpamCheckService());

        $talks_controller->deleteTalk($request, $db);
    }

    public function testDeleteTalkWhichDoesntExist(): void
    {
        $httpRequest = [
            'REQUEST_URI' => 'http://api.dev.joind.in/v2.1/talks/79',
            'REQUEST_METHOD' => 'DELETE'
        ];
        $request = $this->getMockBuilder(Request::class)
            ->setConstructorArgs([[], $httpRequest ])
            ->getMock();

        $request->user_id = 2;
        $request->parameters = [
            'username'      => 'psherman',
            'display_name'  => 'P Sherman',
        ];

        $db = $this->getMockBuilder(mockPDO::class)->getMock();

        $this->talk_mapper
            ->method('getTalkById')
            ->willReturn(false);

        $talks_controller = new TalksController(new NullSpamCheckService());

        $talks_controller->setTalkMapper($this->talk_mapper);

        $view = $this->getMockBuilder(ApiView::class)->getMock();
        $request->method('getView')->willReturn($view);

        $view->method('setHeader')->with('Content-Length', 0);
        $view->method('setResponseCode')->with(Http::NO_CONTENT);

        $this->assertNull($talks_controller->deleteTalk($request, $db));
    }

    public function testDeleteTalkWthNoAdmin(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('You do not have permission to do that');
        $this->expectExceptionCode(Http::BAD_REQUEST);

        $request = new Request(
            [],
            [
                'REQUEST_URI' => 'http://api.dev.joind.in/v2.1/talks/79',
                'REQUEST_METHOD' => 'DELETE'
            ]
        );

        $request->user_id = 2;
        $request->parameters = [
            'username'      => 'psherman',
            'display_name'  => 'P Sherman',
        ];

        $db = $this->getMockBuilder(mockPDO::class)->getMock();

        $talk_mapper = $this->createTalkMapper($db, $request);

        $talks_controller = new TalksController(new NullSpamCheckService());

        $talks_controller->setTalkMapper($talk_mapper);

        $talks_controller->deleteTalk($request, $db);
    }

    public function testDeleteTalkWithAdmin(): void
    {
        $httpRequest = [
            'REQUEST_URI' => 'http://api.dev.joind.in/v2.1/talks/79',
            'REQUEST_METHOD' => 'DELETE'
        ];
        $request = $this->getMockBuilder(Request::class)
            ->setConstructorArgs([[], $httpRequest ])
            ->getMock();

        $request->user_id = 2;
        $request->parameters = [
            'username'      => 'psherman',
            'display_name'  => 'P Sherman',
        ];

        $db = $this->getMockBuilder(mockPDO::class)->getMock();

        $this->talk_mapper
            ->method('thisUserHasAdminOn')
            ->willReturn(true);

        $talks_controller = new TalksController(new NullSpamCheckService());

        $talks_controller->setTalkMapper($this->talk_mapper);

        $view = $this->getMockBuilder(ApiView::class)->getMock();
        $request->method('getView')->willReturn($view);

        $view->method('setHeader')->with('Content-Length', 0);
        $view->method('setResponseCode')->with(Http::NO_CONTENT);

        $this->assertNull($talks_controller->deleteTalk($request, $db));
    }
}
