<?php

namespace JoindinTest\Controller;

use EventMapper;
use JoindinTest\Inc\mockPDO;
use OAuthModel;
use PHPUnit\Framework\TestCase;
use Request;
use TalkCommentMapper;
use TalkMapper;
use TalkModel;
use UserMapper;

class TalkBase extends TestCase
{
    protected function createTalkMapper(mockPDO $db, Request $request, $expectedCalls = 1)
    {
        $talk_mapper = $this->getMockBuilder(TalkMapper::class)
            ->setConstructorArgs(array($db,$request))
            ->getMock();

        $talk_mapper
            ->expects($this->exactly($expectedCalls))
            ->method('getTalkById')
            ->will(
                $this->returnValue(
                    new TalkModel(
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

    protected function createVerboseTalkMapper(mockPDO $db, Request $request)
    {
        $talk_mapper = $this->getMockBuilder(TalkMapper::Class)
            ->setConstructorArgs([$db,$request])
            ->getMock();

        $talk_mapper
            ->expects($this->once())
            ->method('getTalkById')
            ->will(
                $this->returnValue(
                    new TalkModel(
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
                            'event_id'                => 1,
                            'slides_link'             => 'http://slideshare.net',
                            'talk_media'              => [
                                ['slides_link'  =>  'http://slideshare.net'],
                                ['code_link'    =>  'https://github.com'],
                            ],
                        ]
                    )
                )
            );

        return $talk_mapper;
    }

    protected function createUserMapper(mockPDO $db, Request $request)
    {
        $user_mapper = $this->getMockBuilder(UserMapper::Class)
            ->setConstructorArgs(array($db,$request))
            ->getMock();

        $user_mapper
            ->expects($this->atLeastOnce())
            ->method('getUserById')
            ->will(
                $this->returnValue(
                    [
                        'users' => [
                            [
                                'username'  => 'janebloggs',
                                'full_name' => 'Jane Bloggs'
                            ]
                        ]
                    ]
                )
            );

        return $user_mapper;
    }

    protected function createEventMapper(mockPDO $db, Request $request)
    {
        $event_mapper = $this->getMockBuilder(EventMapper::class)
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

    protected function createTalkCommentMapper(mockPDO $db, Request $request)
    {
        return $this->getMockBuilder(TalkCommentMapper::class)
            ->setConstructorArgs(array($db,$request))
            ->getMock();
    }

    protected function createOathModel(mockPDO $db, Request $request, $consumerName = "")
    {

        $oathModel = $this->getMockBuilder(OAuthModel::class)
            ->setConstructorArgs(array($db,$request))
            ->getMock();

        $oathModel
            ->method('getConsumerName')
            ->willReturn(
                [
                    $consumerName
                ]
            );

        return $oathModel;
    }
}
