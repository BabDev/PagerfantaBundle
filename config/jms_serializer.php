<?php declare(strict_types=1);

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use BabDev\PagerfantaBundle\Serializer\Handler\CursorPagerHandler;
use BabDev\PagerfantaBundle\Serializer\Handler\PagerfantaHandler;

return static function (ContainerConfigurator $container): void {
    $services = $container->services();

    $services->set('pagerfanta.serializer.handler', PagerfantaHandler::class)
        ->tag('jms_serializer.subscribing_handler')
    ;

    $services->set('pagerfanta.serializer.cursor_handler', CursorPagerHandler::class)
        ->args([
            service('pagerfanta.cursor_encoder'),
        ])
        ->tag('jms_serializer.subscribing_handler')
    ;
};
