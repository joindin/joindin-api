<?php


namespace Joindin\Api\Factory;

use Joindin\Api\Service\BaseEmailService;

class EmailServiceFactory
{
    protected $emailServices;

    public function getEmailService($emailServiceClass, array $config, array $recipients, ...$args): BaseEmailService
    {
        if (!isset($this->emailServices[$emailServiceClass])) {
            $this->emailServices[$emailServiceClass] = count($args) > 0 ?
                new $emailServiceClass($config, $recipients, ...$args) : new $emailServiceClass($config, $recipients);
        }
        return $this->emailServices[$emailServiceClass];
    }

    public function setEmailService(BaseEmailService $emailService)
    {
        $this->emailServices[get_class($emailService)] = $emailService;
    }
}
