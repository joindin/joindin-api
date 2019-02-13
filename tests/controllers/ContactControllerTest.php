<?php

namespace JoindinTest\Controller;

use PHPUnit\Framework\TestCase;

class ContactControllerTest extends TestCase
{
    /**
     * @dataProvider dataProvider
     * @test
     *
     */
    public function contactWorksAsExpected(
        $isClientPermittedPasswordGrant,
        array $returnValueMap = [],
        $isCommentAcceptable = false,
        $spamCheckServiceIsSet = false,
        $emailServiceIsSet = false,
        $expectedException = null,
        $expectedExceptionMessage = null,
        $emailShouldBeSent = false
    ) {
        $request = $this->getMockBuilder('\Request')->disableOriginalConstructor()->getMock();

        $contactController = new \ContactController();

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

        if ($spamCheckServiceIsSet) {
            $spamCheckService = $this->getMockBuilder(\SpamCheckService::class)
                                     ->disableOriginalConstructor()
                                     ->getMock();

            $spamCheckService
                ->expects($this->once())
                ->method('isCommentAcceptable')
                ->willReturn($isCommentAcceptable);

            $contactController->setSpamCheckService($spamCheckService);
        }

        if ($emailServiceIsSet) {
            $emailService = $this->getMockBuilder(\ContactEmailService::class)
                                 ->disableOriginalConstructor()
                                 ->getMock();

            $contactController->setEmailService($emailService);
        }

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
                'spamCheckServiceIsSet'          => false,
                'emailCheckServiceIsSet'         => false,
                'exceptedException'              => 'Exception',
                'expectedExceptionMessage'       => 'This client cannot perform this action',
                'emailShouldBeSent'              => false
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
                'spamCheckServiceIsSet'          => false,
                'emailCheckServiceIsSet'         => true,
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
                'spamCheckServiceIsSet'    => true,
                'emailCheckServiceIsSet'   => true,
                'exceptedException'        => \Exception::class,
                'expectedExceptionMessage' => 'Comment failed spam check',
            ],
            //EmailService check throws an exception
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
                'spamCheckServiceIsSet'          => false,
                'emailCheckServiceIsSet'         => false,
                'exceptedException'              => \RuntimeException::class,
                'expectedExceptionMessage'       => 'The emailservice has not been set',
                'emailShouldBeSent'              => false
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
                'spamCheckServiceIsSet'          => true,
                'emailCheckServiceIsSet'         => true,
                'exceptedException'              => null,
                'expectedExceptionMessage'       => null,
                'emailShouldBeSent'              => true
            ]
        ];
    }
}
