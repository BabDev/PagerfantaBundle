<?php declare(strict_types=1);

namespace BabDev\PagerfantaBundle\DependencyInjection\CompilerPass;

use BabDev\PagerfantaBundle\View\ContainerBackedImmutableViewFactory;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Compiler\ServiceLocatorTagPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * @internal
 */
final class RegisterPagerfantaViewsPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        if (!$container->hasDefinition('pagerfanta.view_factory')) {
            return;
        }

        $this->configureDefaultSequentialView($container);

        $definition = $container->getDefinition('pagerfanta.view_factory');

        if (ContainerBackedImmutableViewFactory::class === $definition->getClass()) {
            /** @var array<string, Reference> $locator */
            $locator = [];

            /** @var array<string, string> $serviceMap */
            $serviceMap = [];

            foreach ($container->findTaggedServiceIds('pagerfanta.view') as $serviceId => $arguments) {
                $alias = $arguments[0]['alias'] ?? $serviceId;

                $locator[$alias] = new Reference($serviceId);
                $serviceMap[$alias] = $serviceId;
            }

            $definition->replaceArgument(0, ServiceLocatorTagPass::register($container, $locator));
            $definition->replaceArgument(1, $serviceMap);

            return;
        }

        foreach ($container->findTaggedServiceIds('pagerfanta.view') as $serviceId => $arguments) {
            $alias = $arguments[0]['alias'] ?? $serviceId;

            $definition->addMethodCall('set', [$alias, new Reference($serviceId)]);
        }
    }

    /**
     * Defaults the sequential view of the Twig runtime to the sequential variant of the default view, when one exists.
     */
    private function configureDefaultSequentialView(ContainerBuilder $container): void
    {
        if (!$container->hasDefinition('pagerfanta.twig_runtime')) {
            return;
        }

        $runtime = $container->getDefinition('pagerfanta.twig_runtime');

        if (null !== $runtime->getArgument(3)) {
            return;
        }

        $defaultView = $runtime->getArgument(0);

        if (!\is_string($defaultView)) {
            return;
        }

        $sequentialView = \sprintf('%s_sequential', $defaultView);

        foreach ($container->findTaggedServiceIds('pagerfanta.view') as $serviceId => $arguments) {
            $attributes = $arguments[0] ?? [];

            if ($sequentialView === (\is_array($attributes) && isset($attributes['alias']) ? $attributes['alias'] : $serviceId)) {
                $runtime->replaceArgument(3, $sequentialView);

                return;
            }
        }
    }
}
