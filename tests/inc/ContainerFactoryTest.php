<?php

namespace JoindinTest\Inc;

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
        $this->assertInstanceOf(ContainerInterface::class, \ContainerFactory::build($this->config));
    }

    /**
     * @covers ContainerFactory::build
     *
     * @dataProvider dataProvider
     * @param string $service
     */
    public function testServiceIsDefined($service)
    {
        $container = \ContainerFactory::build($this->config, true);
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
        $container = \ContainerFactory::build($this->config, true);
        $this->assertInstanceOf($service, $container->get($service));
    }

    /**
     * @covers ContainerFactory::build
     */
    public function testSpamCheckServiceIsNullCheckerWhenDisabled()
    {
        $container = \ContainerFactory::build([], true);
        $this->assertTrue($container->has(\SpamCheckServiceInterface::class));
        $this->assertInstanceOf(\NullSpamCheckService::class, $container->get(\SpamCheckServiceInterface::class));
    }

    /**
     * List of services which must be defined
     *
     * @return array
     */
    public function dataProvider()
    {
        return [
            [\ContactController::class],
            [\SpamCheckServiceInterface::class],
            [\ContactEmailService::class],
            [\ApplicationsController::class],
            [\DefaultController::class],
            [\EmailsController::class] ,
            [\Event_commentsController::class],
            [\Event_hostsController::class],
            [\EventImagesController::class],
            [\EventsController::class],
            [\FacebookController::class],
            [\LanguagesController::class],
            [\Talk_commentsController::class],
            [\TalkLinkController::class],
            [\TalksController::class],
            [\TalkTypesController::class],
            [\TokenController::class],
            [\TracksController::class],
            [\TwitterController::class],
            [\UsersController::class]
        ];
    }
}
