<?php

namespace JoindinTest\Controller;

use JoindinTest\Inc\mockPDO;
use PDOStatement;
use PHPUnit_Framework_TestCase;
use Request;
use TalkMapper;
use TalkModel;
use TalksController;
use TalkLinkController;

class TalksControllerTest extends TalkBase
{
    /**
     * Ensures that if the setSpeakerForTalk method is called and no user_id is set,
     * an exception is thrown
     *
     * @return void
     *
     * @test
     * @expectedException        \Exception
     * @expectedExceptionMessage You must be logged in to create data
     */
    public function testClaimTalkWithNoUserIdThrowsException()
    {
        $request = new Request(
            [],
            [
                'REQUEST_URI' => "http://api.dev.joind.in/v2.1/talks/326/speakers",
                'REQUEST_METHOD' => 'POST'
            ]
        );

        $talks_controller = new TalksController();
        $db = $this->getMockBuilder(mockPDO::class)->getMock();

        $talks_controller->setSpeakerForTalk($request, $db);

    }

    /**
     * Ensures that if the setSpeakerForTalk method is called on a non-existent talk,
     * an exception is thrown
     *
     * @return void
     *
     * @expectedException        \Exception
     * @expectedExceptionMessage Talk not found
     */
    public function testClaimNonExistantTalkThrowsException()
    {
        $request = new Request(
            [],
            [
                'REQUEST_URI' => "http://api.dev.joind.in/v2.1/talks/9999/speakers",
                'REQUEST_METHOD' => 'POST'
            ]
        );

        $request->user_id = 2;

        $talks_controller = new TalksController();
        $db = $this->getMockBuilder(mockPDO::class)->getMock();


        $talk_mapper = $this->getMockBuilder('\TalkMapper')
            ->setConstructorArgs(array($db,$request))
            ->getMock();

        $talk_mapper
            ->expects($this->once())
            ->method('getTalkById')
            ->will($this->returnValue(false));

        $talks_controller->setTalkMapper($talk_mapper);

        $event_mapper = $this->createEventMapper($db, $request);
        $talks_controller->setEventMapper($event_mapper);

        $talks_controller->setSpeakerForTalk($request, $db);


    }

    /**
     * Ensures that if the setSpeakerForTalk method is called without a username,
     * an exception is thrown
     *
     * @return void
     *
     * @expectedException        \Exception
     * @expectedExceptionMessage You must provide a display name and a username
     */
    public function testClaimTalkWithoutUsernameThrowsException()
    {
        $request = new Request(
            [],
            [
                'REQUEST_URI' => "http://api.dev.joind.in/v2.1/talks/9999/speakers",
                'REQUEST_METHOD' => 'POST'
            ]
        );

        $request->user_id = 2;
        $request->parameters = [
            'display_name'  => 'Jane Bloggs'
        ];

        $talks_controller = new TalksController();
        $db = $this->getMockBuilder(mockPDO::class)->getMock();

        $talk_mapper = $this->createTalkMapper($db, $request);
        $talks_controller->setTalkMapper($talk_mapper);

        $user_mapper = $this->createUserMapper($db, $request);
        $talks_controller->setUserMapper($user_mapper);

        $event_mapper = $this->createEventMapper($db, $request);
        $talks_controller->setEventMapper($event_mapper);


        $talks_controller->setSpeakerForTalk($request, $db);
    }

    /**
     * Ensures that if the setSpeakerForTalk method is called without a display_name,
     * an exception is thrown
     *
     * @return void
     *
     * @expectedException        \Exception
     * @expectedExceptionMessage You must provide a display name and a username
     */
    public function testClaimTalkWithoutDisplayNameThrowsException()
    {
        $request = new Request(
            [],
            [
                'REQUEST_URI' => "http://api.dev.joind.in/v2.1/talks/9999/speakers",
                'REQUEST_METHOD' => 'POST'
            ]
        );

        $request->user_id = 2;
        $request->parameters = [
            'username'  => 'janebloggs'
        ];

        $talks_controller = new TalksController();
        $db = $this->getMockBuilder(mockPDO::class)->getMock();

        $talk_mapper = $this->createTalkMapper($db, $request);
        $talks_controller->setTalkMapper($talk_mapper);

        $user_mapper = $this->createUserMapper($db, $request);
        $talks_controller->setUserMapper($user_mapper);

        $event_mapper = $this->createEventMapper($db, $request);
        $talks_controller->setEventMapper($event_mapper);

        $talks_controller->setSpeakerForTalk($request, $db);
    }

