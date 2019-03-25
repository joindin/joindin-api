<?php

namespace JoindinTest\Controller;

use PHPUnit\Framework\TestCase;

class ContactControllerTest extends TestCase
{
    /**
     * @dataProvider dataProvider
     *
     * @param bool  $isClientPermittedPasswordGrant
     * @param array $returnValueMap
     * @param bool  $isCommentAcceptable
     * @param null  $expectedException
     * @param null  $expectedExceptionMessage
     * @param bool  $spamShouldBeChecked
     * @param bool  $emailShouldBeSent
     *
     * @throws \Exception
     */
    public function testContactWorksAsExpected(
        $isClientPermittedPasswordGrant,
        array $returnValueMap = [],
        $isCommentAcceptable = false,
        $expectedException = null,
        $expectedExceptionMessage = null,
        $spamShouldBeChecked = false,
        $emailShouldBeSent = false
    ) {
        $request = $this->getMockBuilder('\Request')->disableOriginalConstructor()->getMock();

        $oauthModel = $this->getMockBuilder('\OAuthModel')->disableOriginalConstructor()->getMock();
        $oauthModel->expects($this->once())->method('isClientPermittedPasswordGrant')->willReturn($isClientPermittedPasswordGrant);
        $request->expects($this->once())->method('getOauthModel')->willReturn($oauthModel);
        $request
            ->expects($this->any())
            ->method('getParameter')
            ->will(
                $this->returnValueMap($returnValueMap)
            );

        $db = $this->getMockBuilder('\PDO')->disableOriginalConstructor()->getMock();

        $spamCheckService = $this->getMockBuilder(\SpamCheckServiceInterface::class)
                                 ->disableOriginalConstructor()
                                 ->getMock();

        if ($spamShouldBeChecked) {
            $spamCheckService
                ->expects($this->once())
                ->method('isCommentAcceptable')
                ->willReturn($isCommentAcceptable);
        }

        $emailService = $this->getMockBuilder(\ContactEmailService::class)
                             ->disableOriginalConstructor()
                             ->getMock();

        $contactController = new \ContactController($emailService, $spamCheckService);

        if (null !== $expectedException) {
            $this->expectException($expectedException);
        }

        if (null !== $expectedExceptionMessage) {
            $this->expectExceptionMessage($expectedExceptionMessage);
        }

        if ($emailShouldBeSent) {
            $viewMock = $this->getMockBuilder(\ApiView::class)
                             ->disableOriginalConstructor()
                             ->getMock();

            $viewMock->expects($this->once())
                     ->method('setResponseCode')
                     ->with(202);

            $viewMock->expects($this->once())
                     ->method('setHeader')
                     ->with('Content-Length', 0);

            $request->expects($this->once())
                    ->method('getView')
                    ->willReturn($viewMock);

            $emailService
                ->expects($this->once())
                ->method('sendEmail');
        }

        $contactController->contact($request, $db);
    }

    /**
     * Dataprovider for tests
     */
    public function dataProvider()
    {
        return [
            //Client cannot use the contactform
            [
                'isClientPermittedPasswordGrant' => false,
                'returnValueMap'                 => [],
                'isCommentAcceptable'            => false,
                'exceptedException'              => 'Exception',
                'expectedExceptionMessage'       => 'This client cannot perform this action',
                'emailShouldBeSent'              => false,
                'spamShouldBeChecked'            => false
            ],
            //Not all required fields are set
            [
                'isClientPermittedPasswordGrant' => true,
                'returnValueMap'                 => [
                    ['client_id', '', 'client_id'],
                    ['client_secret', '', 'client_secret'],
                    ['name', '', ''],
                    ['email', '', ''],
                    ['subject', '', ''],
                    ['comment', '', '']
                ],
                'isCommentAcceptable'            => false,
                'exceptedException'              => \Exception::class,
                'expectedExceptionMessage'       => "The fields 'name', 'email', 'subject', 'comment' are required",
            ],
            //Spamcheck fails
            [
                'isClientPermittedPasswordGrant' => true,
                'returnValueMap'                 => [
                    ['client_id', '', 'client_id'],
                    ['client_secret', '', 'client_secret'],
                    ['name', '', 'name'],
                    ['email', '', 'email'],
                    ['subject', '', 'subject'],
                    ['comment', '', 'comment']
                ],

                'isCommentAcceptable'      => false,
                'exceptedException'        => \Exception::class,
                'expectedExceptionMessage' => 'Comment failed spam check',
                'spamShouldBeChecked'            => true,
            ],
            //Email is sent without spamcheck
            [
                'isClientPermittedPasswordGrant' => true,
                'returnValueMap'                 => [
                    ['client_id', '', 'client_id'],
                    ['client_secret', '', 'client_secret'],
                    ['name', '', 'name'],
                    ['email', '', 'email'],
                    ['subject', '', 'subject'],
                    ['comment', '', 'comment']
                ],
                'isCommentAcceptable'            => true,
                'exceptedException'              => null,
                'expectedExceptionMessage'       => null,
                'spamShouldBeChecked'            => true,
                'emailShouldBeSent'              => true
            ],
            //All is good email should be sent
            [
                'isClientPermittedPasswordGrant' => true,
                'returnValueMap'                 => [
                    ['client_id', '', 'client_id'],
                    ['client_secret', '', 'client_secret'],
                    ['name', '', 'name'],
                    ['email', '', 'email'],
                    ['subject', '', 'subject'],
                    ['comment', '', 'comment']
                ],
                'isCommentAcceptable'            => true,
                'exceptedException'              => null,
                'expectedExceptionMessage'       => null,
                'spamShouldBeChecked'            => true,
                'emailShouldBeSent'              => true
            ]
        ];
    }
}
