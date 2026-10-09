<?php declare(strict_types=1);

namespace BabDev\PagerfantaBundle\RouteGenerator;

use Pagerfanta\Cursor\CursorEncoderInterface;
use Pagerfanta\Exception\InvalidArgumentException;
use Pagerfanta\Exception\RuntimeException;
use Pagerfanta\RouteGenerator\PositionRouteGeneratorFactoryInterface;
use Pagerfanta\RouteGenerator\PositionRouteGeneratorInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Creates position route generators for the current route, unless another route is set in the options.
 */
final readonly class RequestAwarePositionRouteGeneratorFactory implements PositionRouteGeneratorFactoryInterface
{
    public function __construct(
        private UrlGeneratorInterface $router,
        private RequestStack $requestStack,
        private PropertyAccessorInterface $propertyAccessor,
        private CursorEncoderInterface $cursorEncoder,
    ) {}

    /**
     * @throws InvalidArgumentException if the route parameters option is not an array
     * @throws RuntimeException         if the route cannot be resolved from the current request
     */
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
     * Resolves the options for the route generator, using the current route unless another route is set in the options.
     *
     * @param array<string, mixed> $options
     *
     * @return array<string, mixed>
     *
     * @throws InvalidArgumentException if the route parameters option is not an array
     * @throws RuntimeException         if the route cannot be resolved from the current request
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
            $request = $this->requestStack->getCurrentRequest();

            if (null === $request) {
                throw new RuntimeException('The request aware route generator can not be used when there is not an active request.');
            }

            if (null !== $this->requestStack->getParentRequest()) {
                throw new RuntimeException('The request aware route generator can not guess the route when used in a sub-request, pass the "routeName" option to use this generator.');
            }

            $options['routeName'] = $request->attributes->get('_route');

            if (!\is_array($options['routeParams'])) {
                throw new InvalidArgumentException(\sprintf('The "routeParams" option must be an array, "%s" given.', get_debug_type($options['routeParams'])));
            }

            $requestRouteParams = $request->attributes->get('_route_params', []);

            // Make sure we read the route parameters from the passed option array
            $defaultRouteParams = array_merge($request->query->all(), \is_array($requestRouteParams) ? $requestRouteParams : []);

            $options['routeParams'] = array_merge($defaultRouteParams, $options['routeParams']);
        }

        return $options;
    }
}
