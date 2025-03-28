# Adding Views

Views are added to the service container with the `pagerfanta.view` tag. You can also specify an alias which is used as the view's name in a `Pagerfanta\View\ViewFactoryInterface` instance, but if one is not given then the service ID is used instead.

<div class="docs-note">It is recommended that view services are <strong>NOT</strong> public services, the <code>Pagerfanta\View\ViewFactoryInterface</code> should be used to <a href="/open-source/packages/pagerfantabundle/docs/5.x/retrieving-views">retrieve views</a>.</div>

## PHP Configuration

```php
<?php

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Pagerfanta\View\DefaultView;
use Pagerfanta\View\SemanticUiView;

return static function (ContainerConfigurator $container): void {
    $container->services()
        // Use in Twig by calling {{ pagerfanta(pager, 'default') }}
        ->set('pagerfanta.view.default', DefaultView::class)
            ->tag('pagerfanta.view', ['alias' => 'default'])

        // Use in Twig by calling {{ pagerfanta(pager, 'pagerfanta.view.semantic_ui') }}
        ->set('pagerfanta.view.semantic_ui', SemanticUiView::class)
            ->tag('pagerfanta.view')
    ;
};
```

## XML Configuration

```xml
<container>
    <services>
        <!-- Use in Twig by calling {{ pagerfanta(pager, 'default') }} -->
        <service id="pagerfanta.view.default" class="Pagerfanta\View\DefaultView">
            <tag name="pagerfanta.view" alias="default" />
        </service>

        <!-- Use in Twig by calling {{ pagerfanta(pager, 'pagerfanta.view.semantic_ui') }} -->
        <service id="pagerfanta.view.semantic_ui" class="Pagerfanta\View\SemanticUiView">
            <tag name="pagerfanta.view" />
        </service>
    </services>
</container>
```

## YAML Configuration

```yaml
services:
    # Use in Twig by calling {{ pagerfanta(pager, 'default') }}
    pagerfanta.view.default:
        class: Pagerfanta\View\DefaultView
        tags:
            - { name: pagerfanta.view, alias: default }

    # Use in Twig by calling {{ pagerfanta(pager, 'pagerfanta.view.semantic_ui') }}
    pagerfanta.view.semantic_ui:
        class: Pagerfanta\View\SemanticUiView
        tags:
            - { name: pagerfanta.view }
```
