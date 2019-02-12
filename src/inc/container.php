<?php

use Pimple\Container;

$container = new Container();

//add $config
$container[SpamCheckService::class] = function ($c) use ($config) {
    if (isset($config['akismet']['apiKey'], $config['akismet']['blog'])) {
        return null;
    }

    return new SpamCheckService(
        $config['akismet']['apiKey'],
        $config['akismet']['blog']
    );
};

$container[ContactEmailService::class] = function ($c) use ($config) {
    return new ContactEmailService($config);
};

$container[ContactController::class] = $container->factory(function ($c) use ($config) {
    $contactController = (new ContactController($config))
        ->setEmailService($c[ContactEmailService::class])
        ->setSpamCheckService($c[SpamCheckService::class]);

    return $contactController;
});

$container[ApplicationsController::class] = $container->factory(function ($c) use ($config) {
    return new ApplicationsController($config);
});

$container[BaseTalkController::class] = $container->factory(function ($c) use ($config) {
    return new BaseTalkController($config);
});

$container[ContactController::class] = $container->factory(function ($c) use ($config) {
    return new ContactController($config);
});

$container[DefaultController::class] = $container->factory(function ($c) use ($config) {
    return new DefaultController($config);
});
$container[EmailsController::class]  = $container->factory(function ($c) use ($config) {
    return new EmailsController($config);
});

$container[Event_commentsController::class] = $container->factory(function ($c) use ($config) {
    return new Event_commentsController($config);
});

$container[Event_hostsController::class] = $container->factory(function ($c) use ($config) {
    return new Event_hostsController($config);
});

$container[EventImagesController::class] = $container->factory(function ($c) use ($config) {
    return new EventImagesController($config);
});

$container[EventsController::class] = $container->factory(function ($c) use ($config) {
    return new EventsController($config);
});

$container[FacebookController::class] = $container->factory(function ($c) use ($config) {
    return new FacebookController($config);
});

$container[LanguagesController::class] = $container->factory(function ($c) use ($config) {
    return new LanguagesController($config);
});

$container[Talk_commentsController::class] = $container->factory(function ($c) use ($config) {
    return new Talk_commentsController($config);
});

$container[TalkLinkController::class] = $container->factory(function ($c) use ($config) {
    return new TalkLinkController($config);
});

$container[TalksController::class] = $container->factory(function ($c) use ($config) {
    return new TalksController($config);
});

$container[TalkTypesController::class] = $container->factory(function ($c) use ($config) {
    return new TalkTypesController($config);
});

$container[TokenController::class] = $container->factory(function ($c) use ($config) {
    return new TokenController($config);
});

$container[TracksController::class] = $container->factory(function ($c) use ($config) {
    return new TracksController($config);
});

$container[TwitterController::class] = $container->factory(function ($c) use ($config) {
    return new TwitterController($config);
});

$container[UsersController::class] = $container->factory(function ($c) use ($config) {
    return new UsersController($config);
});

return new \Pimple\Psr11\Container($container);