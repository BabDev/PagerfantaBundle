<?php declare(strict_types=1);

namespace BabDev\PagerfantaBundle\Tests\View;

use BabDev\PagerfantaBundle\Exception\ImmutableViewFactoryException;
use BabDev\PagerfantaBundle\View\ContainerBackedImmutableViewFactory;
use Pagerfanta\View\DefaultView;
use Pagerfanta\View\ViewInterface;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;

final class ContainerBackedImmutableViewFactoryTest extends TestCase
{
    public function testCannotAddViewsAfterInstantiation(): void
    {
        $this->expectException(ImmutableViewFactoryException::class);

        new ContainerBackedImmutableViewFactory($this->createMock(ContainerInterface::class), [])
            ->add(['default' => new DefaultView()]);
    }

    public function testCannotRemoveViewsAfterInstantiation(): void
    {
        $this->expectException(ImmutableViewFactoryException::class);

        new ContainerBackedImmutableViewFactory($this->createMock(ContainerInterface::class), [])
            ->remove('default');
    }

    public function testCannotSetViewsAfterInstantiation(): void
    {
        $this->expectException(ImmutableViewFactoryException::class);

        new ContainerBackedImmutableViewFactory($this->createMock(ContainerInterface::class), [])
            ->set('default', new DefaultView());
    }

    public function testRetrievesAllViewsFromTheContainer(): void
    {
        $views = ['default' => new DefaultView()];

        self::assertSame($views, new ContainerBackedImmutableViewFactory($this->createContainer($views), ['default' => 'default'])->all());
    }

    public function testRetrievesNamedViewFromTheContainer(): void
    {
        $defaultView = new DefaultView();

        $views = ['default' => $defaultView];

        self::assertSame($defaultView, new ContainerBackedImmutableViewFactory($this->createContainer($views), ['default' => 'default'])->get('default'));
    }

    public function testReportsIfAViewExistsInTheContainer(): void
    {
        $views = ['default' => new DefaultView()];

        self::assertTrue(new ContainerBackedImmutableViewFactory($this->createContainer($views), ['default' => 'default'])->has('default'));
    }

    /**
     * @param array<string, ViewInterface> $views
     */
    private function createContainer(array $views): ContainerInterface
    {
        return new readonly class($views) implements ContainerInterface {
            /**
             * @param array<string, ViewInterface> $views
             */
            public function __construct(private array $views) {}

            public function get(string $id)
            {
                return $this->views[$id] ?? throw new class extends \RuntimeException implements NotFoundExceptionInterface {};
            }

            public function has(string $id): bool
            {
                return isset($this->views[$id]);
            }
        };
    }
}
