<?php declare(strict_types=1);

namespace BabDev\PagerfantaBundle\RouteGenerator;

use Pagerfanta\Cursor\Base64JsonCursorEncoder;
use Pagerfanta\Cursor\CursorEncoderInterface;
use Pagerfanta\RouteGenerator\PositionRouteGeneratorFactoryInterface;
use Pagerfanta\RouteGenerator\PositionRouteGeneratorInterface;
use Pagerfanta\RouteGenerator\RouteGeneratorFactoryInterface;
use Pagerfanta\RouteGenerator\RouteGeneratorInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Creates route generators for the current route, unless another route is set in the options.
 *
 * @deprecated since PagerfantaBundle 4.7, use the {@see RequestAwarePositionRouteGeneratorFactory} instead
 */
final class RequestAwareRouteGeneratorFactory implements RouteGeneratorFactoryInterface, PositionRouteGeneratorFactoryInterface
{
    use ResolvesRouteGeneratorOptions;

    /**
     * @param CursorEncoderInterface $cursorEncoder The encoder for the cursors in the generated URLs, the bundle configures an encoder which signs the cursors
     */
    public function __construct(
        private readonly UrlGeneratorInterface $router,
        private readonly RequestStack $requestStack,
        private readonly PropertyAccessorInterface $propertyAccessor,
        private readonly CursorEncoderInterface $cursorEncoder = new Base64JsonCursorEncoder(),
    ) {
        trigger_deprecation('babdev/pagerfanta-bundle', '4.7', 'The "%s" class is deprecated, use the "%s" class instead.', self::class, RequestAwarePositionRouteGeneratorFactory::class);
    }

    public function create(array $options = []): RouteGeneratorInterface
    {
        return new RouterAwareRouteGenerator(
            $this->router,
            $this->propertyAccessor,
            $this->resolveOptions($options),
        );
    }

    public function createPositionRouteGenerator(array $options = []): PositionRouteGeneratorInterface
    {
        return (new RequestAwarePositionRouteGeneratorFactory($this->router, $this->requestStack, $this->propertyAccessor, $this->cursorEncoder))->createPositionRouteGenerator($options);
    }
}
