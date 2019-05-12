<?php

namespace JoindinTest\Inc;

use Joindin\Api\ContainerFactory;
use Joindin\Api\Controller\ApplicationsController;
use Joindin\Api\Controller\ContactController;
use Joindin\Api\Controller\DefaultController;
use Joindin\Api\Controller\EmailsController;
use Joindin\Api\Controller\EventCommentsController;
use Joindin\Api\Controller\EventHostsController;
use Joindin\Api\Controller\EventImagesController;
use Joindin\Api\Controller\EventsController;
use Joindin\Api\Controller\FacebookController;
use Joindin\Api\Controller\LanguagesController;
use Joindin\Api\Controller\TalkCommentsController;
use Joindin\Api\Controller\TalkLinkController;
use Joindin\Api\Controller\TalksController;
use Joindin\Api\Controller\TalkTypesController;
use Joindin\Api\Controller\TokenController;
use Joindin\Api\Controller\TracksController;
use Joindin\Api\Controller\TwitterController;
use Joindin\Api\Controller\UsersController;
use Joindin\Api\Service\ContactEmailService;
use Joindin\Api\Service\NullSpamCheckService;
use Joindin\Api\Service\SpamCheckServiceInterface;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

class ContainerFactoryTest extends TestCase
{
    private $config = [
        'akismet' => [
            'apiKey' => 'key',
            'blog'   => 'blog'
        ],
        'email' => [
            'contact' => 'excample@example.com',
            "from" => "example@example.com",
            'smtp' => [
                'host'     => 'localhost',
                'port'     => 25,
                'username' => 'username',
                'password' => 'ChangeMeSeymourChangeMe',
                'security' => null,
            ],
        ],
        'website_url' => 'www.example.com'
    ];

    public function testContainerIsCreated()
    {
        $this->assertInstanceOf(ContainerInterface::class, ContainerFactory::build($this->config));
    }

    /**
     * @covers \Joindin\Api\ContainerFactory::build
     *
     * @dataProvider dataProvider
     * @param string $service
     */
    public function testServiceIsDefined($service)
    {
        $container = ContainerFactory::build($this->config, true);
        $this->assertTrue($container->has($service));
    }

    /**
     * @covers \Joindin\Api\ContainerFactory::build
     *
     * @dataProvider dataProvider
     * @param string $service
     */
    public function testServicesCanBeCreated($service)
    {
        $container = ContainerFactory::build($this->config, true);
        $this->assertInstanceOf($service, $container->get($service));
    }

    /**
     * @covers \Joindin\Api\ContainerFactory::build
     */
    public function testSpamCheckServiceIsNullCheckerWhenDisabled()
    {
        $container = ContainerFactory::build([], true);
        $this->assertTrue($container->has(SpamCheckServiceInterface::class));
        $this->assertInstanceOf(NullSpamCheckService::class, $container->get(SpamCheckServiceInterface::class));
    }

    /**
     * List of services which must be defined
     *
     * @return array
     */
    public function dataProvider()
    {
        return [
            [ContactController::class],
            [SpamCheckServiceInterface::class],
            [ContactEmailService::class],
            [ApplicationsController::class],
            [DefaultController::class],
            [EmailsController::class] ,
            [EventCommentsController::class],
            [EventHostsController::class],
            [EventImagesController::class],
            [EventsController::class],
            [FacebookController::class],
            [LanguagesController::class],
            [TalkCommentsController::class],
            [TalkLinkController::class],
            [TalksController::class],
            [TalkTypesController::class],
            [TokenController::class],
            [TracksController::class],
            [TwitterController::class],
            [UsersController::class]
        ];
    }
}