    /**
     * Ensures that if the setSpeakerForTalk method is called with a
     * display_name that doesn't match a talk speaker, an exception is thrown
     *
     * @return void
     *
     * @expectedException        \Exception
     * @expectedExceptionMessage No speaker matching that name found
     */
    public function testClaimTalkWithInvalidSpeakerThrowsException()
    {
        $request = new Request(
            [],
            [
                'REQUEST_URI' => "http://api.dev.joind.in/v2.1/talks/9999/speakers",
                'REQUEST_METHOD' => 'POST'
            ]
        );

        $request->user_id = 2;
        $request->parameters = [
            'username'      => 'janebloggs',
            'display_name'  =>  'P Sherman'
        ];

        $talks_controller = new TalksController();
        $db = $this->getMockBuilder(mockPDO::class)->getMock();

        $talk_mapper = $this->createTalkMapper($db, $request);
        $talk_mapper
            ->expects($this->once())
            ->method('getSpeakerFromTalk')
            ->will(
                $this->returnValue(false)
            );
        $talks_controller->setTalkMapper($talk_mapper);

        $user_mapper = $this->createUserMapper($db, $request);
        $talks_controller->setUserMapper($user_mapper);

        $event_mapper = $this->createEventMapper($db, $request);
        $talks_controller->setEventMapper($event_mapper);

        $talks_controller->setSpeakerForTalk($request, $db);
    }

    /**
     * Ensures that if the setSpeakerForTalk method is called against a display_name
     * that has been claimed already, and Exception is thrown
     *
     * @return void
     *
     * @expectedException        \Exception
     * @expectedExceptionMessage Talk already claimed
     */
    public function testClaimTalkAlreadyClaimedThrowsException(){
        $request = new Request(
            [],
            [
                'REQUEST_URI' => "http://api.dev.joind.in/v2.1/talks/9999/speakers",
                'REQUEST_METHOD' => 'POST'
            ]
        );
        $request->user_id = 2;
        $request->parameters = [
            'username'      => 'janebloggs',
            'display_name'  => 'P Sherman'
        ];

        $talks_controller = new TalksController();
        $db = $this->getMockBuilder(mockPDO::class)->getMock();

        $talk_mapper = $this->createTalkMapper($db, $request);
        $talk_mapper
            ->expects($this->once())
            ->method('getSpeakerFromTalk')
            ->will(
                $this->returnValue(
                    [
                        'speaker_id'  => 1,
                        'ID'          => 1
                    ]
                )
            );
        $talks_controller->setTalkMapper($talk_mapper);

        $user_mapper = $this->createUserMapper($db, $request);
        $talks_controller->setUserMapper($user_mapper);

        $event_mapper = $this->createEventMapper($db, $request);
        $talks_controller->setEventMapper($event_mapper);

        $talks_controller->setSpeakerForTalk($request, $db);
    }

