<?php declare(strict_types=1);

namespace BabDev\PagerfantaBundle;

use BabDev\PagerfantaBundle\DependencyInjection\BabDevPagerfantaExtension;
use BabDev\PagerfantaBundle\DependencyInjection\CompilerPass\RegisterPagerfantaViewsPass;
use BabDev\PagerfantaBundle\DependencyInjection\CompilerPass\RegisterTwigUndefinedCallablePass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\ExtensionInterface;
use Symfony\Component\HttpKernel\Bundle\Bundle;

final class BabDevPagerfantaBundle extends Bundle
{
    public function build(ContainerBuilder $container): void
    {
        parent::build($container);

        $container->addCompilerPass(new RegisterPagerfantaViewsPass());
        $container->addCompilerPass(new RegisterTwigUndefinedCallablePass());
    }

    #[\Override]
    public function getContainerExtension(): ?ExtensionInterface
    {
        if (null === $this->extension) {
            $this->extension = new BabDevPagerfantaExtension();
        }

        return $this->extension ?: null;
    }

    #[\Override]
    public function getPath(): string
    {
        return \dirname(__DIR__);
    }
}
