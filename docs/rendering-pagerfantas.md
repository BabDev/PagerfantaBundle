# Rendering Pagerfantas

First, you'll need to pass an instance of Pagerfanta as a parameter into your template.

```php
<?php

namespace App\Controller;

use App\Entity\BlogPostRepository;
use Pagerfanta\Doctrine\ORM\QueryAdapter;
use Pagerfanta\Pagerfanta;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

final class BlogController extends AbstractController
{
    #[Route(path: '/blog', name: 'app_blog_list', methods: ['GET'])]
    public function listPosts(BlogPostRepository $blogPostRepository): Response
    {
        $queryBuilder = $blogPostRepository->createBlogListQueryBuilder();

        $pagerfanta = new Pagerfanta(
            new QueryAdapter($queryBuilder),
        );

        return $this->render(
            'blog/list.html.twig',
            [
                'pager' => $pagerfanta,
            ],
        );
    }
}
```

You then call the `pagerfanta` function in your Twig template, passing in the `Pagerfanta\Pagerfanta` instance. The routes are generated automatically for the current route using the variable "page" to propagate the page number. By default, the bundle uses the `Pagerfanta\View\DefaultView` class to render the pager.

```twig
{{ pagerfanta(pager) }}
```

By default, the "page" variable is also added for the link to the first page. To disable the generation of `?page=1` in the URL, set the `omitFirstPage` option to `true` when calling the `pagerfanta()` Twig function.

```twig
{{ pagerfanta(pager, 'default', {'omitFirstPage': true}) }}
```

You can omit the template parameter to make function call shorter, in this case the default template will be used.

```twig
{{ pagerfanta(pager, {'omitFirstPage': true }) }}
```

If you are using a parameter other than `page` for pagination, you can set the parameter name by using the `pageParameter` option when rendering the pager.

```twig
{{ pagerfanta(pager, 'default', {'pageParameter': '[other_page]'}) }}
```

Note that the page parameter *MUST* be wrapped in brackets (i.e. `[other_page]`) for the route generator to correctly function.

See the [Pagerfanta documentation](/open-source/packages/pagerfanta/docs) for the list of supported options.

## Cursor Pagers

<div class="docs-note docs-note--new-feature">Rendering cursor pagers was introduced in PagerfantaBundle 4.7.</div>

Cursor pagers are rendered with the `pagerfanta()` function in the same way, and are linked with signed cursors in the `cursor` parameter. See the [cursor pagination documentation](/open-source/packages/pagerfantabundle/docs/4.x/cursor-pagination) for details.

## Turbo

The links rendered by the `pagerfanta()` function are regular links, so [Symfony UX Turbo](https://symfony.com/bundles/ux-turbo/current/index.html) works with them without any changes. With Turbo Drive enabled, changing pages is handled as a Turbo visit.

To only update the list when changing pages, wrap the list and its pager in a `<turbo-frame>`. The controller does not need any changes, it renders the full page and Turbo replaces the frame with the frame of the same ID from the response. Add the `data-turbo-action="advance"` attribute to the frame to update the URL of the page as well.

```twig
<turbo-frame id="posts" data-turbo-action="advance">
    {% for post in pager %}
        <article>{{ post.title }}</article>
    {% endfor %}

    {{ pagerfanta(pager) }}
</turbo-frame>
```

### Pagers Outside Of The Frame

<div class="docs-note docs-note--new-feature">Adding attributes to the links of the Twig templates requires PagerfantaBundle 4.8 or later.</div>

When the pager is rendered outside of the frame it updates, each link must target the frame with the `data-turbo-frame` attribute. Add the attribute to the links by overriding the `link_attributes` block of your pager's template, see the [Pagerfanta documentation](/open-source/packages/pagerfanta/docs/4.x/views#link-attributes) for more details.

```twig
{# templates/pagination/posts_frame.html.twig #}
{%- extends '@BabDevPagerfanta/twitter_bootstrap5.html.twig' -%}

{%- block link_attributes %} data-turbo-frame="posts"{% endblock link_attributes -%}
```

```twig
<turbo-frame id="posts" data-turbo-action="advance">
    {% for post in pager %}
        <article>{{ post.title }}</article>
    {% endfor %}
</turbo-frame>

{{ pagerfanta(pager, 'twig', {'template': 'pagination/posts_frame.html.twig'}) }}
```

### Turbo Or Live Components

Turbo Frames are the simplest way to change pages without reloading the whole page, as they do not need any changes to your controllers. When the list has state which should be kept in sync with the pager, such as filters which return to the first page when they change, consider rendering it in a [live component](/open-source/packages/pagerfantabundle/docs/4.x/live-components) instead.