    /**
     * Ensures that if the setSpeakerForTalk method is called against a username
     * that is different to the logged in user, an Exception is thrown
     *
     * @return void
     *
     * @expectedException        \Exception
     * @expectedExceptionMessage You must be the speaker or event admin to link a user to a talk
     */
    public function testClaimTalkForSomeoneElseThrowsException(){
        $request = new Request(
            [],
            [
                'REQUEST_URI' => "http://api.dev.joind.in/v2.1/talks/9999/speakers",
                'REQUEST_METHOD' => 'POST'
            ]
        );
        $request->user_id = 2;
        $request->parameters = [
            'username'      => 'psherman',
            'display_name'  => 'P Sherman'
        ];

        $talks_controller = new TalksController();
        $db = $this->getMockBuilder(mockPDO::class)->getMock();

        $talk_mapper = $this->createTalkMapper($db, $request);
        $talk_mapper
            ->expects($this->once())
            ->method('getSpeakerFromTalk')
            ->will(
                $this->returnValue(
                    [
                        'speaker_id'  => null,
                        'ID'          => 1
                    ]
                )
            );
        $talk_mapper
            ->expects($this->once())
            ->method('thisUserHasAdminOn')
            ->will($this->returnValue(false));
        $talks_controller->setTalkMapper($talk_mapper);

        $user_mapper = $this->createUserMapper($db, $request);
        $user_mapper
            ->expects($this->once())
            ->method('getUserIdFromUsername')
            ->will($this->returnValue(6));
        $talks_controller->setUserMapper($user_mapper);

        $pending_talk_claim_mapper = $this->getMockBuilder('\PendingTalkClaimMapper')
            ->setConstructorArgs(array($db,$request))
            ->getMock();
        $pending_talk_claim_mapper
            ->expects($this->once())
            ->method('claimExists')
            ->will($this->returnValue(false));

        $talks_controller->setPendingTalkClaimMapper($pending_talk_claim_mapper);

        $event_mapper = $this->createEventMapper($db, $request);
        $talks_controller->setEventMapper($event_mapper);

        $talks_controller->setSpeakerForTalk($request, $db);
    }

    /**
     * Ensures that if the setSpeakerForTalk method is called as a host against a username
     * that doesn't exist, an Exception is thrown
     *
     * @return void
     *
     * @expectedException        \Exception
     * @expectedExceptionMessage Specified user not found
     */
    public function testAssignTalkAsHostToNonExistentUserThrowsException(){
        $request = new Request(
            [],
            [
                'REQUEST_URI' => "http://api.dev.joind.in/v2.1/talks/9999/speakers",
                'REQUEST_METHOD' => 'POST'
            ]
        );
        $request->user_id = 2;
        $request->parameters = [
            'username'      => 'psherman',
            'display_name'  => 'P Sherman'
        ];

        $talks_controller = new TalksController();
        $db = $this->getMockBuilder(mockPDO::class)->getMock();

        $talk_mapper = $this->createTalkMapper($db, $request);
        $talk_mapper
            ->expects($this->once())
            ->method('getSpeakerFromTalk')
            ->will(
                $this->returnValue(
                    [
                        'speaker_id'  => null,
                        'ID'          => 1
                    ]
                )
            );
        $talks_controller->setTalkMapper($talk_mapper);

        $user_mapper = $this->createUserMapper($db, $request);
        $user_mapper
            ->expects($this->once())
            ->method('getUserIdFromUsername')
            ->will($this->returnValue(false));
        $talks_controller->setUserMapper($user_mapper);

        $event_mapper = $this->createEventMapper($db, $request);
        $talks_controller->setEventMapper($event_mapper);

        $talks_controller->setSpeakerForTalk($request, $db);
    }

    /**
     * Ensures that if the setSpeakerForTalk method is called and user_id is not an admin
     * then as long as they are claiming for themselves, the method succeeds
     *
     * @return void
     */
    public function testClaimTalkAsUserIsSuccessful()
    {
        $request = new Request(
            [],
            [
                'REQUEST_URI' => "http://api.dev.joind.in/v2.1/talks/9999/speakers",
                'REQUEST_METHOD' => 'POST'
            ]
        );
        $request->user_id = 2;
        $request->parameters = [
            'username'      => 'janebloggs',
            'display_name'  => 'Jane Bloggs'
        ];

        $talks_controller = new TalksController();
        $db = $this->getMockBuilder(mockPDO::class)->getMock();

        $talk_mapper = $this->createTalkMapper($db, $request);
        $talk_mapper
            ->expects($this->once())
            ->method('getSpeakerFromTalk')
            ->will(
                $this->returnValue(
                    [
                        'speaker_id'  => null,
                        'ID'          => 1
                    ]
                )
            );
        $talks_controller->setTalkMapper($talk_mapper);

        $user_mapper = $this->createUserMapper($db, $request);
        $user_mapper
            ->expects($this->once())
            ->method('getUserIdFromUsername')
            ->will($this->returnValue(2));
        $talks_controller->setUserMapper($user_mapper);

        $pending_talk_claim_mapper = $this->getMockBuilder('\PendingTalkClaimMapper')
            ->setConstructorArgs(array($db,$request))
            ->getMock();
        $pending_talk_claim_mapper
            ->expects($this->once())
            ->method('claimExists')
            ->will($this->returnValue(false));

        $pending_talk_claim_mapper
            ->expects($this->once())
            ->method('claimTalkAsSpeaker')
            ->will($this->returnValue(true));
        $talks_controller->setPendingTalkClaimMapper($pending_talk_claim_mapper);

        $event_mapper = $this->createEventMapper($db, $request);
        $talks_controller->setEventMapper($event_mapper);

        $this->assertNull($talks_controller->setSpeakerForTalk($request, $db));

    }

