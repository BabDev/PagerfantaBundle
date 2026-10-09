<?php declare(strict_types=1);

namespace BabDev\PagerfantaBundle\Serializer\Normalizer;

use Pagerfanta\CountableCursorPagerfanta;
use Pagerfanta\CountablePagerInterface;
use Pagerfanta\Cursor\CursorEncoderInterface;
use Pagerfanta\CursorPagerfanta;
use Pagerfanta\CursorPagerInterface;
use Symfony\Component\Serializer\Exception\InvalidArgumentException;
use Symfony\Component\Serializer\Exception\LogicException;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareTrait;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

/**
 * Normalizes cursor pagers, including the encoded cursors for the previous and next pages.
 *
 * The total number of items is only included for pagers which can count their results.
 */
final class CursorPagerNormalizer implements NormalizerInterface, NormalizerAwareInterface
{
    use NormalizerAwareTrait;

    public const string PRESERVE_KEYS_KEY = 'pagerfanta_preserve_keys';

    public function __construct(
        private readonly CursorEncoderInterface $cursorEncoder,
    ) {}

    /**
     * @throws InvalidArgumentException when the object given is not a supported type for the normalizer
     * @throws LogicException           when the normalizer is not called in an expected context
     */
    public function normalize(mixed $data, ?string $format = null, array $context = []): array
    {
        if (!$data instanceof CursorPagerInterface) {
            throw new InvalidArgumentException(\sprintf('The object must be an instance of "%s".', CursorPagerInterface::class));
        }

        $items = $data->getCurrentPageResults();

        if (\array_key_exists(self::PRESERVE_KEYS_KEY, $context)) {
            $preserveKeys = $context[self::PRESERVE_KEYS_KEY];

            if (!\is_bool($preserveKeys) && null !== $preserveKeys) {
                throw new LogicException(\sprintf('The "%s" context key must be a boolean value or null, "%s" given.', self::PRESERVE_KEYS_KEY, get_debug_type($preserveKeys)));
            }

            if (null !== $preserveKeys) {
                $items = iterator_to_array($items, $preserveKeys);
            }
        }

        $pagination = [
            'per_page' => $data->getMaxPerPage(),
            'has_previous_page' => $data->hasPreviousPage(),
            'has_next_page' => $data->hasNextPage(),
            'previous_cursor' => $data->hasPreviousPage() ? $this->cursorEncoder->encode($data->getPreviousPosition()->cursor) : null,
            'next_cursor' => $data->hasNextPage() ? $this->cursorEncoder->encode($data->getNextPosition()->cursor) : null,
        ];

        if ($data instanceof CountablePagerInterface) {
            $pagination['total_items'] = $data->getNbResults();
        }

        return [
            'items' => $this->normalizer->normalize($items, $format, $context),
            'pagination' => $pagination,
        ];
    }

    public function supportsNormalization(mixed $data, ?string $format = null, array $context = []): bool
    {
        return $data instanceof CursorPagerInterface;
    }

    /**
     * @return array<class-string, true>
     */
    public function getSupportedTypes(?string $format): array
    {
        return [
            CursorPagerInterface::class => true,
            CursorPagerfanta::class => true,
            CountableCursorPagerfanta::class => true,
        ];
    }
}
