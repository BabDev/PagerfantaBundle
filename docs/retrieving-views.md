# Retrieving Views

You can access the Pagerfanta views through the `pagerfanta.view_factory` service, which is a `Pagerfanta\View\ViewFactoryInterface` instance. This is useful if your application does not use Twig but you still want to use Pagerfanta views for rendering pagination lists.

```php
<?php

namespace App\Service;

use Pagerfanta\PagerInterface;
use Pagerfanta\RouteGenerator\PositionRouteGeneratorFactoryInterface;
use Pagerfanta\View\ViewFactoryInterface;

final class PagerfantaService
{
    public function __construct(
        private readonly ViewFactoryInterface $viewFactory,
        private readonly PositionRouteGeneratorFactoryInterface $routeGeneratorFactory,
    ) {
    }

    public function render(PagerInterface $pager, string $view, array $options = []): string
    {
        return $this->viewFactory->get($view)->render($pager, $this->routeGeneratorFactory->createPositionRouteGenerator($options), $options);
    }
}
```