    /**
     * Ensures that if the setSpeakerForTalk method is called and user_id is a host
     * then as long as the username exists, the method succeeds
     *
     * @return void
     */
    public function testAssignTalkAsHostIsSuccessful()
    {
        $request = new Request(
            [],
            [
                'REQUEST_URI' => "http://api.dev.joind.in/v2.1/talks/9999/speakers",
                'REQUEST_METHOD' => 'POST'
            ]
        );
        $request->user_id = 2;
        $request->parameters = [
            'username'      => 'psherman',
            'display_name'  => 'P Sherman'
        ];

        $talks_controller = new TalksController();
        $db = $this->getMockBuilder(mockPDO::class)->getMock();

        $talk_mapper = $this->createTalkMapper($db, $request);
        $talk_mapper
            ->expects($this->once())
            ->method('getSpeakerFromTalk')
            ->will(
                $this->returnValue(
                    [
                        'speaker_id'  => null,
                        'ID'          => 1
                    ]
                )
            );
        $talk_mapper
            ->expects($this->once())
            ->method('thisUserHasAdminOn')
            ->will($this->returnValue(true));
        $talks_controller->setTalkMapper($talk_mapper);

        $user_mapper = $this->createUserMapper($db, $request);
        $user_mapper
            ->expects($this->once())
            ->method('getUserIdFromUsername')
            ->will($this->returnValue(1));
        $talks_controller->setUserMapper($user_mapper);

        $pending_talk_claim_mapper = $this->getMockBuilder('\PendingTalkClaimMapper')
            ->setConstructorArgs(array($db,$request))
            ->getMock();
        $pending_talk_claim_mapper
            ->expects($this->once())
            ->method('claimExists')
            ->will($this->returnValue(false));
        $pending_talk_claim_mapper
            ->expects($this->once())
            ->method('assignTalkAsHost')
            ->will($this->returnValue(true));
        $talks_controller->setPendingTalkClaimMapper($pending_talk_claim_mapper);

        $event_mapper = $this->createEventMapper($db, $request);
        $talks_controller->setEventMapper($event_mapper);

        $this->assertNull($talks_controller->setSpeakerForTalk($request, $db));

    }

