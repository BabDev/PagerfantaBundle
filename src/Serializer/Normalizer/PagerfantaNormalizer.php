<?php declare(strict_types=1);

namespace BabDev\PagerfantaBundle\Serializer\Normalizer;

use Pagerfanta\Pagerfanta;
use Pagerfanta\PagerfantaInterface;
use Symfony\Component\Serializer\Exception\InvalidArgumentException;
use Symfony\Component\Serializer\Exception\LogicException;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareTrait;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

final class PagerfantaNormalizer implements NormalizerInterface, NormalizerAwareInterface
{
    use NormalizerAwareTrait;

    public const PRESERVE_KEYS_KEY = 'pagerfanta_preserve_keys';

    /**
     * @throws InvalidArgumentException when the object given is not a supported type for the normalizer
     * @throws LogicException           when the normalizer is not called in an expected context
     */
    public function normalize(mixed $object, ?string $format = null, array $context = []): array
    {
        if (!$object instanceof PagerfantaInterface) {
            throw new InvalidArgumentException(\sprintf('The object must be an instance of "%s".', PagerfantaInterface::class));
        }

        $items = $object->getIterator();

        if (\array_key_exists(self::PRESERVE_KEYS_KEY, $context)) {
            $preserveKeys = $context[self::PRESERVE_KEYS_KEY];

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

        return [
            'items' => $this->normalizer->normalize($items, $format, $context),
            'pagination' => [
                'current_page' => $object->getCurrentPage(),
                'has_previous_page' => $object->hasPreviousPage(),
                'has_next_page' => $object->hasNextPage(),
                'per_page' => $object->getMaxPerPage(),
                'total_items' => $object->getNbResults(),
                'total_pages' => $object->getNbPages(),
            ],
        ];
    }

    public function supportsNormalization(mixed $data, ?string $format = null, array $context = []): bool
    {
        return $data instanceof PagerfantaInterface;
    }

    /**
     * @return array<class-string, true>
     */
    public function getSupportedTypes(?string $format): array
    {
        return [
            PagerfantaInterface::class => true,
            Pagerfanta::class => true,
        ];
    }
}
