# Configuring The Bundle

## Default View

The default view for your application can be set with the `default_view` configuration node. This defaults to "default".

```yaml
# config/packages/babdev_pagerfanta.yaml
babdev_pagerfanta:
    default_view: my_view
```

## Default Sequential View

<div class="docs-note docs-note--new-feature">The default sequential view was introduced in PagerfantaBundle 4.7.</div>

Views with numbered page links can only render offset pagers. When the default view (or the view given to the `pagerfanta()` Twig function) cannot render a pager, such as a [cursor pager](/open-source/packages/pagerfantabundle/docs/4.x/cursor-pagination), the Twig function renders it with the default sequential view instead.

The default sequential view can be set with the `default_sequential_view` configuration node. When not set, the sequential variant of the default view is used if one exists (i.e. `twitter_bootstrap5_sequential` for the `twitter_bootstrap5` view). The Twig view can render any pager, so it does not need a default sequential view.

```yaml
# config/packages/babdev_pagerfanta.yaml
babdev_pagerfanta:
    default_view: twitter_bootstrap5
    default_sequential_view: twitter_bootstrap5_sequential
```

## Default Twig Template

The default Twig template for Twig views in your application can be set with the `default_twig_template` configuration node. This defaults to "`@BabDevPagerfanta/default.html.twig`".

```yaml
# config/packages/babdev_pagerfanta.yaml
babdev_pagerfanta:
    default_view: twig
    default_twig_template: '@App/Pagerfanta/default.html.twig'
```

## Exception Strategies

By default, the bundle converts `Pagerfanta\Exception\NotValidCurrentPageException` and `Pagerfanta\Exception\NotValidMaxPerPageException` exceptions into 404 responses, and `Pagerfanta\Exception\InvalidCursorException` exceptions into 400 responses. If you would like to disable or change this behavior, you can change the strategies using the `exceptions_strategy` node by setting the value to "custom" for each behavior you want to change.

```yaml
# config/packages/babdev_pagerfanta.yaml
babdev_pagerfanta:
    exceptions_strategy:
        out_of_range_page: custom # Disables converting `Pagerfanta\Exception\NotValidMaxPerPageException` to a 404 response
        not_valid_current_page: to_http_not_found # Default behavior converting `Pagerfanta\Exception\NotValidCurrentPageException` to a 404 response
        invalid_cursor: to_http_bad_request # Default behavior converting `Pagerfanta\Exception\InvalidCursorException` to a 400 response
```

<div class="docs-note docs-note--new-feature">The <code>invalid_cursor</code> exception strategy was introduced in PagerfantaBundle 4.7.</div>
