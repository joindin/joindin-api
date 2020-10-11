<?php


namespace Joindin\Api\Factory;


use Joindin\Api\Model\UserMapper;
use Joindin\Api\Request;
use Joindin\Api\Service\EventCommentReportedEmailService;
use Joindin\Api\Service\UserPasswordResetEmailService;
use Joindin\Api\Service\UserRegistrationEmailService;
use Joindin\Api\Service\UserUsernameReminderEmailService;
use PDO;

class EmailServiceFactory
{
    private $userMapper;
    private $userUsernameReminderEmailService;

    private $userRegistrationEmailService;
    private $userPasswordResetEmailService;
    private $eventCommentReportedEmailService;

    public function getUserMapper(Request $request, PDO $db)
    {
        if ($this->userMapper == null) {
            $this->userMapper = new UserMapper($db, $request);
        }
        return $this->userMapper;
    }

    public function setUserMapper($userMapper): void
    {
        $this->userMapper = $userMapper;
    }

    public function getUserUsernameReminderEmailService($config, $recipients, $user)
    {
        if ($this->userUsernameReminderEmailService == null) {
            $this->userUsernameReminderEmailService = new UserUsernameReminderEmailService($config, $recipients, $user);
        }
        return $this->userUsernameReminderEmailService;
    }

    public function setUserUsernameReminderEmailService($userUsernameReminderEmailService): void
    {
        $this->userUsernameReminderEmailService = $userUsernameReminderEmailService;
    }

    public function getUserPasswordResetEmailService($config, $recipients, $user)
    {
        if ($this->userPasswordResetEmailService == null) {
            $this->userPasswordResetEmailService = new UserPasswordResetEmailService($config, $recipients, $user);
        }
        return $this->userPasswordResetEmailService;
    }

    public function setUserPasswordResetEmailService($userPasswordResetEmailService): void
    {
        $this->userPasswordResetEmailService = $userPasswordResetEmailService;
    }

    public function getUserRegistrationEmailService($config, $recipients, $user)
    {
        if ($this->userRegistrationEmailService == null) {
            $this->userRegistrationEmailService = new UserRegistrationEmailService($config, $recipients, $user);
        }
        return $this->userRegistrationEmailService;
    }

    public function setUserRegistrationEmailService($userRegistrationEmailService): void
    {
        $this->userRegistrationEmailService = $userRegistrationEmailService;
    }

    public function getEventCommentReportedEmailService($config, $recipients, $comment, $event) {
        return $this->eventCommentReportedEmailService ?? new EventCommentReportedEmailService($config, $recipients, $comment, $event);
    }

    public function setEventCommentReportedEmailService($eventCommentReportedEmailService): void
    {
        $this->eventCommentReportedEmailService = $eventCommentReportedEmailService;
    }

}
