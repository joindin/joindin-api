<?php

namespace Joindin\Api\Test;

use Joindin\Api\ContainerFactory;
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

    /**
     * @test
     */
    public function containerIsCreated()
    {
        $this->assertInstanceOf(ContainerInterface::class, ContainerFactory::build($this->config));
    }

    /**
     * @test
     * @covers \Joindin\Api\ContainerFactory::build
     *
     * @dataProvider dataProvider
     * @param string $service
     */
    public function serviceIsDefined($service)
    {
        $container = ContainerFactory::build($this->config, true);
        $this->assertTrue($container->has($service));
    }

    /**
     * @test
     * @covers \Joindin\Api\ContainerFactory::build
     *
     * @dataProvider dataProvider
     * @param string $service
     */
    public function servicesCanBeCreated($service)
    {
        $container = ContainerFactory::build($this->config, true);
        $this->assertInstanceOf($service, $container->get($service));
    }

    /**
     * @test
     * @covers \Joindin\Api\ContainerFactory::build
     */
    public function spamCheckServiceIsNullCheckerWhenDisabled()
    {
        $container = ContainerFactory::build([], true);
        $this->assertTrue($container->has(\Joindin\Api\Service\SpamCheckServiceInterface::class));
        $this->assertInstanceOf(\Joindin\Api\Service\NullSpamCheckService::class, $container->get(\Joindin\Api\Service\SpamCheckServiceInterface::class));
    }

    /**
     * List of services which must be defined
     *
     * @return array
     */
    public function dataProvider()
    {
        return [
            [\Joindin\Api\Controller\ContactController::class],
            [\Joindin\Api\Service\SpamCheckServiceInterface::class],
            [\Joindin\Api\Service\ContactEmailService::class],
            [\Joindin\Api\Controller\ApplicationsController::class],
            [\Joindin\Api\Controller\DefaultController::class],
            [\Joindin\Api\Controller\EmailsController::class] ,
            [\Joindin\Api\Controller\EventCommentsController::class],
            [\Joindin\Api\Controller\EventHostsController::class],
            [\Joindin\Api\Controller\EventImagesController::class],
            [\Joindin\Api\Controller\EventsController::class],
            [\Joindin\Api\Controller\FacebookController::class],
            [\Joindin\Api\Controller\LanguagesController::class],
            [\Joindin\Api\Controller\TalkCommentsController::class],
            [\Joindin\Api\Controller\TalkLinkController::class],
            [\Joindin\Api\Controller\TalksController::class],
            [\Joindin\Api\Controller\TalkTypesController::class],
            [\Joindin\Api\Controller\TokenController::class],
            [\Joindin\Api\Controller\TracksController::class],
            [\Joindin\Api\Controller\TwitterController::class],
            [\Joindin\Api\Controller\UsersController::class]
        ];
    }
}
