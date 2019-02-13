<?php

namespace JoindinTest\Inc;

use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

class ContainerFactoryTest extends TestCase
{
    /**
     * @test
     */
    public function containerIsCreated()
    {
        $this->assertInstanceOf(ContainerInterface::class, \ContainerFactory::build([]));
    }

    /**
     * @test
     *
     * @dataProvider dataProvider
     * @param string $service
     */
    public function serviceIsDefined($service)
    {
        $container = \ContainerFactory::build([]);
        $this->assertTrue($container->has($service));
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
            [\SpamCheckService::class],
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
