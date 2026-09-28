<?php declare(strict_types=1);

namespace BabDev\PagerfantaBundle\RouteGenerator;

use Pagerfanta\Cursor\Base64JsonCursorEncoder;
use Pagerfanta\Cursor\CursorEncoderInterface;
use Pagerfanta\Exception\RuntimeException;
use Pagerfanta\RouteGenerator\PositionRouteGeneratorFactoryInterface;
use Pagerfanta\RouteGenerator\PositionRouteGeneratorInterface;
use Pagerfanta\RouteGenerator\RouteGeneratorFactoryInterface;
use Pagerfanta\RouteGenerator\RouteGeneratorInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Creates route generators for the current route, unless another route is set in the options.
 */
final class RequestAwareRouteGeneratorFactory implements RouteGeneratorFactoryInterface, PositionRouteGeneratorFactoryInterface
{
    public function __construct(
        private readonly UrlGeneratorInterface $router,
        private readonly RequestStack $requestStack,
        private readonly PropertyAccessorInterface $propertyAccessor,
        private readonly CursorEncoderInterface $cursorEncoder = new Base64JsonCursorEncoder(),
    ) {}

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
        return new RouterAwarePositionRouteGenerator(
            $this->router,
            $this->propertyAccessor,
            $this->cursorEncoder,
            $this->resolveOptions($options),
        );
    }

    /**
     * @param array<string, mixed> $options
     *
     * @return array<string, mixed>
     *
     * @throws RuntimeException if the route cannot be resolved from the current request
     */
    private function resolveOptions(array $options): array
    {
        $options = array_replace(
            [
                'routeName' => null,
                'routeParams' => [],
                'pageParameter' => '[page]',
                'cursorParameter' => '[cursor]',
                'omitFirstPage' => false,
            ],
            $options
        );

        if (null === $options['routeName']) {
            $request = $this->getRequest();

            if (null === $request) {
                throw new RuntimeException('The request aware route generator can not be used when there is not an active request.');
            }

            if (null !== $this->requestStack->getParentRequest()) {
                throw new RuntimeException('The request aware route generator can not guess the route when used in a sub-request, pass the "routeName" option to use this generator.');
            }

            $options['routeName'] = $request->attributes->get('_route');

            // Make sure we read the route parameters from the passed option array
            $defaultRouteParams = array_merge($request->query->all(), $request->attributes->get('_route_params', []));

            $options['routeParams'] = array_merge($defaultRouteParams, $options['routeParams']);
        }

        return $options;
    }

    private function getRequest(): ?Request
    {
        return $this->requestStack->getCurrentRequest();
    }
}