    /**
     * Ensures that if the setSpeakerForTalk method is called by the same user who made the claim
     * then an exception is thrown
     *
     * @test
     * @expectedException        \Exception
     * @expectedExceptionMessage You must be an event admin to approve this claim
     */
    public function testApproveAssignmentAsUserWhoClaimedThrowsException()
    {
        $request = new Request(
            [],
            [
                'REQUEST_URI' => "http://api.dev.joind.in/v2.1/talks/9999/speakers",
                'REQUEST_METHOD' => 'POST'
            ]
        );
        $request->user_id = 2;
        $request->parameters = [
            'username'      => 'janebloggs',
            'display_name'  => 'Jane Bloggs'
        ];

        $talks_controller = new TalksController();
        $db = $this->getMockBuilder(mockPDO::class)->getMock();

        $talk_mapper = $this->createTalkMapper($db, $request);
        $talk_mapper
            ->expects($this->once())
            ->method('getSpeakerFromTalk')
            ->will(
                $this->returnValue(
                    [
                        'speaker_id'  => null,
                        'ID'          => 1
                    ]
                )
            );
        $talks_controller->setTalkMapper($talk_mapper);

        $user_mapper = $this->createUserMapper($db, $request);
        $user_mapper
            ->expects($this->once())
            ->method('getUserIdFromUsername')
            ->will($this->returnValue(2));
        $talk_mapper
            ->expects($this->once())
            ->method('thisUserHasAdminOn')
            ->will($this->returnValue(false));
        $talks_controller->setUserMapper($user_mapper);

        $pending_talk_claim_mapper = $this->getMockBuilder('\PendingTalkClaimMapper')
            ->setConstructorArgs(array($db,$request))
            ->getMock();
        $pending_talk_claim_mapper
            ->expects($this->once())
            ->method('claimExists')
            ->will($this->returnValue(\PendingTalkClaimMapper::SPEAKER_CLAIM));

        $talks_controller->setPendingTalkClaimMapper($pending_talk_claim_mapper);

        $event_mapper = $this->createEventMapper($db, $request);
        $talks_controller->setEventMapper($event_mapper);

        $talks_controller->setSpeakerForTalk($request, $db);
    }


    /**
     * Ensures that if the setSpeakerForTalk method is called by the host who assigned the talk
     * then an exception is thrown
     *
     * @test
     * @expectedException        \Exception
     * @expectedExceptionMessage You must be the talk speaker to approve this assignment
     */
    public function testApproveClaimAsHostWhoAssignedThrowsException()
    {
        $request = new Request(
            [],
            [
                'REQUEST_URI' => "http://api.dev.joind.in/v2.1/talks/9999/speakers",
                'REQUEST_METHOD' => 'POST'
            ]
        );
        $request->user_id = 2;
        $request->parameters = [
            'username'      => 'psherman',
            'display_name'  => 'P Sherman'
        ];

        $talks_controller = new TalksController();
        $db = $this->getMockBuilder(mockPDO::class)->getMock();

        $talk_mapper = $this->createTalkMapper($db, $request);
        $talk_mapper
            ->expects($this->once())
            ->method('getSpeakerFromTalk')
            ->will(
                $this->returnValue(
                    [
                        'speaker_id'  => null,
                        'ID'          => 1
                    ]
                )
            );
        $talks_controller->setTalkMapper($talk_mapper);

        $user_mapper = $this->createUserMapper($db, $request);
        $user_mapper
            ->expects($this->once())
            ->method('getUserIdFromUsername')
            ->will($this->returnValue(1));
        $talks_controller->setUserMapper($user_mapper);

        $pending_talk_claim_mapper = $this->getMockBuilder('\PendingTalkClaimMapper')
            ->setConstructorArgs(array($db,$request))
            ->getMock();
        $pending_talk_claim_mapper
            ->expects($this->once())
            ->method('claimExists')
            ->will($this->returnValue(\PendingTalkClaimMapper::HOST_ASSIGN));


        $talks_controller->setPendingTalkClaimMapper($pending_talk_claim_mapper);

        $event_mapper = $this->createEventMapper($db, $request);
        $talks_controller->setEventMapper($event_mapper);
        $talks_controller->setSpeakerForTalk($request, $db);
    }

