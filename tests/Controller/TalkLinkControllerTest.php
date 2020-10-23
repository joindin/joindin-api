<?php

namespace Joindin\Api\Test\Controller;

use Exception;
use Joindin\Api\Controller\TalkLinkController;
use Joindin\Api\Model\TalkMapper;
use Joindin\Api\Request;
use Joindin\Api\Test\MapperFactoryForTests;
use Joindin\Api\Test\Mock\mockPDO;
use Teapot\StatusCode\Http;

final class TalkLinkControllerTest extends TalkBase
{
    /**
     * @var TalkLinkController $talks_controller
     */
    private $talks_controller;
    private $request;
    private $db;
    private $mapperFactoryForTests;

    /**
     * Test sending delete link where the link id is not found
     */
    public function testDeleteTalkLinkWithInvalidID()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Talk Link ID not found');
        $this->expectExceptionCode(Http::NOT_FOUND);

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
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('You do not have permission to add links to this talk');
        $this->expectExceptionCode(Http::FORBIDDEN);

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
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('ID not found');
        $this->expectExceptionCode(Http::NOT_FOUND);

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
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Missing required fields URL OR Display Name');
        $this->expectExceptionCode(Http::BAD_REQUEST);

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
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('The Link has not been inserted');
        $this->expectExceptionCode(Http::BAD_REQUEST);

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
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Update of Link ID Failed');
        $this->expectExceptionCode(Http::INTERNAL_SERVER_ERROR);

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
            'username' => 'janebloggs',
            'display_name' => 'P Sherman'
        ];

        $this->mapperFactory = new MapperFactoryForTests();
        $this->talks_controller = new TalkLinkController([], $this->mapperFactory);
        $this->db = $this->getMockBuilder(mockPDO::class)->getMock();

        $this->talk_mapper = $this->createTalkMapper($this->db, $this->request);
        $this->mapperFactory->setMapperMockAs(TalkMapper::class, $this->talk_mapper);
    }

    public function testDifferentTalkMedia()
    {
        $request = new Request(
            [],
            [
                'REQUEST_URI' => 'http://api.dev.joind.in/v2.1/talks/3links',
                'REQUEST_METHOD' => 'GET'
            ]
        );

        $db = $this->getMockBuilder(mockPDO::class)->getMock();

        $this->talk_mapper = $this->createTalkMapper($db, $request);

        $expected = [
            ['slides_link' => 'http://slideshare.net'],
            ['code_link' => 'https://github.com/link/to/repo'],
        ];

        $this->talk_mapper
            ->method('getTalkMediaLinks')
            ->willReturn($expected);

        $this->mapperFactory = new MapperFactoryForTests();
        $talks_controller = new TalkLinkController([], $this->mapperFactory);
        $this->mapperFactory->setMapperMockAs(TalkMapper::class, $this->talk_mapper);

        $output = $talks_controller->getTalkLinks($request, $db);
        $this->assertSame(
            $expected,
            $output['talk_links']
        );
    }

    public function authenticateAsSpeaker()
    {
        $this->talk_mapper
            ->expects($this->once())
            ->method("isUserASpeakerOnTalk")
            ->willReturn(true);
    }
}
