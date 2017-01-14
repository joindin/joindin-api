<?php

namespace JoindinTest\Controller;

use JoindinTest\Inc\mockPDO;
use PDOStatement;
use PHPUnit_Framework_TestCase;
use Request;
use TalksController;

class TalksControllerTest extends PHPUnit_Framework_TestCase
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
                'REQUEST_URI' => 'http://api.dev.joind.in/v2.1/talks/3?verbose=yes',
                'REQUEST_METHOD' => 'GET'
            ]
        );
        $request->user_id = 1;
        $request->parameters = [
            'username'      => 'psherman',
            'display_name'  => 'P Sherman',
            'verbose'       => 'yes',
        ];
        $db = $this->getMockBuilder(mockPDO::class)->getMock();
        $stmt = $this->getMockBuilder(PDOStatement::class)
                ->setMethods(array("execute", 'fetchAll'))
                ->getMock();

        $stmt->method("execute")->willReturn(true);

        $stmt->method("fetchAll")->will(
            $this->onConsecutiveCalls(
                $this->getValidTalkDBRows(),
                $this->getValidTalkDBRows(),
                $this->getValidTalkDBRows(),
                $this->getValidMediaRows()
            )
        );

        $db->method('prepare')
        ->willReturn($stmt);

        $talks_controller = new TalksController();

        $output = $talks_controller->getAction($request, $db);

        $this->assertSame(
            $this->transformMediaRows($this->getValidMediaRows()),
            $output['talks'][0]['talk_media']
        );
    }

    private function createTalkMapper($db, $request)
    {
        $talk_mapper = $this->getMockBuilder('\TalkMapper')
            ->setConstructorArgs(array($db,$request))
            ->getMock();

        $talk_mapper
            ->expects($this->once())
            ->method('getTalkById')
            ->will(
                $this->returnValue(
                    new \TalkModel(
                        [
                            'talk_title'              => 'talk_title',
                            'url_friendly_talk_title' => 'url_friendly_talk_title',
                            'talk_description'        => 'talk_desc',
                            'type'                    => 'talk_type',
                            'start_date'              => 'date_given',
                            'duration'                => 'duration',
                            'stub'                    => 'stub',
                            'average_rating'          => 'avg_rating',
                            'comments_enabled'        => 'comments_enabled',
                            'comment_count'           => 'comment_count',
                            'starred'                 => 'starred',
                            'starred_count'           => 'starred_count',
                            'event_id'                => 1
                        ]
                    )
                )
            );

        return $talk_mapper;
    }

    private function createUserMapper($db, $request)
    {
        $user_mapper = $this->getMockBuilder('\UserMapper')
            ->setConstructorArgs(array($db,$request))
            ->getMock();

        $user_mapper
            ->expects($this->once())
            ->method('getUserById')
            ->will(
                $this->returnValue(
                    [
                        'users' => [
                            [
                                'username'  => 'janebloggs'
                            ]
                        ]
                    ]
                )
            );

        return $user_mapper;
    }

    private function createEventMapper($db, $request)
    {
        $event_mapper = $this->getMockBuilder('\EventMapper')
            ->setConstructorArgs(array($db,$request))
            ->getMock();

        $event_mapper
            ->method('getEventById')
            ->will(
                $this->returnValue(
                    [
                        'events' => [
                            [
                                'name'  => 'Test Event'
                            ]
                        ]
                    ]
                )
            );

        $event_mapper
            ->method('getHostsEmailAddresses')
            ->will(
                $this->returnValue(
                    [
                        'none@example.com'
                    ]
                )
            );

        return $event_mapper;
    }
    private function getValidMediaRows()
    {
        return [
            [
                'display_name' => "slides_link",
                'url' => "https://slideshare.net",
            ],
            [
                'display_name' => "video_link",
                'url' => "https://youtube.com",
            ]
        ];
    }

    private function transformMediaRows($rows)
    {
        $transformedRows = [];

        foreach ($rows as $row) {
               $transformedRows[] = [$row['display_name'] => $row['url']];
        }

        return $transformedRows;
    }

    private function getValidTalkDBRows()
    {
        return [[
            'ID' => 9999,
            'talk_title' => 'Test Talk',
            'full_name' => 'Talker Name',
            'speaker_id' => 1,
            'track_name' => 'test track',
            'display_name' => 'test name',
            'url' => 'url',
        ]];
    }
}
