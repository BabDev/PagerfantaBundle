<?php declare(strict_types=1);

namespace BabDev\PagerfantaBundle\Tests\DependencyInjection;

use BabDev\PagerfantaBundle\BabDevPagerfantaBundle;
use BabDev\PagerfantaBundle\DependencyInjection\BabDevPagerfantaExtension;
use BabDev\PagerfantaBundle\DependencyInjection\Configuration;
use JMS\SerializerBundle\JMSSerializerBundle;
use Matthias\SymfonyDependencyInjectionTest\PhpUnit\AbstractExtensionTestCase;
use Pagerfanta\Twig\Extension\PagerfantaExtension;
use Pagerfanta\View\ViewFactoryInterface;
use Symfony\Bundle\TwigBundle\DependencyInjection\TwigExtension;
use Symfony\Bundle\TwigBundle\TwigBundle;
use Symfony\Component\HttpKernel\KernelEvents;

final class BabDevPagerfantaExtensionTest extends AbstractExtensionTestCase
{
    public function testContainerIsLoadedWithDefaultConfigurationWhenTwigBundleIsNotInstalled(): void
    {
        $this->container->setParameter(
            'kernel.bundles',
            [
                'BabDevPagerfantaBundle' => BabDevPagerfantaBundle::class,
            ],
        );

        $this->load();

        $this->assertContainerBuilderHasAlias(ViewFactoryInterface::class, 'pagerfanta.view_factory');

        $listeners = [
            'pagerfanta.event_listener.convert_not_valid_max_per_page_to_not_found',
            'pagerfanta.event_listener.convert_not_valid_current_page_to_not_found',
        ];

        foreach ($listeners as $listener) {
            $this->assertContainerBuilderHasServiceDefinitionWithTag(
                $listener,
                'kernel.event_listener',
                [
                    'event' => KernelEvents::EXCEPTION,
                    'method' => 'onKernelException',
                    'priority' => 512,
                ]
            );
        }

        $twigServices = [
            'pagerfanta.twig_extension',
            'pagerfanta.twig_runtime',
            'pagerfanta.view.twig',
        ];

        foreach ($twigServices as $twigService) {
            $this->assertContainerBuilderNotHasService($twigService);
        }

        $this->assertContainerBuilderHasService('pagerfanta.serializer.normalizer');
    }

    public function testContainerIsLoadedWithDefaultConfigurationWhenTwigBundleIsInstalled(): void
    {
        if (!class_exists(PagerfantaExtension::class)) {
            self::markTestSkipped('Test requires Twig');
        }

        $this->container->registerExtension(new TwigExtension());

        $this->container->setParameter(
            'kernel.bundles',
            [
                'BabDevPagerfantaBundle' => BabDevPagerfantaBundle::class,
                'TwigBundle' => TwigBundle::class,
            ],
        );

        $this->container->setParameter(
            'kernel.bundles_metadata',
            [
                'BabDevPagerfantaBundle' => [
                    'path' => (__DIR__.'/../..'),
                    'namespace' => 'BabDev\\PagerfantaBundle',
                ],
                'TwigBundle' => [
                    'path' => (__DIR__.'/../../vendor/symfony/twig-bundle'),
                    'namespace' => 'Symfony\\Bundle\\TwigBundle',
                ],
            ],
        );

        $this->container->setParameter('kernel.debug', false);
        $this->container->setParameter('kernel.build_dir', __DIR__);
        $this->container->setParameter('kernel.cache_dir', __DIR__);
        $this->container->setParameter('kernel.project_dir', __DIR__);

        $this->load();

        $this->assertContainerBuilderHasAlias(ViewFactoryInterface::class, 'pagerfanta.view_factory');

        $listeners = [
            'pagerfanta.event_listener.convert_not_valid_max_per_page_to_not_found',
            'pagerfanta.event_listener.convert_not_valid_current_page_to_not_found',
        ];

        foreach ($listeners as $listener) {
            $this->assertContainerBuilderHasServiceDefinitionWithTag(
                $listener,
                'kernel.event_listener',
                [
                    'event' => KernelEvents::EXCEPTION,
                    'method' => 'onKernelException',
                    'priority' => 512,
                ],
            );
        }

        $twigServices = [
            'pagerfanta.twig_extension',
            'pagerfanta.twig_runtime',
            'pagerfanta.view.twig',
        ];

        foreach ($twigServices as $twigService) {
            $this->assertContainerBuilderHasService($twigService);
        }

        $twigConfig = $this->container->getExtensionConfig('twig');

        $refl = new \ReflectionClass(PagerfantaExtension::class);

        if (false === $refl->getFileName()) {
            self::fail(\sprintf('Could not reflect "%s"', PagerfantaExtension::class));
        }

        $path = \dirname($refl->getFileName(), 2).'/templates/';

        self::assertArrayHasKey($path, $twigConfig[0]['paths']);

        $this->assertContainerBuilderHasService('pagerfanta.serializer.normalizer');
    }

    public function testContainerIsLoadedWithDefaultConfigurationWhenJMSSerializerBundleIsInstalled(): void
    {
        $this->container->setParameter(
            'kernel.bundles',
            [
                'BabDevPagerfantaBundle' => BabDevPagerfantaBundle::class,
                'JMSSerializerBundle' => JMSSerializerBundle::class,
            ],
        );

        $this->load();

        $this->assertContainerBuilderHasAlias(ViewFactoryInterface::class, 'pagerfanta.view_factory');

        $listeners = [
            'pagerfanta.event_listener.convert_not_valid_max_per_page_to_not_found',
            'pagerfanta.event_listener.convert_not_valid_current_page_to_not_found',
        ];

        foreach ($listeners as $listener) {
            $this->assertContainerBuilderHasServiceDefinitionWithTag(
                $listener,
                'kernel.event_listener',
                [
                    'event' => KernelEvents::EXCEPTION,
                    'method' => 'onKernelException',
                    'priority' => 512,
                ],
            );
        }

        $this->assertContainerBuilderHasService('pagerfanta.serializer.handler');
        $this->assertContainerBuilderHasService('pagerfanta.serializer.normalizer');
    }

    public function testContainerIsLoadedWhenBundleIsConfiguredWithCustomExceptionStrategies(): void
    {
        $this->container->setParameter(
            'kernel.bundles',
            [
                'BabDevPagerfantaBundle' => BabDevPagerfantaBundle::class,
            ],
        );

        $bundleConfig = [
            'exceptions_strategy' => [
                'out_of_range_page' => Configuration::EXCEPTION_STRATEGY_CUSTOM,
                'not_valid_current_page' => Configuration::EXCEPTION_STRATEGY_CUSTOM,
            ],
        ];

        $this->load($bundleConfig);

        $this->assertContainerBuilderHasAlias(ViewFactoryInterface::class, 'pagerfanta.view_factory');

        $listeners = [
            'pagerfanta.event_listener.convert_not_valid_max_per_page_to_not_found',
            'pagerfanta.event_listener.convert_not_valid_current_page_to_not_found',
        ];

        foreach ($listeners as $listener) {
            $this->assertContainerBuilderNotHasService($listener);
        }
    }

    protected function getContainerExtensions(): array
    {
        return [
            new BabDevPagerfantaExtension(),
        ];
    }
}
