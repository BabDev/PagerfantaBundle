<?php declare(strict_types=1);

namespace BabDev\PagerfantaBundle\Tests\DependencyInjection\CompilerPass;

use BabDev\PagerfantaBundle\DependencyInjection\CompilerPass\RegisterPagerfantaViewsPass;
use BabDev\PagerfantaBundle\View\ContainerBackedImmutableViewFactory;
use Composer\InstalledVersions;
use Matthias\SymfonyDependencyInjectionTest\PhpUnit\AbstractCompilerPassTestCase;
use Pagerfanta\View\DefaultView;
use Pagerfanta\View\ViewFactory;
use Symfony\Component\DependencyInjection\Argument\AbstractArgument;
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

        // The locator ID is different at Symfony 7.1
        $version = InstalledVersions::getVersion('symfony/dependency-injection');

        if (null === $version) {
            self::fail('Could not detect installed version of symfony/dependency-injection');
        }

        $locatorId = version_compare($version, '7.1', '>=') ? '.service_locator.mde9.qA' : '.service_locator.3Jj7I65';

        $this->assertContainerBuilderHasServiceDefinitionWithArgument(
            'pagerfanta.view_factory',
            0,
            new Reference($locatorId),
        );
        $this->assertContainerBuilderHasServiceDefinitionWithArgument(
            'pagerfanta.view_factory',
            1,
            ['default' => 'pagerfanta.view.default'],
        );
    }

    protected function registerCompilerPass(ContainerBuilder $container): void
    {
        $container->addCompilerPass(new RegisterPagerfantaViewsPass());
    }
}
