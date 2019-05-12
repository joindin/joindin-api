<?php

namespace Joindin\Api\Test;

use Joindin\Api\ContainerFactory;
use Joindin\Api\Controller;
use Joindin\Api\Service;
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
            'from' => 'example@example.com',
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
     * @covers ContainerFactory::build
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
     * @covers ContainerFactory::build
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
     * @covers ContainerFactory::build
     */
    public function testSpamCheckServiceIsNullCheckerWhenDisabled()
    {
        $container = ContainerFactory::build([], true);
        $this->assertTrue($container->has(SpamCheckServiceInterface::class));
        $this->assertInstanceOf(Service\NullSpamCheckService::class, $container->get(SpamCheckServiceInterface::class));
    }

    /**
     * List of services which must be defined
     */
    public function dataProvider(): array
    {
        return [
            [Controller\ContactController::class],
            [SpamCheckServiceInterface::class],
            [Service\ContactEmailService::class],
            [Controller\ApplicationsController::class],
            [Controller\DefaultController::class],
            [Controller\EmailsController::class] ,
            [Controller\EventCommentsController::class],
            [Controller\EventHostsController::class],
            [Controller\EventImagesController::class],
            [Controller\EventsController::class],
            [Controller\FacebookController::class],
            [Controller\LanguagesController::class],
            [Controller\TalkCommentsController::class],
            [Controller\TalkLinkController::class],
            [Controller\TalksController::class],
            [Controller\TalkTypesController::class],
            [Controller\TokenController::class],
            [Controller\TracksController::class],
            [Controller\TwitterController::class],
            [Controller\UsersController::class],
        ];
    }
}
