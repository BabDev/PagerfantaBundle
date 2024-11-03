<?php declare(strict_types=1);

namespace BabDev\PagerfantaBundle\Serializer\Handler;

use JMS\Serializer\Exception\LogicException;
use JMS\Serializer\GraphNavigatorInterface;
use JMS\Serializer\Handler\SubscribingHandlerInterface;
use JMS\Serializer\JsonSerializationVisitor;
use JMS\Serializer\SerializationContext;
use Pagerfanta\Pagerfanta;
use Pagerfanta\PagerfantaInterface;

final class PagerfantaHandler implements SubscribingHandlerInterface
{
    public const PRESERVE_KEYS_KEY = 'pagerfanta_preserve_keys';

    public static function getSubscribingMethods(): array
    {
        return [
            [
                'direction' => GraphNavigatorInterface::DIRECTION_SERIALIZATION,
                'format' => 'json',
                'type' => Pagerfanta::class,
                'method' => 'serializeToJson',
            ],
            [
                'direction' => GraphNavigatorInterface::DIRECTION_SERIALIZATION,
                'format' => 'json',
                'type' => PagerfantaInterface::class,
                'method' => 'serializeToJson',
            ],
        ];
    }

    /**
     * @param PagerfantaInterface<mixed> $pagerfanta
     *
     * @return array<string, mixed>|\ArrayObject<string, mixed>
     */
    public function serializeToJson(JsonSerializationVisitor $visitor, PagerfantaInterface $pagerfanta, array $type, SerializationContext $context)
    {
        $items = $pagerfanta->getCurrentPageResults();

        if ($context->hasAttribute(self::PRESERVE_KEYS_KEY)) {
            $preserveKeys = $context->getAttribute(self::PRESERVE_KEYS_KEY);

            if (!\is_bool($preserveKeys) && null !== $preserveKeys) {
                throw new LogicException(\sprintf('The "%s" context key must be a boolean value or null, "%s" given.', self::PRESERVE_KEYS_KEY, get_debug_type($preserveKeys)));
            }

            if (null !== $preserveKeys) {
                // When requiring PHP 8.2, this `is_array()` check can be removed
                if (\is_array($items)) {
                    $items = new \ArrayIterator($items);
                }

                $items = iterator_to_array($items, $preserveKeys);
            }
        }

        return $visitor->visitArray(
            [
                'items' => $items,
                'pagination' => [
                    'current_page' => $pagerfanta->getCurrentPage(),
                    'has_previous_page' => $pagerfanta->hasPreviousPage(),
                    'has_next_page' => $pagerfanta->hasNextPage(),
                    'per_page' => $pagerfanta->getMaxPerPage(),
                    'total_items' => $pagerfanta->getNbResults(),
                    'total_pages' => $pagerfanta->getNbPages(),
                ],
            ],
            $type,
        );
    }
}