    /**
     * Ensures that if the setSpeakerForTalk method is called by a user who has had a talk
     * assigned to them then it succeeds
     */
    public function testApproveAssignmentAsUserSucceeds()
    {
        $request = new Request(
            [],
            [
                'REQUEST_URI' => "http://api.dev.joind.in/v2.1/talks/9999/speakers",
                'REQUEST_METHOD' => 'POST'
            ]
        );
        $request->user_id = 2;
        $request->parameters = [
            'username'      => 'janebloggs',
            'display_name'  => 'Jane Bloggs',
        ];

        $talks_controller = new TalksController();
        $db = $this->getMockBuilder(mockPDO::class)->getMock();

        $talk_mapper = $this->createTalkMapper($db, $request);
        $talk_mapper
            ->expects($this->once())
            ->method('getSpeakerFromTalk')
            ->will(
                $this->returnValue(
                    [
                        'speaker_id'  => null,
                        'ID'          => 1
                    ]
                )
            );
        $talk_mapper
            ->expects($this->once())
            ->method('assignTalkToSpeaker')
            ->will($this->returnValue(true));
        $talks_controller->setTalkMapper($talk_mapper);

        $user_mapper = $this->createUserMapper($db, $request);
        $user_mapper
            ->expects($this->once())
            ->method('getUserIdFromUsername')
            ->will($this->returnValue(2));
        $talks_controller->setUserMapper($user_mapper);

        $pending_talk_claim_mapper = $this->getMockBuilder('\PendingTalkClaimMapper')
            ->setConstructorArgs(array($db,$request))
            ->getMock();
        $pending_talk_claim_mapper
            ->expects($this->once())
            ->method('claimExists')
            ->will($this->returnValue(\PendingTalkClaimMapper::HOST_ASSIGN));
        $pending_talk_claim_mapper
            ->expects($this->once())
            ->method('approveAssignmentAsSpeaker')
            ->will($this->returnValue(true));



        $talks_controller->setPendingTalkClaimMapper($pending_talk_claim_mapper);

        $event_mapper = $this->createEventMapper($db, $request);
        $talks_controller->setEventMapper($event_mapper);

        $this->assertNull($talks_controller->setSpeakerForTalk($request, $db));

    }


    /**
     * Ensures that if the setSpeakerForTalk method is called by a host
     * in response to a claimed talk then it succeeds
     */
    public function testApproveClaimAsHostSucceeds()
    {
        $request = new Request(
            [],
            [
                'REQUEST_URI' => "http://api.dev.joind.in/v2.1/talks/9999/speakers",
                'REQUEST_METHOD' => 'POST'
            ]
        );
        $request->user_id = 2;
        $request->parameters = [
            'username'      => 'psherman',
            'display_name'  => 'P Sherman',
        ];

        $talks_controller = new TalksController();
        $db = $this->getMockBuilder(mockPDO::class)->getMock();

        $talk_mapper = $this->createTalkMapper($db, $request);
        $talk_mapper
            ->expects($this->once())
            ->method('getSpeakerFromTalk')
            ->will(
                $this->returnValue(
                    [
                        'speaker_id'  => null,
                        'ID'          => 1
                    ]
                )
            );
        $talk_mapper
            ->expects($this->once())
            ->method('thisUserHasAdminOn')
            ->will($this->returnValue(true));
        $talk_mapper
            ->expects($this->once())
            ->method('assignTalkToSpeaker')
            ->will($this->returnValue(true));

        $talks_controller->setTalkMapper($talk_mapper);

        $user_mapper = $this->createUserMapper($db, $request);
        $user_mapper
            ->expects($this->once())
            ->method('getUserIdFromUsername')
            ->will($this->returnValue(1));
        $talks_controller->setUserMapper($user_mapper);

        $pending_talk_claim_mapper = $this->getMockBuilder('\PendingTalkClaimMapper')
            ->setConstructorArgs(array($db,$request))
            ->getMock();
        $pending_talk_claim_mapper
            ->expects($this->once())
            ->method('claimExists')
            ->will($this->returnValue(\PendingTalkClaimMapper::SPEAKER_CLAIM));
        $pending_talk_claim_mapper
            ->expects($this->once())
            ->method('approveClaimAsHost')
            ->will($this->returnValue(true));


        $talks_controller->setPendingTalkClaimMapper($pending_talk_claim_mapper);

        $event_mapper = $this->createEventMapper($db, $request);
        $talks_controller->setEventMapper($event_mapper);

        $this->assertNull($talks_controller->setSpeakerForTalk($request, $db));

    }

