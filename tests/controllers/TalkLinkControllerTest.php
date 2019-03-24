<?php

namespace JoindinTest\Controller;

use JoindinTest\Inc\mockPDO;
use Request;
use TalkLinkController;

class TalkLinkControllerTest extends TalkBase
{

    /**
     * Test sending delete link where the link id is not found
     */
    public function testDeleteTalkLinkWithInvalidID()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionCode(404);

        $this->makeRequest(
            'http://api.dev.joind.in/v2.1/talks/3/links/1234',
            'DELETE'
        );

        $this->authenticateAsSpeaker();

        $this->talk_mapper
            ->expects($this->once())
            ->method("removeTalkLink")
            ->willReturn(false);


        $this->assertTrue(
            $this->talks_controller->removeTalkLink(
                $this->request,
                $this->db
            )
        );
    }

    /**
     * Test sending delete link
     */
    public function testDeleteTalkLink()
    {

        $this->makeRequest(
            'http://api.dev.joind.in/v2.1/talks/3/links/1234',
            'DELETE'
        );

        $this->authenticateAsSpeaker();

        $this->talk_mapper
            ->expects($this->once())
            ->method("removeTalkLink")
            ->willReturn(true);

        $this->assertTrue(
            $this->talks_controller->removeTalkLink(
                $this->request,
                $this->db
            )
        );
    }

    /**
     * Test sending delete link with no permissions
     */
    public function testDeleteTalkLinkNoPermissions()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionCode(403);

        $this->makeRequest(
            'http://api.dev.joind.in/v2.1/talks/3/links/1234',
            'DELETE'
        );

        $this->talks_controller->removeTalkLink($this->request, $this->db);
    }

    public function testGetTalkLink()
    {
        $this->makeRequest(
            'http://api.dev.joind.in/v2.1/talks/3/links/1234',
            'GET'
        );

        $expected = [
            'id' => 1234,
            'display_name' => 'slides_link',
            'url' => 'http://url'
        ];

        $this->talk_mapper
            ->expects($this->once())
            ->method("getTalkMediaLinks")
            ->willReturn([$expected]);

        $this->assertEquals(
            $expected,
            $this->talks_controller->getTalkLink($this->request, $this->db)
        );
    }

    public function testGetTalkLinkFails()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionCode(404);

        $this->makeRequest(
            'http://api.dev.joind.in/v2.1/talks/3/links/1234',
            'GET'
        );

        $this->talk_mapper
            ->expects($this->once())
            ->method("getTalkMediaLinks")
            ->willReturn([]);

        $this->talks_controller->getTalkLink(
            $this->request,
            $this->db
        );
    }

    public function testAddTalkLink()
    {
        $this->makeRequest(
            'http://api.dev.joind.in/v2.1/talks/3/links/1234',
            'POST'
        );

        $this->request->parameters = $this->request->parameters + [
            'display_name' => 'slides_link',
            'url' => 'https://slides_url.com',
        ];

        $this->authenticateAsSpeaker();

        $this->talk_mapper
            ->expects($this->once())
            ->method("addTalkLink")
            ->willReturn(12);

        $this->talks_controller->addTalkLink(
            $this->request,
            $this->db
        );
    }

    public function testAddTalkLinkWithInvalidData()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionCode(400);

        $this->makeRequest(
            'http://api.dev.joind.in/v2.1/talks/3/links/1234',
            'POST'
        );

        $this->authenticateAsSpeaker();

        $this->talks_controller->addTalkLink(
            $this->request,
            $this->db
        );
    }

    public function testAddTalkLinkFails()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionCode(400);

        $this->makeRequest(
            'http://api.dev.joind.in/v2.1/talks/3/links/1234',
            'POST'
        );

        $this->request->parameters = $this->request->parameters + [
            'display_name' => 'slides_link',
            'url' => 'https://slides_url.com',
        ];

        $this->talk_mapper
            ->expects($this->once())
            ->method("addTalkLink")
            ->willReturn(false);

        $this->authenticateAsSpeaker();

        $this->talks_controller->addTalkLink(
            $this->request,
            $this->db
        );
    }

    public function testUpdateTalkLink()
    {
        $this->makeRequest(
            'http://api.dev.joind.in/v2.1/talks/3/links/1234',
            'PUT'
        );

        $this->authenticateAsSpeaker();
        $this->talk_mapper
            ->expects($this->once())
            ->method("updateTalkLink")
            ->willReturn(true);

        $this->talks_controller->updateTalkLink(
            $this->request,
            $this->db
        );
    }

    public function testUpdateTalkLinkFails()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionCode(500);

        $this->makeRequest(
            'http://api.dev.joind.in/v2.1/talks/3/links/1234',
            'PUT'
        );

        $this->authenticateAsSpeaker();

        $this->talks_controller->updateTalkLink(
            $this->request,
            $this->db
        );
    }

    public function makeRequest($url, $method)
    {
        $this->request = new Request(
            [],
            [
                'REQUEST_URI' => $url,
                'REQUEST_METHOD' => $method
            ]
        );

        $this->request->user_id = 2;
        $this->request->parameters = [
            'username'      => 'janebloggs',
            'display_name'  =>  'P Sherman'
        ];

        $this->talks_controller = new TalkLinkController();
        $this->db = $this->getMockBuilder(mockPDO::class)->getMock();

        $this->talk_mapper = $this->createTalkMapper($this->db, $this->request);
        $this->talks_controller->setTalkMapper($this->talk_mapper);
    }

    public function authenticateAsSpeaker()
    {
        $this->talk_mapper
            ->expects($this->once())
            ->method("isUserASpeakerOnTalk")
            ->willReturn(true);
    }
}
