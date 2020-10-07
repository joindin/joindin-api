<?php

namespace Joindin\Api\Test\Service;

use Joindin\Api\Service\BaseEmailService;

trait ReplaceMailerTrait
{
    public function replaceMailer(BaseEmailService $service): BaseEmailService
    {
        $reflectionObject = new \ReflectionObject($service);

        $reflectionProperty = $reflectionObject->getProperty('mailer');
        $reflectionProperty->setAccessible(true);
        $reflectionProperty->setValue($service, $this->createMock(\Swift_Mailer::class));

        return $service;
    }
}
