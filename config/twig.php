<?php declare(strict_types=1);

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use BabDev\PagerfantaBundle\Twig\UndefinedCallableHandler;
use Pagerfanta\RouteGenerator\PositionRouteGeneratorFactoryInterface;
use Pagerfanta\Twig\Extension\PagerfantaExtension;
use Pagerfanta\Twig\Extension\PagerfantaRuntime;
use Pagerfanta\Twig\View\TwigView;

return static function (ContainerConfigurator $container): void {
    $services = $container->services();

    $services->set('pagerfanta.twig_extension', PagerfantaExtension::class)
        ->tag('twig.extension')
    ;

    $services->set('pagerfanta.twig_runtime', PagerfantaRuntime::class)
        ->args([
            abstract_arg('default view'),
            service('pagerfanta.view_factory'),
            service(PositionRouteGeneratorFactoryInterface::class),
            abstract_arg('default sequential view'),
        ])
        ->tag('twig.runtime')
    ;

    $services->set('pagerfanta.undefined_callable_handler', UndefinedCallableHandler::class);

    $services->set('pagerfanta.view.twig', TwigView::class)
        ->args([
            service('twig'),
            abstract_arg('default Twig template'),
        ])
        ->tag('pagerfanta.view', ['alias' => 'twig'])
    ;
};
