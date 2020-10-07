<?php

namespace Joindin\Api\Test\Service;

trait ReplaceMailerTrait
{
    public function replaceMailer($service)
    {
        $reflectionObject = new \ReflectionObject($service);

        $reflectionProperty = $reflectionObject->getProperty('mailer');
        $reflectionProperty->setAccessible(true);
        $reflectionProperty->setValue($service, $this->createMock(\Swift_Mailer::class));

        return $service;
    }
}
