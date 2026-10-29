# Live Components

<div class="docs-note docs-note--new-feature">Live component support was introduced in PagerfantaBundle 4.8.</div>

The bundle integrates with [Symfony UX Live Components](https://symfony.com/bundles/ux-live-component/current/index.html), allowing a live component to change pages without reloading the whole page. The links of the pager keep their URLs, so they also work when the component is not connected, and the URL of the page is kept in sync with the current page.

To use this integration, install the `symfony/ux-live-component` package (version 2.36 or later on the 2.x branch, or 3.1 or later) and the `pagerfanta/twig` package.

For lists which do not need a component, such as a list without filters, [Turbo Frames](/open-source/packages/pagerfantabundle/docs/4.x/rendering-pagerfantas#turbo) can also change pages without reloading the whole page.

## Creating The Component

Add the `BabDev\PagerfantaBundle\LiveComponent\ComponentWithPagerfantaTrait` to your live component and implement the `createPager()` method, which returns a pager for the component. Both offset pagers (`Pagerfanta\PagerfantaInterface`) and cursor pagers (`Pagerfanta\CursorPagerInterface`) are supported, the trait sets the position of the pager from the state of the component.

<div class="docs-note">Cursor pagers are moved to the current position with their <code>withPosition()</code> method, which is implemented by the cursor pagers provided by Pagerfanta and will be required by the <code>Pagerfanta\CursorPagerInterface</code> in Pagerfanta 5.0. A <code>Pagerfanta\Exception\LogicException</code> is thrown when a custom cursor pager does not implement it.</div>

```php
<?php

namespace App\Twig\Components;

use App\Repository\BlogPostRepository;
use BabDev\PagerfantaBundle\LiveComponent\ComponentWithPagerfantaTrait;
use Pagerfanta\Doctrine\ORM\QueryAdapter;
use Pagerfanta\Pagerfanta;
use Pagerfanta\PagerfantaInterface;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\DefaultActionTrait;

#[AsLiveComponent]
final class BlogPostList
{
    use ComponentWithPagerfantaTrait;
    use DefaultActionTrait;

    public function __construct(
        private readonly BlogPostRepository $blogPostRepository,
    ) {}

    protected function createPager(): PagerfantaInterface
    {
        return (new Pagerfanta(new QueryAdapter($this->blogPostRepository->createQueryBuilder('p'))))->setMaxPerPage(10);
    }
}
```

The trait receives the services it needs with setter injection, so the component must be autowired, which is typically the default for services in Symfony applications.

The pager is available in the component's template as `this.pager`. Render it with the `pagerfanta()` Twig function, giving it the `this.pagerfantaOptions` options so the links point to the page the component is rendered on, and compose the `@BabDevPagerfanta/live_component.html.twig` template with the template for your pager's markup. This template adds the attributes which change the page with a live action to each link.

```twig
{# templates/components/BlogPostList.html.twig #}
<div {{ attributes }}>
    {% for post in this.pager %}
        <article>{{ post.title }}</article>
    {% endfor %}

    {{ pagerfanta(this.pager, 'twig', this.pagerfantaOptions|merge({'template': ['@BabDevPagerfanta/live_component.html.twig', '@BabDevPagerfanta/twitter_bootstrap5.html.twig']})) }}
</div>
```

Composing the templates with a list requires Twig 3.29 or later. With older Twig versions, create a template which extends the template for your pager's markup and uses the live component template, and give its name as the `template` option instead.

```twig
{# templates/pagination/live_component.html.twig #}
{%- extends '@BabDevPagerfanta/twitter_bootstrap5.html.twig' -%}
{%- use '@BabDevPagerfanta/live_component.html.twig' -%}
```

<div class="docs-note">The live component template works by overriding the <code>link_attributes</code> block of the Twig templates, see the <a href="/open-source/packages/pagerfanta/docs/4.x/views#link-attributes">Pagerfanta documentation</a> for more details.</div>

## Component State

The trait adds the following properties to the component:

- `page` - The current page of an offset pager, a writable live prop synchronized with the `page` query string parameter
- `cursor` - The encoded cursor of the current page of a cursor pager, or null for the first page, a writable live prop synchronized with the `cursor` query string parameter
- `paginationRoute` - The route the links of the pager point to
- `paginationRouteParams` - The parameters for the route the links of the pager point to

The current page is changed with the `goToPage` and `goToCursor` live actions, which are used by the links of the pager, and the `resetPage()` method returns the component to the first page.

The position of the pager is resolved before the component is rendered. When the current page of an offset pager is out of range, such as after a filter reduces the number of results, the component moves to the last page instead of throwing an exception. When a cursor cannot be decoded, such as when it was tampered with, the component returns to the first page.

## Filters

When the component has filters, return to the first page when they change, and add them to the parameters of the links by overriding the `getPaginationRouteParams()` method so the links keep the filters when the component is not connected.

```php
<?php

namespace App\Twig\Components;

use BabDev\PagerfantaBundle\LiveComponent\ComponentWithPagerfantaTrait;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;

#[AsLiveComponent]
final class BlogPostList
{
    use ComponentWithPagerfantaTrait;
    use DefaultActionTrait;

    #[LiveProp(writable: true, url: true, onUpdated: 'onQueryUpdated')]
    public string $query = '';

    public function onQueryUpdated(): void
    {
        $this->resetPage();
    }

    protected function getPaginationRouteParams(): array
    {
        return [...$this->paginationRouteParams, 'query' => $this->query];
    }

    // ...
}
```

## Link Routes

Live components are rendered again with an internal route, so the route of the links is resolved when the component is first rendered, using the route and route parameters of the page. When the component is first rendered by another live component, the route of the page is not known, so set the `paginationRoute` property (and the `paginationRouteParams` property if the route has required parameters) when rendering the component.

```twig
<twig:BlogPostList paginationRoute="app_blog_category" :paginationRouteParams="{category: category.slug}" />
```

The links use the `page` and `cursor` parameters by default, which match the query string parameters of the component's state. If you change these parameters with the `pageParameter` or `cursorParameter` options, also change the query string parameter of the live prop by redeclaring the property in your component.

```php
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\Metadata\UrlMapping;

#[LiveProp(writable: true, url: new UrlMapping(as: 'p'))]
public int $page = 1;
```

```twig
{{ pagerfanta(this.pager, 'twig', this.pagerfantaOptions|merge({'pageParameter': '[p]', 'template': ['@BabDevPagerfanta/live_component.html.twig', '@BabDevPagerfanta/twitter_bootstrap5.html.twig']})) }}
```
