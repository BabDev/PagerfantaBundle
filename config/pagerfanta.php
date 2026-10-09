<?php declare(strict_types=1);

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use BabDev\PagerfantaBundle\Cursor\SignedCursorEncoder;
use BabDev\PagerfantaBundle\Position\PositionResolver;
use BabDev\PagerfantaBundle\RouteGenerator\RequestAwarePositionRouteGeneratorFactory;
use BabDev\PagerfantaBundle\View\ContainerBackedImmutableViewFactory;
use Pagerfanta\Cursor\Base64JsonCursorEncoder;
use Pagerfanta\Cursor\CursorEncoderInterface;
use Pagerfanta\RouteGenerator\PositionRouteGeneratorFactoryInterface;
use Pagerfanta\View\DefaultView;
use Pagerfanta\View\Foundation6View;
use Pagerfanta\View\SemanticUiView;
use Pagerfanta\View\SequentialView;
use Pagerfanta\View\Template\DefaultTemplate;
use Pagerfanta\View\Template\Foundation6Template;
use Pagerfanta\View\Template\SemanticUiTemplate;
use Pagerfanta\View\Template\TwitterBootstrap3Template;
use Pagerfanta\View\Template\TwitterBootstrap4Template;
use Pagerfanta\View\Template\TwitterBootstrap5Template;
use Pagerfanta\View\Template\TwitterBootstrapTemplate;
use Pagerfanta\View\TwitterBootstrap3View;
use Pagerfanta\View\TwitterBootstrap4View;
use Pagerfanta\View\TwitterBootstrap5View;
use Pagerfanta\View\TwitterBootstrapView;
use Pagerfanta\View\ViewFactoryInterface;

return static function (ContainerConfigurator $container): void {
    $services = $container->services();

    $services->set('pagerfanta.cursor_encoder.base64_json', Base64JsonCursorEncoder::class);

    $services->set('pagerfanta.cursor_encoder.signed', SignedCursorEncoder::class)
        ->args([
            service('pagerfanta.cursor_encoder.base64_json'),
            param('kernel.secret'),
        ])
    ;

    $services->alias('pagerfanta.cursor_encoder', 'pagerfanta.cursor_encoder.signed');
    $services->alias(CursorEncoderInterface::class, 'pagerfanta.cursor_encoder');

    $services->set('pagerfanta.position_resolver', PositionResolver::class)
        ->args([
            service('property_accessor'),
            service('pagerfanta.cursor_encoder'),
        ])
    ;
    $services->alias(PositionResolver::class, 'pagerfanta.position_resolver');

    $services->set('pagerfanta.position_route_generator_factory', RequestAwarePositionRouteGeneratorFactory::class)
        ->args([
            service('router'),
            service('request_stack'),
            service('property_accessor'),
            service('pagerfanta.cursor_encoder'),
        ])
    ;
    $services->alias(PositionRouteGeneratorFactoryInterface::class, 'pagerfanta.position_route_generator_factory');

    $services->set('pagerfanta.view.default', DefaultView::class)
        ->tag('pagerfanta.view', ['alias' => 'default'])
    ;

    $services->set('pagerfanta.view.foundation6', Foundation6View::class)
        ->tag('pagerfanta.view', ['alias' => 'foundation6'])
    ;

    $services->set('pagerfanta.view.semantic_ui', SemanticUiView::class)
        ->tag('pagerfanta.view', ['alias' => 'semantic_ui'])
    ;

    $services->set('pagerfanta.view.twitter_bootstrap', TwitterBootstrapView::class)
        ->tag('pagerfanta.view', ['alias' => 'twitter_bootstrap'])
    ;

    $services->set('pagerfanta.view.twitter_bootstrap3', TwitterBootstrap3View::class)
        ->tag('pagerfanta.view', ['alias' => 'twitter_bootstrap3'])
    ;

    $services->set('pagerfanta.view.twitter_bootstrap4', TwitterBootstrap4View::class)
        ->tag('pagerfanta.view', ['alias' => 'twitter_bootstrap4'])
    ;

    $services->set('pagerfanta.view.twitter_bootstrap5', TwitterBootstrap5View::class)
        ->tag('pagerfanta.view', ['alias' => 'twitter_bootstrap5'])
    ;

    foreach ([
        'default' => DefaultTemplate::class,
        'foundation6' => Foundation6Template::class,
        'semantic_ui' => SemanticUiTemplate::class,
        'twitter_bootstrap' => TwitterBootstrapTemplate::class,
        'twitter_bootstrap3' => TwitterBootstrap3Template::class,
        'twitter_bootstrap4' => TwitterBootstrap4Template::class,
        'twitter_bootstrap5' => TwitterBootstrap5Template::class,
    ] as $name => $templateClass) {
        $services->set(\sprintf('pagerfanta.view.%s_sequential', $name), SequentialView::class)
            ->args([
                inline_service($templateClass),
                \sprintf('%s_sequential', $name),
            ])
            ->tag('pagerfanta.view', ['alias' => \sprintf('%s_sequential', $name)])
        ;
    }

    $services->set('pagerfanta.view_factory', ContainerBackedImmutableViewFactory::class)
        ->args([
            abstract_arg('service locator'),
            abstract_arg('service map'),
        ])
    ;
    $services->alias(ViewFactoryInterface::class, 'pagerfanta.view_factory');
};
