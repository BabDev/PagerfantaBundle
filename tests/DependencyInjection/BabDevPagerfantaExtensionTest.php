<?php declare(strict_types=1);

namespace BabDev\PagerfantaBundle\Tests\DependencyInjection;

use BabDev\PagerfantaBundle\BabDevPagerfantaBundle;
use BabDev\PagerfantaBundle\DependencyInjection\BabDevPagerfantaExtension;
use BabDev\PagerfantaBundle\DependencyInjection\Configuration;
use BabDev\PagerfantaBundle\EventListener\ConvertInvalidCursorToBadRequestListener;
use BabDev\PagerfantaBundle\EventListener\ConvertNotValidCurrentPageToNotFoundListener;
use BabDev\PagerfantaBundle\EventListener\ConvertNotValidMaxPerPageToNotFoundListener;
use BabDev\PagerfantaBundle\Position\PositionResolver;
use Composer\InstalledVersions;
use JMS\SerializerBundle\JMSSerializerBundle;
use Matthias\SymfonyDependencyInjectionTest\PhpUnit\AbstractExtensionTestCase;
use Matthias\SymfonyDependencyInjectionTest\PhpUnit\DefinitionDecoratesConstraint;
use Pagerfanta\Cursor\CursorEncoderInterface;
use Pagerfanta\RouteGenerator\PositionRouteGeneratorFactoryInterface;
use Pagerfanta\RouteGenerator\RouteGeneratorFactoryInterface;
use Pagerfanta\Twig\Extension\PagerfantaExtension;
use Pagerfanta\View\ViewFactoryInterface;
use Symfony\Component\DependencyInjection\Reference;
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
        $this->assertCursorAndRouteGenerationServicesAreRegistered();

        $listeners = [
            'pagerfanta.event_listener.convert_not_valid_max_per_page_to_not_found',
            'pagerfanta.event_listener.convert_not_valid_current_page_to_not_found',
            'pagerfanta.event_listener.convert_invalid_cursor_to_bad_request',
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

        if (class_exists(InstalledVersions::class)) {
            $version = InstalledVersions::getVersion('symfony/serializer');

            if (null !== $version && version_compare($version, '6.3', '<')) {
                // TODO - Fix upstream
                // $this->assertContainerBuilderServiceDecoration('pagerfanta.serializer.normalizer.legacy', 'pagerfanta.serializer.normalizer');
                self::assertThat($this->container, new DefinitionDecoratesConstraint('pagerfanta.serializer.normalizer.legacy', 'pagerfanta.serializer.normalizer'));
            } else {
                $this->assertContainerBuilderNotHasService('pagerfanta.serializer.normalizer.legacy');
            }
        }
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

        $this->container->setParameter('kernel.build_dir', __DIR__);
        $this->container->setParameter('kernel.cache_dir', __DIR__);
        $this->container->setParameter('kernel.debug', false);
        $this->container->setParameter('kernel.project_dir', __DIR__);

        $this->load();

        $this->assertContainerBuilderHasAlias(ViewFactoryInterface::class, 'pagerfanta.view_factory');

        $listeners = [
            'pagerfanta.event_listener.convert_not_valid_max_per_page_to_not_found',
            'pagerfanta.event_listener.convert_not_valid_current_page_to_not_found',
            'pagerfanta.event_listener.convert_invalid_cursor_to_bad_request',
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

        $this->assertContainerBuilderHasServiceDefinitionWithArgument('pagerfanta.twig_runtime', 0, 'default');
        $this->assertContainerBuilderHasServiceDefinitionWithArgument('pagerfanta.twig_runtime', 2, new Reference(PositionRouteGeneratorFactoryInterface::class));
        $this->assertContainerBuilderHasServiceDefinitionWithArgument('pagerfanta.twig_runtime', 3, null);

        $twigConfig = $this->container->getExtensionConfig('twig');

        self::assertArrayHasKey(0, $twigConfig);
        self::assertArrayHasKey('paths', $twigConfig[0]);
        self::assertIsArray($twigConfig[0]['paths']);

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
        if (!class_exists(JMSSerializerBundle::class)) {
            self::markTestSkipped('Test requires JMSSerializerBundle');
        }

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
            'pagerfanta.event_listener.convert_invalid_cursor_to_bad_request',
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

        $this->assertContainerBuilderHasServiceDefinitionWithArgument('pagerfanta.serializer.cursor_handler', 0, new Reference('pagerfanta.cursor_encoder'));
        $this->assertContainerBuilderHasServiceDefinitionWithTag('pagerfanta.serializer.cursor_handler', 'jms_serializer.subscribing_handler');
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
                'invalid_cursor' => Configuration::EXCEPTION_STRATEGY_CUSTOM,
            ],
        ];

        $this->load($bundleConfig);

        $this->assertContainerBuilderHasAlias(ViewFactoryInterface::class, 'pagerfanta.view_factory');

        $listeners = [
            'pagerfanta.event_listener.convert_not_valid_max_per_page_to_not_found',
            'pagerfanta.event_listener.convert_not_valid_current_page_to_not_found',
            'pagerfanta.event_listener.convert_invalid_cursor_to_bad_request',
        ];

        foreach ($listeners as $listener) {
            $this->assertContainerBuilderNotHasService($listener);
        }
    }

    public function testTheExceptionListenersAreRegisteredForTheirStrategies(): void
    {
        $this->container->setParameter(
            'kernel.bundles',
            [
                'BabDevPagerfantaBundle' => BabDevPagerfantaBundle::class,
            ],
        );

        $this->load();

        $this->assertContainerBuilderHasService('pagerfanta.event_listener.convert_not_valid_max_per_page_to_not_found', ConvertNotValidMaxPerPageToNotFoundListener::class);
        $this->assertContainerBuilderHasService('pagerfanta.event_listener.convert_not_valid_current_page_to_not_found', ConvertNotValidCurrentPageToNotFoundListener::class);
        $this->assertContainerBuilderHasService('pagerfanta.event_listener.convert_invalid_cursor_to_bad_request', ConvertInvalidCursorToBadRequestListener::class);
    }

    public function testOnlyTheMaxPerPageListenerIsNotRegisteredWhenTheOutOfRangePageStrategyIsCustom(): void
    {
        $this->container->setParameter(
            'kernel.bundles',
            [
                'BabDevPagerfantaBundle' => BabDevPagerfantaBundle::class,
            ],
        );

        $this->load(['exceptions_strategy' => ['out_of_range_page' => Configuration::EXCEPTION_STRATEGY_CUSTOM]]);

        $this->assertContainerBuilderNotHasService('pagerfanta.event_listener.convert_not_valid_max_per_page_to_not_found');
        $this->assertContainerBuilderHasService('pagerfanta.event_listener.convert_not_valid_current_page_to_not_found', ConvertNotValidCurrentPageToNotFoundListener::class);
        $this->assertContainerBuilderHasService('pagerfanta.event_listener.convert_invalid_cursor_to_bad_request', ConvertInvalidCursorToBadRequestListener::class);
    }

    public function testOnlyTheCurrentPageListenerIsNotRegisteredWhenTheNotValidCurrentPageStrategyIsCustom(): void
    {
        $this->container->setParameter(
            'kernel.bundles',
            [
                'BabDevPagerfantaBundle' => BabDevPagerfantaBundle::class,
            ],
        );

        $this->load(['exceptions_strategy' => ['not_valid_current_page' => Configuration::EXCEPTION_STRATEGY_CUSTOM]]);

        $this->assertContainerBuilderHasService('pagerfanta.event_listener.convert_not_valid_max_per_page_to_not_found', ConvertNotValidMaxPerPageToNotFoundListener::class);
        $this->assertContainerBuilderNotHasService('pagerfanta.event_listener.convert_not_valid_current_page_to_not_found');
        $this->assertContainerBuilderHasService('pagerfanta.event_listener.convert_invalid_cursor_to_bad_request', ConvertInvalidCursorToBadRequestListener::class);
    }

    protected function getContainerExtensions(): array
    {
        return [
            new BabDevPagerfantaExtension(),
        ];
    }

    public function testTheDefaultSequentialViewIsGivenToTheTwigRuntime(): void
    {
        if (!class_exists(PagerfantaExtension::class)) {
            self::markTestSkipped('Test requires Twig');
        }

        $this->container->setParameter(
            'kernel.bundles',
            [
                'BabDevPagerfantaBundle' => BabDevPagerfantaBundle::class,
                'TwigBundle' => TwigBundle::class,
            ],
        );

        $this->load(['default_sequential_view' => 'twitter_bootstrap5_sequential']);

        $this->assertContainerBuilderHasServiceDefinitionWithArgument('pagerfanta.twig_runtime', 3, 'twitter_bootstrap5_sequential');
    }

    private function assertCursorAndRouteGenerationServicesAreRegistered(): void
    {
        $this->assertContainerBuilderHasServiceDefinitionWithArgument('pagerfanta.cursor_encoder.signed', 0, new Reference('pagerfanta.cursor_encoder.base64_json'));
        $this->assertContainerBuilderHasServiceDefinitionWithArgument('pagerfanta.cursor_encoder.signed', 1, '%kernel.secret%');
        $this->assertContainerBuilderHasAlias('pagerfanta.cursor_encoder', 'pagerfanta.cursor_encoder.signed');
        $this->assertContainerBuilderHasAlias(CursorEncoderInterface::class, 'pagerfanta.cursor_encoder');

        $this->assertContainerBuilderHasServiceDefinitionWithArgument('pagerfanta.serializer.cursor_normalizer', 0, new Reference('pagerfanta.cursor_encoder'));
        $this->assertContainerBuilderHasServiceDefinitionWithTag('pagerfanta.serializer.cursor_normalizer', 'serializer.normalizer');

        $this->assertContainerBuilderHasServiceDefinitionWithArgument('pagerfanta.position_resolver', 1, new Reference('pagerfanta.cursor_encoder'));
        $this->assertContainerBuilderHasAlias(PositionResolver::class, 'pagerfanta.position_resolver');

        $this->assertContainerBuilderHasServiceDefinitionWithArgument('pagerfanta.position_route_generator_factory', 3, new Reference('pagerfanta.cursor_encoder'));
        $this->assertContainerBuilderHasAlias(PositionRouteGeneratorFactoryInterface::class, 'pagerfanta.position_route_generator_factory');

        $this->assertContainerBuilderHasServiceDefinitionWithArgument('pagerfanta.route_generator_factory', 3, new Reference('pagerfanta.cursor_encoder'));
        self::assertTrue($this->container->getDefinition('pagerfanta.route_generator_factory')->isDeprecated());
        $this->assertContainerBuilderHasAlias(RouteGeneratorFactoryInterface::class, 'pagerfanta.route_generator_factory');
        self::assertTrue($this->container->getAlias(RouteGeneratorFactoryInterface::class)->isDeprecated());

        foreach (['default', 'foundation6', 'semantic_ui', 'twitter_bootstrap', 'twitter_bootstrap3', 'twitter_bootstrap4', 'twitter_bootstrap5'] as $name) {
            $this->assertContainerBuilderHasServiceDefinitionWithArgument(\sprintf('pagerfanta.view.%s_sequential', $name), 1, \sprintf('%s_sequential', $name));
            $this->assertContainerBuilderHasServiceDefinitionWithTag(\sprintf('pagerfanta.view.%s_sequential', $name), 'pagerfanta.view', ['alias' => \sprintf('%s_sequential', $name)]);
        }
    }
}
