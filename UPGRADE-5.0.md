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

### Twig

- The labels of the previous and next links are now translated by the `@BabDevPagerfanta/translations.html.twig` template, which the `pagerfanta.view.twig` service composes after the template being rendered, so the labels are translated for every template which does not override the `previous_page_message` or `next_page_message` blocks
    - Templates extending one of the `@Pagerfanta` templates (such as `@Pagerfanta/twitter_bootstrap5.html.twig`) now have translated labels
- The default value of the `default_twig_template` configuration node changed from `@BabDevPagerfanta/default.html.twig` to `@Pagerfanta/default.html.twig`
- The `default_twig_template` configuration node is now given to the `pagerfanta.view.twig` service as a list of templates, followed by the `@BabDevPagerfanta/translations.html.twig` template
- `BabDev\PagerfantaBundle\LiveComponent\ComponentWithPagerfantaTrait::getPagerfantaOptions()` now includes a `template` option composing the `@BabDevPagerfanta/live_component.html.twig` template with the default Twig template, a `template` option given in its place must include the live component template

## Deprecations

- The `@BabDevPagerfanta/default.html.twig`, `@BabDevPagerfanta/foundation6.html.twig`, `@BabDevPagerfanta/semantic_ui.html.twig`, `@BabDevPagerfanta/tailwind.html.twig`, `@BabDevPagerfanta/twitter_bootstrap.html.twig`, `@BabDevPagerfanta/twitter_bootstrap3.html.twig`, `@BabDevPagerfanta/twitter_bootstrap4.html.twig`, and `@BabDevPagerfanta/twitter_bootstrap5.html.twig` templates are deprecated, use the `@Pagerfanta` template of the same name instead (i.e. `@Pagerfanta/twitter_bootstrap5.html.twig`)

## Removed Features

- Removed `BabDev\PagerfantaBundle\RouteGenerator\RequestAwareRouteGeneratorFactory`, use `BabDev\PagerfantaBundle\RouteGenerator\RequestAwarePositionRouteGeneratorFactory` instead
- Removed `BabDev\PagerfantaBundle\RouteGenerator\RouterAwareRouteGenerator`, use `BabDev\PagerfantaBundle\RouteGenerator\RouterAwarePositionRouteGenerator` instead
- Removed the `pagerfanta.route_generator_factory` service, use the `pagerfanta.position_route_generator_factory` service instead
- Removed the `Pagerfanta\RouteGenerator\RouteGeneratorFactoryInterface` service alias, use the `Pagerfanta\RouteGenerator\PositionRouteGeneratorFactoryInterface` alias instead
