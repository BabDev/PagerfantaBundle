# Serializer

The PagerfantaBundle provides support for serializing offset pagers (any `Pagerfanta\OffsetPagerInterface`, such as a `Pagerfanta\Pagerfanta` instance) and [cursor pagers](#cursor-pagers) using either the [Symfony Serializer component](https://symfony.com/doc/current/components/serializer.html) or the [JMS Serializer](https://jmsyst.com/libs/serializer) (note, the `JMSSerializerBundle` must be installed to enable the serialization handler for the JMS serializer).

Below is an example of building a JSON response in a controller using the Symfony Serializer:

```php
<?php

namespace App\Controller\API;

use App\Entity\BlogPostRepository;
use Pagerfanta\Doctrine\ORM\QueryAdapter;
use Pagerfanta\Pagerfanta;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

final class PostController extends AbstractController
{
    #[Route(path: '/api/posts', name: 'app_api_post_list', methods: ['GET'])]
    public function apiPostList(BlogPostRepository $blogPostRepository): JsonResponse
    {
        $queryBuilder = $blogPostRepository->createBlogListQueryBuilder();

        $pagerfanta = new Pagerfanta(new QueryAdapter($queryBuilder));

        return $this->json($pagerfanta);
    }
}
```

Below is an example of how a `Pagerfanta\Pagerfanta` instance is serialized into JSON format (note the `items` array is a simplified example, it will be an array of items based on your serializer configuration):

```json
{
    "items": [
        {
            "id": 1
        },
        {
            "id": 2
        },
        {
            "id": 3
        }
    ],
    "pagination": {
        "current_page": 1,
        "has_previous_page": false,
        "has_next_page": true,
        "per_page": 10,
        "total_items": 35,
        "total_pages": 4
    }
}
```

## Cursor Pagers

[Cursor pagers](/open-source/packages/pagerfantabundle/docs/5.x/cursor-pagination) are serialized with the [signed cursors](/open-source/packages/pagerfantabundle/docs/5.x/cursor-pagination#signed-cursors) for the previous and next pages, which are null when there is no page in that direction. Clients pass these cursors back in the `cursor` parameter to request the other pages.

The total number of items is only included for pagers which can count their results (implementing `Pagerfanta\CountablePagerInterface`), as counting the results is often expensive and cursor pagers do not need the total.

```json
{
    "items": [
        {
            "id": 4
        },
        {
            "id": 5
        },
        {
            "id": 6
        }
    ],
    "pagination": {
        "per_page": 3,
        "has_previous_page": true,
        "has_next_page": true,
        "previous_cursor": "eyJmIjp7ImlkIjo0fSwiZCI6InAifQ.3TwY0stlypgZh7JF3MaG4-U73b75wQ5vAjc7czLn25M",
        "next_cursor": "eyJmIjp7ImlkIjo2fSwiZCI6Im4ifQ.FQMxPOBSKzLVFP20bHe62gJloQtIqkt4pQdnUc0Xk1s",
        "total_items": 35
    }
}
```

<div class="docs-note">The JMS Serializer omits null values unless the serialization context enables serializing them, so the <code>previous_cursor</code> and <code>next_cursor</code> keys are omitted when there is no page in that direction unless <code>SerializationContext::setSerializeNull(true)</code> is used.</div>

## Serialization Context Configuration

### Preserving Array Keys

Both serialization integrations support configuring the way array keys are preserved using the `pagerfanta_preserve_keys` context attribute. By default, or when the attribute is explicitly set to null, the payload will be serialized exactly as provided by the pagination adapter. However, when the attribute is set to a boolean value, the value will be used as the second argument when calling the native `iterator_to_array()` function.
