<?php


namespace Joindin\Api\Test;

use Joindin\Api\Factory\EmailServiceFactory;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class EmailServiceFactoryForTests extends EmailServiceFactory
{
    public function getEmailServiceMock(TestCase $case, $emailService) : MockObject {
        if (empty($this->emailServices[$emailService])) {
            $this->setEmailServiceMock($case, $emailService);
        }
        return $this->emailServices[$emailService];
    }

    public function setEmailServiceMock(TestCase $case, $emailService) {
        $this->emailServices[$emailService] = $case->getMockBuilder($emailService)->disableOriginalConstructor()->getMock();
    }

    public function setEmailServiceMocks(TestCase $case, ...$emailServices) {
        foreach ($emailServices as $emailService) {
            $this->setEmailServiceMock($case, $emailService);
        }
    }
}
