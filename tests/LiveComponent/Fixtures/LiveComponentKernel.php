<?php declare(strict_types=1);

namespace BabDev\PagerfantaBundle\Tests\LiveComponent\Fixtures;

use BabDev\PagerfantaBundle\BabDevPagerfantaBundle;
use Psr\Log\NullLogger;
use Symfony\Bundle\FrameworkBundle\Controller\TemplateController;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Bundle\TwigBundle\TwigBundle;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;
use Symfony\UX\LiveComponent\LiveComponentBundle;
use Symfony\UX\StimulusBundle\StimulusBundle;
use Symfony\UX\TwigComponent\TwigComponentBundle;

/**
 * Kernel for the integration tests of the live components paginated with the bundle.
 */
final class LiveComponentKernel extends Kernel
{
    use MicroKernelTrait;

    public function registerBundles(): iterable
    {
        yield new FrameworkBundle();
        yield new TwigBundle();
        yield new StimulusBundle();
        yield new TwigComponentBundle();
        yield new LiveComponentBundle();
        yield new BabDevPagerfantaBundle();
    }

    public function getProjectDir(): string
    {
        return __DIR__;
    }

    public function getCacheDir(): string
    {
        return sys_get_temp_dir().'/babdev_pagerfanta_bundle/live_component/cache';
    }

    public function getLogDir(): string
    {
        return sys_get_temp_dir().'/babdev_pagerfanta_bundle/live_component/logs';
    }

    protected function configureContainer(ContainerConfigurator $container): void
    {
        $container->extension('framework', [
            'secret' => 'secret',
            'test' => true,
        ]);

        $container->extension('twig', [
            'default_path' => __DIR__.'/templates',
        ]);

        $container->extension('twig_component', [
            'anonymous_template_directory' => 'components/',
            'defaults' => [
                __NAMESPACE__.'\\' => 'components/',
            ],
        ]);

        $container->services()
            ->defaults()
                ->autowire()
                ->autoconfigure()
            ->set(OffsetPagerComponent::class)
            ->set(CursorPagerComponent::class)
            ->set(CustomCursorPagerComponent::class)
            ->set('logger', NullLogger::class)
        ;
    }

    protected function configureRoutes(RoutingConfigurator $routes): void
    {
        $routes->add('ux_live_component', '/_components/{_live_component}/{_live_action}')
            ->defaults(['_live_action' => 'get']);

        $routes->add('offset_pager', '/offset')
            ->controller(TemplateController::class)
            ->defaults(['template' => 'offset.html.twig']);

        $routes->add('category_offset_pager', '/categories/{category}')
            ->controller(TemplateController::class)
            ->defaults(['template' => 'offset.html.twig']);

        $routes->add('cursor_pager', '/cursor')
            ->controller(TemplateController::class)
            ->defaults(['template' => 'cursor.html.twig']);
    }
}
