<?php declare(strict_types=1);

namespace BabDev\PagerfantaBundle\View;

use BabDev\PagerfantaBundle\Exception\ImmutableViewFactoryException;
use Pagerfanta\Exception\InvalidArgumentException;
use Pagerfanta\View\ViewFactoryInterface;
use Pagerfanta\View\ViewInterface;
use Psr\Container\ContainerInterface;

final class ContainerBackedImmutableViewFactory implements ViewFactoryInterface
{
    /**
     * @param array<string, string> $serviceMap
     */
    public function __construct(
        private readonly ContainerInterface $container,
        private readonly array $serviceMap,
    ) {}

    /**
     * @param array<string, ViewInterface> $views
     *
     * @throws ImmutableViewFactoryException
     */
    public function add(array $views): void
    {
        throw new ImmutableViewFactoryException(\sprintf('"%s" cannot be modified after instantiation.', self::class));
    }

    /**
     * @return array<string, ViewInterface>
     */
    public function all(): array
    {
        $views = [];

        foreach (array_keys($this->serviceMap) as $viewName) {
            $views[$viewName] = $this->get($viewName);
        }

        return $views;
    }

    /**
     * @throws ImmutableViewFactoryException
     */
    public function clear(): void
    {
        throw new ImmutableViewFactoryException(\sprintf('"%s" cannot be modified after instantiation.', self::class));
    }

    /**
     * @throws InvalidArgumentException if the view does not exist
     */
    public function get(string $name): ViewInterface
    {
        if (!$this->has($name)) {
            throw new InvalidArgumentException(\sprintf('The view "%s" does not exist.', $name));
        }

        return $this->container->get($name);
    }

    public function has(string $name): bool
    {
        return $this->container->has($name);
    }

    /**
     * @throws ImmutableViewFactoryException
     */
    public function remove(string $name): void
    {
        throw new ImmutableViewFactoryException(\sprintf('"%s" cannot be modified after instantiation.', self::class));
    }

    /**
     * @throws ImmutableViewFactoryException
     */
    public function set(string $name, ViewInterface $view): void
    {
        throw new ImmutableViewFactoryException(\sprintf('"%s" cannot be modified after instantiation.', self::class));
    }
}
