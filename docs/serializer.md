# Serializer

The PagerfantaBundle provides support for serializing `Pagerfanta/Pagerfanta` instances using either the [Symfony Serializer component](https://symfony.com/doc/current/components/serializer.html) or the [JMS Serializer](https://jmsyst.com/libs/serializer) (note, the `JMSSerializerBundle` must be installed to enable the serialization handler for the JMS serializer).

Below is an example of building a JSON response in a controller using the Symfony Serializer:

```php
<?php

namespace App\Controller\API;

use App\Entity\BlogPostRepository;
use Pagerfanta\Doctrine\ORM\QueryAdapter;
use Pagerfanta\Pagerfanta;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

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

## Serialization Context Configuration

### Preserving Array Keys

Both serialization integrations support configuring the way array keys are preserved using the `pagerfanta_preserve_keys` context attribute. By default, or when the attribute is explicitly set to null, the payload will be serialized exactly as provided by the pagination adapter. However, when the attribute is set to a boolean value, the value will be used as the second argument when calling the native `iterator_to_array()` function.
