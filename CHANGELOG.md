# Changelog

## 4.7.0 (2026-10-09)

- Add cursor pagination support
- Add support for rendering sequential pagination views (previous/next links only)
- [#66](https://github.com/BabDev/PagerfantaBundle/pull/66) Added Lithuanian locale
- Deprecate `BabDev\PagerfantaBundle\RouteGenerator\RequestAwareRouteGeneratorFactory` and `BabDev\PagerfantaBundle\RouteGenerator\RouterAwareRouteGenerator` in favor of the position based route generator factory and generator
- Deprecate the `pagerfanta.route_generator_factory` service and the `Pagerfanta\RouteGenerator\RouteGeneratorFactoryInterface` alias, use the `pagerfanta.position_route_generator_factory` service and the `Pagerfanta\RouteGenerator\PositionRouteGeneratorFactoryInterface` alias instead
- Fix the `out_of_range_page` and `not_valid_current_page` exception strategies registering each other's exception listeners
- Drop support for Pagerfanta 3.x
- Drop support for Symfony 7.3 and 8.0

## 4.6.0 (2025-11-29)

- Add support for Symfony 7.4 and 8.0
- Drop support for Symfony 7.0 through 7.2

## 4.5.0 (2024-11-03)

- Add support for Symfony 7.1 and 7.2
- Drop support for Symfony 6.3 and 7.0
- [#63](https://github.com/BabDev/PagerfantaBundle/issues/63) Add a serialization context to control how array keys are preserved when serializing Pagerfanta instances

## 4.4.0 (2023-12-20)

- [#60](https://github.com/BabDev/PagerfantaBundle/pull/60) Added Basque locale

## 4.3.0 (2023-12-12)

- Add support for Symfony 7

## 4.2.1 (2023-06-29)

- [#55](https://github.com/BabDev/PagerfantaBundle/pull/55) Fix deprecation in the LegacyPagerfantaNormalizer

## 4.2.0 (2023-06-05)

- [#54](https://github.com/BabDev/PagerfantaBundle/pull/54) Introduce LegacyPagerfantaNormalizer to better address Symfony 6.3 serializer deprecations

## 4.1.0 (2023-05-31)

- Add support for `Symfony\Component\Serializer\Normalizer\NormalizerInterface::getSupportedTypes()` for Symfony 6.3+

## 4.0.0 (2023-03-15)

- Consult the UPGRADE guide for changes between 3.x and 4.0
