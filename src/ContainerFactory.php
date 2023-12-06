<?php

namespace Joindin\Api;

use GuzzleHttp\Client;
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
use Joindin\Api\Service\SpamCheckService;
use Joindin\Api\Service\SpamCheckServiceInterface;
use Pimple\Container;
use Psr\Container\ContainerInterface;

class ContainerFactory
{
    private static ?ContainerInterface $container;

    /**
     * Builds a Psr11 compatible container
     *
     * @param array $config
     * @param bool  $rebuild
     *
     * @return ContainerInterface
     */
    public static function build(array $config, bool $rebuild = false): ContainerInterface
    {
        if (!isset(self::$container) || $rebuild) {
            $container = new Container();

            $container[SpamCheckServiceInterface::class] = static function ($c) {
                return new NullSpamCheckService();
            };

            //add $config
            if (isset($config['akismet']['apiKey'], $config['akismet']['blog'])) {
                $container[SpamCheckServiceInterface::class] = static function ($c) use ($config) {
                    return new SpamCheckService(
                        new Client(),
                        $config['akismet']['apiKey'],
                        $config['akismet']['blog']
                    );
                };
            }

            $container[ContactEmailService::class] = static function ($c) use ($config) {
                return new ContactEmailService($config);
            };

            $container[ContactController::class] = $container->factory(static function ($c) use ($config) {
                return new ContactController(
                    $c[ContactEmailService::class],
                    $c[SpamCheckServiceInterface::class],
                    $config
                );
            });

            $container[ApplicationsController::class] = $container->factory(static function ($c) use ($config) {
                return new ApplicationsController($config);
            });

            $container[DefaultController::class] = $container->factory(static function ($c) use ($config) {
                return new DefaultController($config);
            });

            $container[EmailsController::class] = $container->factory(static function ($c) use ($config) {
                return new EmailsController($config);
            });

            $container[EventCommentsController::class] = $container->factory(static function ($container) use ($config): EventCommentsController {
                return new EventCommentsController(
                    $container[SpamCheckServiceInterface::class],
                    $config
                );
            });

            $container[EventHostsController::class] = $container->factory(static function ($c) use ($config) {
                return new EventHostsController($config);
            });

            $container[EventImagesController::class] = $container->factory(static function ($c) use ($config) {
                return new EventImagesController($config);
            });

            $container[EventsController::class] = $container->factory(static function ($c) use ($config) {
                return new EventsController($config);
            });

            $container[FacebookController::class] = $container->factory(static function ($c) use ($config) {
                return new FacebookController($config);
            });

            $container[LanguagesController::class] = $container->factory(static function ($c) use ($config) {
                return new LanguagesController($config);
            });

            $container[TalkCommentsController::class] = $container->factory(static function ($c) use ($config) {
                return new TalkCommentsController($config);
            });

            $container[TalkLinkController::class] = $container->factory(static function ($c) use ($config) {
                return new TalkLinkController($config);
            });

            $container[TalksController::class] = $container->factory(static function ($container) use ($config): TalksController {
                return new TalksController(
                    $container[SpamCheckServiceInterface::class],
                    $config
                );
            });

            $container[TalkTypesController::class] = $container->factory(static function ($c) use ($config) {
                return new TalkTypesController($config);
            });

            $container[TokenController::class] = $container->factory(static function ($c) use ($config) {
                return new TokenController($config);
            });

            $container[TracksController::class] = $container->factory(static function ($c) use ($config) {
                return new TracksController($config);
            });

            $container[TwitterController::class] = $container->factory(static function ($c) use ($config) {
                return new TwitterController($config);
            });

            $container[UsersController::class] = $container->factory(static function ($c) use ($config) {
                return new UsersController($config);
            });

            self::$container = new \Pimple\Psr11\Container($container);
        }

        return self::$container;
    }
}
