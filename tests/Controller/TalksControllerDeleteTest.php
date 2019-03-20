<?php

namespace Joindin\Api\Test\Controller;

use Joindin\Api\View\ApiView;
use JoindinTest\Inc\mockPDO;
use Joindin\Api\Request;
use Joindin\Api\Model\TalkMapper;
use Joindin\Api\Controller\TalksController;

class TalksControllerDeleteTest extends TalkBase
{
    /**
     * @test
     * @expectedExceptionCode 401
     * @expectedException \Exception
     */
    public function removeStarFromTalkFailsWhenNotLoggedIn()
    {
        $request = new Request(
            [],
            [
                'REQUEST_URI' => 'http://api.dev.joind.in/v2.1/talks/79/starred',
                'REQUEST_METHOD' => 'DELETE'
            ]
        );

        $db = $this->getMockBuilder(mockPDO::class)->getMock();

        $talks_controller = new TalksController();
        $talks_controller->deleteTalkStarred($request, $db);
    }

    /**
     * @test
     */
    public function removeStarFromTalksWhenLoggedIn()
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

        $talkMapper = $this->createTalkMapper($db, $request, 0);
        $talkMapper->method('setUserNonStarred')
            ->willReturn(true);

        $talks_controller = new TalksController();
        $talks_controller->setTalkMapper(
            $talkMapper
        );

        $talks_controller->deleteTalkStarred($request, $db);
    }

    /**
     * @test
     * @expectedExceptionCode 401
     * @expectedException \Exception
     */
    public function deleteTalkWhenNotLoggedIn()
    {
        $request = new Request(
            [],
            [
                'REQUEST_URI' => 'http://api.dev.joind.in/v2.1/talks/79',
                'REQUEST_METHOD' => 'DELETE'
            ]
        );

        $db = $this->getMockBuilder(mockPDO::class)->getMock();

        $talks_controller = new TalksController();
        $talks_controller->deleteTalk($request, $db);
    }

    /**
     * @test
     */
    public function deleteTalkWhichDoesntExist()
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

        $talk_mapper = $this->getMockBuilder(TalkMapper::class)
            ->setConstructorArgs([$db,$request])
            ->getMock();

        $talk_mapper
            ->method('getTalkById')
            ->willReturn(false);

        $talks_controller = new TalksController();
        $talks_controller->setTalkMapper($talk_mapper);

        $view = $this->getMockBuilder(ApiView::class)->getMock();
        $request->method('getView')->willReturn($view);

        $view->method('setHeader')->with('Content-Length', 0);
        $view->method('setResponseCode')->with(204);

        $this->assertNull($talks_controller->deleteTalk($request, $db));
    }

    /**
     * @test
     * @expectedException \Exception
     * @expectedExceptionCode 400
     */
    public function deleteTalkWthNoAdmin()
    {
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

        $talks_controller = new TalksController();
        $talks_controller->setTalkMapper($talk_mapper);


        $talks_controller->deleteTalk($request, $db);
    }

    /**
     * @test
     */
    public function deleteTalkWithAdmin()
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

        $talk_mapper = $this->createTalkMapper($db, $request);
        $talk_mapper
            ->method('thisUserHasAdminOn')
            ->willReturn(true);

        $talks_controller = new TalksController();
        $talks_controller->setTalkMapper($talk_mapper);

        $view = $this->getMockBuilder(ApiView::class)->getMock();
        $request->method('getView')->willReturn($view);

        $view->method('setHeader')->with('Content-Length', 0);
        $view->method('setResponseCode')->with(204);

        $this->assertNull($talks_controller->deleteTalk($request, $db));
    }
}