    /**
     * Ensures that if the setSpeakerForTalk method is called by a host who rejects the speaker
     * in response to a claimed talk then it succeeds
     */
    public function testRejectClaimAsHostSucceeds()
    {
        $request = new Request(
            [],
            [
                'REQUEST_URI' => "http://api.dev.joind.in/v2.1/talks/9999/speakers",
                'REQUEST_METHOD' => 'DELETE'
            ]
        );

        $request->user_id = 2;
        $request->parameters = [
            'username'      => 'psherman',
            'display_name'  => 'P Sherman',
        ];

        $talks_controller = new TalksController();
        $db = $this->getMockBuilder(mockPDO::class)->getMock();

        $talk_mapper = $this->createTalkMapper($db, $request);
        $talk_mapper
            ->expects($this->once())
            ->method('getSpeakerFromTalk')
            ->will(
                $this->returnValue(
                    [
                        'speaker_id'  => null,
                        'ID'          => 1
                    ]
                )
            );
        $talk_mapper
            ->expects($this->once())
            ->method('thisUserHasAdminOn')
            ->will($this->returnValue(true));

        $talks_controller->setTalkMapper($talk_mapper);

        $user_mapper = $this->getMockBuilder('\UserMapper')
            ->setConstructorArgs(array($db,$request))
            ->getMock();

        $user_mapper
            ->expects($this->once())
            ->method('getUserIdFromUsername')
            ->will($this->returnValue(1));
        $talks_controller->setUserMapper($user_mapper);

        $pending_talk_claim_mapper = $this->getMockBuilder('\PendingTalkClaimMapper')
            ->setConstructorArgs(array($db,$request))
            ->getMock();
        $pending_talk_claim_mapper
            ->expects($this->once())
            ->method('claimExists')
            ->will($this->returnValue(\PendingTalkClaimMapper::SPEAKER_CLAIM));
        $pending_talk_claim_mapper
            ->expects($this->once())
            ->method('rejectClaimAsHost')
            ->will($this->returnValue(true));


        $talks_controller->setPendingTalkClaimMapper($pending_talk_claim_mapper);

        $event_mapper = $this->createEventMapper($db, $request);
        $talks_controller->setEventMapper($event_mapper);

        $this->assertTrue($talks_controller->removeSpeakerForTalk($request, $db));

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

        $talk_mapper = $this->createTalkMapper($db, $request);

        $expected = [
            ['slides_link' => 'http://slideshare.net'],
            ['code_link' => 'https://github.com/link/to/repo'],
        ];

        $talk_mapper
            ->method('getTalkMediaLinks')
            ->willReturn($expected);

        $talks_controller = new TalkLinkController();
        $talks_controller->setTalkMapper($talk_mapper);

        $output = $talks_controller->getTalkLinks($request, $db);
        $this->assertSame(
            $expected,
            $output['talk_links']
        );
    }

    public function testGetTalkMapperReturnsTalkMapper()
    {
        $request = new Request(
            [],
            [
                'REQUEST_URI' => 'http://api.dev.joind.in/v2.1/talks/3links',
                'REQUEST_METHOD' => 'GET'
            ]
        );

        $db = $this->getMockBuilder(mockPDO::class)->getMock();


        $talks_controller = new TalkLinkController();
        $talk_mapper = $talks_controller->getTalkMapper($db, $request);
        $this->assertInstanceOf(TalkMapper::class, $talk_mapper);
        $this->assertSame($talk_mapper, $talks_controller->getTalkMapper($db, $request));
    }


    public function testVerboseTalkOutput()
    {
        $request = new Request(
            [],
            [
                'REQUEST_URI' => 'http://api.dev.joind.in/v2.1/talks/3?verbose=yes',
                'REQUEST_METHOD' => 'GET'
            ]
        );

        $request->parameters = [
            'verbose'      => 'yes',
        ];

        $db = $this->getMockBuilder(mockPDO::class)->getMock();

        $talk_mapper = $this->createVerboseTalkMapper($db, $request);

        $expected = [
            ['slides_link' => 'http://slideshare.net'],
            ['code_link' => 'https://github.com'],
        ];

        $talks_controller = new TalksController();
        $talks_controller->setTalkMapper($talk_mapper);

        $output = $talks_controller->getAction($request, $db);

        $this->assertSame(
            $expected,
            $output['talks'][0]['talk_media']
        );
        $this->assertSame(
            'http://slideshare.net',
            $output['talks'][0]['slides_link']
        );
    }
}
