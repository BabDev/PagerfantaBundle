<?php declare(strict_types=1);

namespace BabDev\PagerfantaBundle\Serializer\Handler;

use JMS\Serializer\Exception\LogicException;
use JMS\Serializer\GraphNavigatorInterface;
use JMS\Serializer\Handler\SubscribingHandlerInterface;
use JMS\Serializer\JsonSerializationVisitor;
use JMS\Serializer\SerializationContext;
use Pagerfanta\CountableCursorPagerfanta;
use Pagerfanta\CountablePagerInterface;
use Pagerfanta\Cursor\CursorEncoderInterface;
use Pagerfanta\CursorPagerfanta;
use Pagerfanta\CursorPagerInterface;

/**
 * Serializes cursor pagers, including the encoded cursors for the previous and next pages.
 *
 * The total number of items is only included for pagers which can count their results.
 */
final readonly class CursorPagerHandler implements SubscribingHandlerInterface
{
    public const string PRESERVE_KEYS_KEY = 'pagerfanta_preserve_keys';

    public function __construct(
        private CursorEncoderInterface $cursorEncoder,
    ) {}

    public static function getSubscribingMethods(): array
    {
        $methods = [];

        foreach ([CursorPagerfanta::class, CountableCursorPagerfanta::class, CursorPagerInterface::class] as $type) {
            $methods[] = [
                'direction' => GraphNavigatorInterface::DIRECTION_SERIALIZATION,
                'format' => 'json',
                'type' => $type,
                'method' => 'serializeToJson',
            ];
        }

        return $methods;
    }

    /**
     * @param CursorPagerInterface<mixed>               $pager
     * @param array{name: string, params: array<mixed>} $type
     *
     * @return array<mixed>|\ArrayObject<array-key, mixed>|null
     *
     * @throws LogicException when the handler is not called in an expected context
     */
    public function serializeToJson(JsonSerializationVisitor $visitor, CursorPagerInterface $pager, array $type, SerializationContext $context)
    {
        $items = $pager->getCurrentPageResults();

        if ($context->hasAttribute(self::PRESERVE_KEYS_KEY)) {
            $preserveKeys = $context->getAttribute(self::PRESERVE_KEYS_KEY);

            if (!\is_bool($preserveKeys) && null !== $preserveKeys) {
                throw new LogicException(\sprintf('The "%s" context key must be a boolean value or null, "%s" given.', self::PRESERVE_KEYS_KEY, get_debug_type($preserveKeys)));
            }

            if (null !== $preserveKeys) {
                $items = iterator_to_array($items, $preserveKeys);
            }
        }

        $pagination = [
            'per_page' => $pager->getMaxPerPage(),
            'has_previous_page' => $pager->hasPreviousPage(),
            'has_next_page' => $pager->hasNextPage(),
            'previous_cursor' => $pager->hasPreviousPage() ? $this->cursorEncoder->encode($pager->getPreviousPosition()->cursor) : null,
            'next_cursor' => $pager->hasNextPage() ? $this->cursorEncoder->encode($pager->getNextPosition()->cursor) : null,
        ];

        if ($pager instanceof CountablePagerInterface) {
            $pagination['total_items'] = $pager->getNbResults();
        }

        return $visitor->visitArray(
            [
                'items' => $items,
                'pagination' => $pagination,
            ],
            $type,
        );
    }
}
