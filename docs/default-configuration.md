# Default Configuration

```yaml
babdev_pagerfanta:
    # The default Pagerfanta view to use in your application
    default_view: default

    # The view to render pagers which the default view cannot render (i.e. cursor pagers with a numbered view), defaults to the "<default_view>_sequential" view when one exists
    default_sequential_view: null

    # The default Twig template to use when using the Twig Pagerfanta view
    default_twig_template: '@BabDevPagerfanta/default.html.twig'

    exceptions_strategy:
        # The exception strategy if requesting a page outside the available pages in a paginated list; valid options are "custom" or "to_http_not_found"
        out_of_range_page: to_http_not_found

        # The exception strategy if the current page is not an allowed value in a paginated list; valid options are "custom" or "to_http_not_found"
        not_valid_current_page: to_http_not_found

        # The exception strategy if a cursor cannot be decoded or fails validation (i.e. a tampered cursor); valid options are "custom" or "to_http_bad_request"
        invalid_cursor: to_http_bad_request
```
