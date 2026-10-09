# Available Views

## Default Views

All of the views provided in the `pagerfanta/core` package are available by default for use with this bundle.

The below table lists the view names and the corresponding class. 

| View Name            | Class Name                              |
|----------------------|-----------------------------------------|
| `default`            | `Pagerfanta\View\DefaultView`           |
| `foundation6`        | `Pagerfanta\View\Foundation6View`       |
| `semantic_ui`        | `Pagerfanta\View\SemanticUiView`        |
| `twitter_bootstrap`  | `Pagerfanta\View\TwitterBootstrapView`  |
| `twitter_bootstrap3` | `Pagerfanta\View\TwitterBootstrap3View` |
| `twitter_bootstrap4` | `Pagerfanta\View\TwitterBootstrap4View` |
| `twitter_bootstrap5` | `Pagerfanta\View\TwitterBootstrap5View` |

## Sequential Views

<div class="docs-note docs-note--new-feature">The sequential views were introduced in PagerfantaBundle 4.7.</div>

The views above render numbered page links, so they can only render offset pagers. The [sequential views](/open-source/packages/pagerfanta/docs/4.x/views#sequential-views) render only the links to the previous and next pages, so they can render any pager, including [cursor pagers](/open-source/packages/pagerfantabundle/docs/4.x/cursor-pagination).

A sequential view is available for each of the default views, all using the `Pagerfanta\View\SequentialView` class.

| View Name                       | Template Class Name                                  |
|---------------------------------|------------------------------------------------------|
| `default_sequential`            | `Pagerfanta\View\Template\DefaultTemplate`           |
| `foundation6_sequential`        | `Pagerfanta\View\Template\Foundation6Template`       |
| `semantic_ui_sequential`        | `Pagerfanta\View\Template\SemanticUiTemplate`        |
| `twitter_bootstrap_sequential`  | `Pagerfanta\View\Template\TwitterBootstrapTemplate`  |
| `twitter_bootstrap3_sequential` | `Pagerfanta\View\Template\TwitterBootstrap3Template` |
| `twitter_bootstrap4_sequential` | `Pagerfanta\View\Template\TwitterBootstrap4Template` |
| `twitter_bootstrap5_sequential` | `Pagerfanta\View\Template\TwitterBootstrap5Template` |

When the `pagerfanta()` Twig function is given a pager which its view cannot render, the pager is rendered with the [default sequential view](/open-source/packages/pagerfantabundle/docs/4.x/configuring-the-bundle#default-sequential-view).

## Twig View

This bundle provides a Pagerfanta view which renders a Twig template. If you have not already, you will need to install the `pagerfanta/twig` package.

The below table lists the available templates and the CSS framework they correspond to.

| Template Name                                    | Framework                                                     |
|--------------------------------------------------|---------------------------------------------------------------|
| `@BabDevPagerfanta/default.html.twig`            | None (Pagerfanta's default view)                              |
| `@BabDevPagerfanta/foundation6.html.twig`        | [Foundation](https://get.foundation/index.html) (version 6.x) |
| `@BabDevPagerfanta/semantic_ui.html.twig`        | [Semantic UI](https://semantic-ui.com) (version 2.x)          |
| `@BabDevPagerfanta/tailwind.html.twig`           | [Tailwind CSS](https://tailwindcss.com/)                      |
| `@BabDevPagerfanta/twitter_bootstrap.html.twig`  | [Bootstrap](https://getbootstrap.com) (version 2.x)           |
| `@BabDevPagerfanta/twitter_bootstrap3.html.twig` | [Bootstrap](https://getbootstrap.com) (version 3.x)           |
| `@BabDevPagerfanta/twitter_bootstrap4.html.twig` | [Bootstrap](https://getbootstrap.com) (version 5.x)           |
| `@BabDevPagerfanta/twitter_bootstrap5.html.twig` | [Bootstrap](https://getbootstrap.com) (version 5.x)           |

The labels of the "Previous" and "Next" buttons are localizable in the Twig templates.

The Twig view can render any pager. Offset pagers are rendered with numbered page links, and all other pagers (such as cursor pagers) are rendered with only the previous and next links using the same templates.

See the [Pagerfanta documentation](/open-source/packages/pagerfanta/docs/views) for more information about building a Twig template.

## Default View CSS

The bundle comes with basic CSS for the default view so you can get started quickly.

```twig
<link rel="stylesheet" href="{{ asset('bundles/babdevpagerfanta/css/pagerfanta.css') }}">
```

If you are using the [AssetMapper component](https://symfony.com/doc/current/frontend/asset_mapper.html) in your application, the CSS file can also be loaded through that component. Please see the Symfony documentation for examples of [importing assets from bundles](https://symfony.com/doc/current/frontend/asset_mapper.html#third-party-bundles-custom-asset-paths) and [importing assets outside your `/assets` directory](https://symfony.com/doc/current/frontend/asset_mapper.html#importing-assets-outside-of-the-assets-directory).
