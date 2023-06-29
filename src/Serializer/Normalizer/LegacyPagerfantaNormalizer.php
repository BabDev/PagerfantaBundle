<?php declare(strict_types=1);

namespace BabDev\PagerfantaBundle\Serializer\Normalizer;

use Symfony\Component\Serializer\Normalizer\CacheableSupportsMethodInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

/**
 * Decorator for {@see PagerfantaNormalizer} implementing the legacy {@CacheableSupportsMethodInterface} for older Symfony version support.
 *
 * @internal
 */
final class LegacyPagerfantaNormalizer implements NormalizerInterface, CacheableSupportsMethodInterface, NormalizerAwareInterface
{
    public function __construct(private readonly PagerfantaNormalizer $normalizer)
    {
    }

    public function setNormalizer(NormalizerInterface $normalizer): void
    {
        $this->normalizer->setNormalizer($normalizer);
    }

    public function normalize(mixed $object, string $format = null, array $context = []): array
    {
        return $this->normalizer->normalize($object, $format, $context);
    }

    public function supportsNormalization(mixed $data, string $format = null, array $context = []): bool
    {
        return $this->normalizer->supportsNormalization($data, $format, $context);
    }

    public function hasCacheableSupportsMethod(): bool
    {
        return true;
    }
}
