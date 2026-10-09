<?php declare(strict_types=1);

namespace BabDev\PagerfantaBundle\Tests\DependencyInjection\CompilerPass;

use BabDev\PagerfantaBundle\DependencyInjection\CompilerPass\RegisterPagerfantaViewsPass;
use BabDev\PagerfantaBundle\View\ContainerBackedImmutableViewFactory;
use Matthias\SymfonyDependencyInjectionTest\PhpUnit\AbstractCompilerPassTestCase;
use Pagerfanta\Twig\Extension\PagerfantaRuntime;
use Pagerfanta\View\DefaultView;
use Pagerfanta\View\SequentialView;
use Pagerfanta\View\ViewFactory;
use Symfony\Component\DependencyInjection\Argument\AbstractArgument;
use Symfony\Component\DependencyInjection\Argument\ServiceClosureArgument;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

final class RegisterPagerfantaViewsPassTest extends AbstractCompilerPassTestCase
{
    public function testViewsAreAddedToTheViewFactory(): void
    {
        $this->registerService('pagerfanta.view_factory', ViewFactory::class);
        $this->registerService('pagerfanta.view.default', DefaultView::class)
            ->addTag('pagerfanta.view', ['alias' => 'default']);

        $this->compile();

        $this->assertContainerBuilderHasService('pagerfanta.view.default', DefaultView::class);
        $this->assertContainerBuilderHasServiceDefinitionWithMethodCall(
            'pagerfanta.view_factory',
            'set',
            ['default', new Reference('pagerfanta.view.default')]
        );
    }

    public function testViewsAreAddedToTheContainerBackedViewFactory(): void
    {
        $this->registerService('pagerfanta.view_factory', ContainerBackedImmutableViewFactory::class)
            ->addArgument(new AbstractArgument('service locator'))
            ->addArgument(new AbstractArgument('service map'));
        $this->registerService('pagerfanta.view.default', DefaultView::class)
            ->addTag('pagerfanta.view', ['alias' => 'default']);

        $this->compile();

        $this->assertContainerBuilderHasService('pagerfanta.view.default', DefaultView::class);

        $this->assertContainerBuilderHasServiceDefinitionWithServiceLocatorArgument(
            'pagerfanta.view_factory',
            0,
            ['default' => new ServiceClosureArgument(new Reference('pagerfanta.view.default'))],
        );
        $this->assertContainerBuilderHasServiceDefinitionWithArgument(
            'pagerfanta.view_factory',
            1,
            ['default' => 'pagerfanta.view.default'],
        );
    }

    public function testTheDefaultSequentialViewIsTheSequentialVariantOfTheDefaultView(): void
    {
        $this->registerService('pagerfanta.view_factory', ViewFactory::class);
        $this->registerService('pagerfanta.view.twitter_bootstrap5_sequential', SequentialView::class)
            ->addTag('pagerfanta.view', ['alias' => 'twitter_bootstrap5_sequential']);
        $this->registerService('pagerfanta.twig_runtime', PagerfantaRuntime::class)
            ->setArguments(['twitter_bootstrap5', new Reference('pagerfanta.view_factory'), new Reference('pagerfanta.route_generator_factory'), null]);

        $this->compile();

        $this->assertContainerBuilderHasServiceDefinitionWithArgument('pagerfanta.twig_runtime', 3, 'twitter_bootstrap5_sequential');
    }

    public function testTheDefaultSequentialViewIsNotSetWhenTheDefaultViewHasNoSequentialVariant(): void
    {
        $this->registerService('pagerfanta.view_factory', ViewFactory::class);
        $this->registerService('pagerfanta.twig_runtime', PagerfantaRuntime::class)
            ->setArguments(['twig', new Reference('pagerfanta.view_factory'), new Reference('pagerfanta.route_generator_factory'), null]);

        $this->compile();

        $this->assertContainerBuilderHasServiceDefinitionWithArgument('pagerfanta.twig_runtime', 3, null);
    }

    public function testAConfiguredDefaultSequentialViewIsKept(): void
    {
        $this->registerService('pagerfanta.view_factory', ViewFactory::class);
        $this->registerService('pagerfanta.view.default_sequential', SequentialView::class)
            ->addTag('pagerfanta.view', ['alias' => 'default_sequential']);
        $this->registerService('pagerfanta.twig_runtime', PagerfantaRuntime::class)
            ->setArguments(['default', new Reference('pagerfanta.view_factory'), new Reference('pagerfanta.route_generator_factory'), 'custom_sequential']);

        $this->compile();

        $this->assertContainerBuilderHasServiceDefinitionWithArgument('pagerfanta.twig_runtime', 3, 'custom_sequential');
    }

    protected function registerCompilerPass(ContainerBuilder $container): void
    {
        $container->addCompilerPass(new RegisterPagerfantaViewsPass());
    }
}
