<?php declare(strict_types=1);

namespace BabDev\PagerfantaBundle\RouteGenerator;

use Pagerfanta\Cursor\Base64JsonCursorEncoder;
use Pagerfanta\Cursor\CursorEncoderInterface;
use Pagerfanta\RouteGenerator\PositionRouteGeneratorFactoryInterface;
use Pagerfanta\RouteGenerator\PositionRouteGeneratorInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Creates position route generators for the current route, unless another route is set in the options.
 */
final class RequestAwarePositionRouteGeneratorFactory implements PositionRouteGeneratorFactoryInterface
{
    use ResolvesRouteGeneratorOptions;

    public function __construct(
        private readonly UrlGeneratorInterface $router,
        private readonly RequestStack $requestStack,
        private readonly PropertyAccessorInterface $propertyAccessor,
        private readonly CursorEncoderInterface $cursorEncoder = new Base64JsonCursorEncoder(),
    ) {}

    public function createPositionRouteGenerator(array $options = []): PositionRouteGeneratorInterface
    {
        return new RouterAwarePositionRouteGenerator(
            $this->router,
            $this->propertyAccessor,
            $this->cursorEncoder,
            $this->resolveOptions($options),
        );
    }
}
