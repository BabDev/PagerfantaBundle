# Upgrade from 4.x to 5.0

The below guide will assist in upgrading from the 4.x versions to 5.0.

PagerfantaBundle 5.0 requires Pagerfanta 5.0 and removes the page number based route generators which were deprecated in 4.7. Upgrading to 4.7 and resolving its deprecations first is the recommended upgrade path. Please also review the [Pagerfanta upgrade guide](https://github.com/BabDev/Pagerfanta/blob/5.x/UPGRADE-5.0.md) for the changes in the library.

## Bundle Requirements

- Symfony 6.4, 7.4, or 8.0+
- PHP 8.4 or later
- Pagerfanta 5.0 or later

## General Changes

- Dropped support for versions of `jms/serializer` before 3.32
- Dropped support for versions of JMSSerializerBundle before 5.4
- Dropped support for versions of Twig before 3.29
- `BabDev\PagerfantaBundle\Serializer\Normalizer\PagerfantaNormalizer` and `BabDev\PagerfantaBundle\Serializer\Handler\PagerfantaHandler` now serialize any `Pagerfanta\OffsetPagerInterface`
- Made the cursor encoder a required argument in the `BabDev\PagerfantaBundle\RouteGenerator\RequestAwarePositionRouteGeneratorFactory` constructor

## Removed Features

- Removed `BabDev\PagerfantaBundle\RouteGenerator\RequestAwareRouteGeneratorFactory`, use `BabDev\PagerfantaBundle\RouteGenerator\RequestAwarePositionRouteGeneratorFactory` instead
- Removed `BabDev\PagerfantaBundle\RouteGenerator\RouterAwareRouteGenerator`, use `BabDev\PagerfantaBundle\RouteGenerator\RouterAwarePositionRouteGenerator` instead
- Removed the `pagerfanta.route_generator_factory` service, use the `pagerfanta.position_route_generator_factory` service instead
- Removed the `Pagerfanta\RouteGenerator\RouteGeneratorFactoryInterface` service alias, use the `Pagerfanta\RouteGenerator\PositionRouteGeneratorFactoryInterface` alias instead
