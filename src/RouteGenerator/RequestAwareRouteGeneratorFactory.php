<?php declare(strict_types=1);

namespace BabDev\PagerfantaBundle\RouteGenerator;

use Pagerfanta\Exception\RuntimeException;
use Pagerfanta\RouteGenerator\RouteGeneratorFactoryInterface;
use Pagerfanta\RouteGenerator\RouteGeneratorInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final readonly class RequestAwareRouteGeneratorFactory implements RouteGeneratorFactoryInterface
{
    public function __construct(
        private UrlGeneratorInterface $router,
        private RequestStack $requestStack,
        private PropertyAccessorInterface $propertyAccessor
    ) {}

    public function create(array $options = []): RouteGeneratorInterface
    {
        $options = array_replace(
            [
                'routeName' => null,
                'routeParams' => [],
                'pageParameter' => '[page]',
                'omitFirstPage' => false,
            ],
            $options
        );

        if (null === $options['routeName']) {
            $request = $this->getRequest();

            if (!$request instanceof Request) {
                throw new RuntimeException('The request aware route generator can not be used when there is not an active request.');
            }

            if ($this->requestStack->getParentRequest() instanceof Request) {
                throw new RuntimeException('The request aware route generator can not guess the route when used in a sub-request, pass the "routeName" option to use this generator.');
            }

            $options['routeName'] = $request->attributes->get('_route');

            // Make sure we read the route parameters from the passed option array
            $defaultRouteParams = array_merge($request->query->all(), $request->attributes->get('_route_params', []));

            $options['routeParams'] = array_merge($defaultRouteParams, $options['routeParams']);
        }

        return new RouterAwareRouteGenerator(
            $this->router,
            $this->propertyAccessor,
            $options,
        );
    }

    private function getRequest(): ?Request
    {
        return $this->requestStack->getCurrentRequest();
    }
}
