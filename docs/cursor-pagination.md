# Cursor Pagination

The bundle integrates the [cursor pagination](/open-source/packages/pagerfanta/docs/5.x/cursor-pagination) support from Pagerfanta with your Symfony application:

- Cursors in URLs are signed, so they cannot be tampered with
- The position of the current page is resolved from the request with the `BabDev\PagerfantaBundle\Position\PositionResolver`
- Invalid cursors are converted into 400 responses
- Cursor pagers are rendered with the [sequential views](/open-source/packages/pagerfantabundle/docs/5.x/views#sequential-views) and serialized with their cursors

## Resolving The Current Page

The `BabDev\PagerfantaBundle\Position\PositionResolver` service reads the position of the current page from the request. It has a method for each kind of pager, which ignores the parameters for the other kind:

- `resolveCursorPosition()` returns the `Pagerfanta\Position\CursorPosition` from the `cursor` parameter, or null for the first page
- `resolvePagePosition()` returns the `Pagerfanta\Position\PagePosition` from the `page` parameter, or null for the first page
- `resolve()` returns either position, preferring the cursor when both parameters are given

Below is an example of paginating blog posts with a cursor pager.

```php
<?php

namespace App\Controller;

use App\Entity\BlogPostRepository;
use BabDev\PagerfantaBundle\Position\PositionResolver;
use Pagerfanta\CursorPagerfantaFactory;
use Pagerfanta\Doctrine\ORM\CursorQueryAdapter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class BlogController extends AbstractController
{
    #[Route(path: '/blog', name: 'app_blog_list', methods: ['GET'])]
    public function listPosts(Request $request, BlogPostRepository $blogPostRepository, PositionResolver $positionResolver): Response
    {
        $queryBuilder = $blogPostRepository->createQueryBuilder('p')
            ->orderBy('p.publishedAt', 'DESC')
            ->addOrderBy('p.id', 'DESC');

        $pager = CursorPagerfantaFactory::create(
            new CursorQueryAdapter($queryBuilder),
            10,
            $positionResolver->resolveCursorPosition($request),
        );

        return $this->render(
            'blog/list.html.twig',
            [
                'pager' => $pager,
            ],
        );
    }
}
```

The parameters are read from the query string and the route parameters of the request. If your application uses different parameters, set the `cursorParameter` and `pageParameter` options, which use the same format as the [route generator options](/open-source/packages/pagerfantabundle/docs/5.x/generating-paginated-routes#route-generator-options).

```php
$position = $positionResolver->resolveCursorPosition($request, ['cursorParameter' => '[after]']);
```

When the page parameter is not a positive integer, a `Pagerfanta\Exception\NotValidCurrentPageException` is thrown, which is handled by the `not_valid_current_page` [exception strategy](/open-source/packages/pagerfantabundle/docs/5.x/configuring-the-bundle#exception-strategies).

## Signed Cursors

The bundle encodes cursors with the `BabDev\PagerfantaBundle\Cursor\SignedCursorEncoder`, which appends a HMAC-SHA256 signature using the `kernel.secret` parameter to the cursors encoded by the `Pagerfanta\Cursor\Base64JsonCursorEncoder`. The signature is verified before a cursor is decoded, so a cursor which has been altered (or was not created by your application) is rejected with a `Pagerfanta\Exception\InvalidCursorException` and never reaches your query.

The encoder is available as the `pagerfanta.cursor_encoder` service, and can be autowired with the `Pagerfanta\Cursor\CursorEncoderInterface`. It is used by the position resolver, the route generators, and the serializers.

<div class="docs-note">Changing the <code>kernel.secret</code> parameter invalidates all previously generated cursors, clients using a cursor from before the change will receive a 400 response.</div>

To use another encoder, such as one which encrypts the cursors, point the `pagerfanta.cursor_encoder` alias to your service.

```yaml
# config/services.yaml
services:
    pagerfanta.cursor_encoder:
        alias: App\Pagination\EncryptedCursorEncoder
```

## Invalid Cursors

By default, the bundle converts a `Pagerfanta\Exception\InvalidCursorException` into a 400 response. This exception is thrown for cursors which cannot be decoded, fail signature verification, or do not match the sort fields of the adapter. See the [exception strategies](/open-source/packages/pagerfantabundle/docs/5.x/configuring-the-bundle#exception-strategies) to change this behavior.

## Rendering Cursor Pagers

Cursor pagers are rendered with the `pagerfanta()` Twig function, the same as offset pagers. As cursor pagers can only link to the previous and next pages, they are rendered with a [sequential view](/open-source/packages/pagerfantabundle/docs/5.x/views#sequential-views).

```twig
{{ pagerfanta(pager) }}
```

The URLs are generated for the current route, setting the signed cursor to the `cursor` parameter and removing the `page` parameter. The `pagerfanta_position_url()` Twig function generates the URL for a single position, such as a "Load more" link.

```twig
{% if pager.hasNextPage() %}
    <a href="{{ pagerfanta_position_url(pager.nextPosition) }}">Load more</a>
{% endif %}
```

## APIs

Cursor pagers are [serialized](/open-source/packages/pagerfantabundle/docs/5.x/serializer#cursor-pagers) with the signed cursors for the previous and next pages, which clients pass back in the `cursor` parameter to request those pages.
