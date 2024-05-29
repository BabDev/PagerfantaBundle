<?php declare(strict_types=1);

namespace BabDev\PagerfantaBundle\Tests\Serializer\Normalizer;

use BabDev\PagerfantaBundle\Serializer\Normalizer\LegacyPagerfantaNormalizer;
use BabDev\PagerfantaBundle\Serializer\Normalizer\PagerfantaNormalizer;
use Pagerfanta\Adapter\FixedAdapter;
use Pagerfanta\Adapter\NullAdapter;
use Pagerfanta\Pagerfanta;
use Pagerfanta\PagerfantaInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Serializer\Exception\InvalidArgumentException;
use Symfony\Component\Serializer\Normalizer\CacheableSupportsMethodInterface;
use Symfony\Component\Serializer\Serializer;

final class PagerfantaNormalizerTest extends TestCase
{
    public function testNormalize(): void
    {
        $pager = new Pagerfanta(
            new NullAdapter(25),
        );

        $expectedResultArray = [
            'items' => $pager->getCurrentPageResults(),
            'pagination' => [
                'current_page' => $pager->getCurrentPage(),
                'has_previous_page' => $pager->hasPreviousPage(),
                'has_next_page' => $pager->hasNextPage(),
                'per_page' => $pager->getMaxPerPage(),
                'total_items' => $pager->getNbResults(),
                'total_pages' => $pager->getNbPages(),
            ],
        ];

        $serializer = new Serializer([new PagerfantaNormalizer()]);

        self::assertEquals($expectedResultArray, $serializer->normalize($pager));
    }

    /**
     * @group legacy
     */
    public function testNormalizeWithLegacyDecorator(): void
    {
        if (!interface_exists(CacheableSupportsMethodInterface::class)) {
            self::markTestSkipped('Test requires symfony/serializer:<=6.4');
        }

        $pager = new Pagerfanta(
            new NullAdapter(25),
        );

        $expectedResultArray = [
            'items' => $pager->getCurrentPageResults(),
            'pagination' => [
                'current_page' => $pager->getCurrentPage(),
                'has_previous_page' => $pager->hasPreviousPage(),
                'has_next_page' => $pager->hasNextPage(),
                'per_page' => $pager->getMaxPerPage(),
                'total_items' => $pager->getNbResults(),
                'total_pages' => $pager->getNbPages(),
            ],
        ];

        $serializer = new Serializer([new LegacyPagerfantaNormalizer(new PagerfantaNormalizer())]);

        self::assertEquals($expectedResultArray, $serializer->normalize($pager));
    }

    public function testNormalizeOnlyAcceptsPagerfantaInstances(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(sprintf('The object must be an instance of "%s".', PagerfantaInterface::class));

        (new PagerfantaNormalizer())->normalize(new \stdClass());
    }

    public function dataSupportsNormalization(): \Generator
    {
        yield 'Supported' => [new Pagerfanta(new NullAdapter(25)), true];
        yield 'Not Supported' => [new \stdClass(), false];
    }

    /**
     * @dataProvider dataSupportsNormalization
     */
    public function testSupportsNormalization(mixed $data, bool $supported): void
    {
        self::assertSame($supported, (new PagerfantaNormalizer())->supportsNormalization($data));
    }

    /**
     * @group legacy
     */
    public function testHasCacheableSupportsMethod(): void
    {
        if (!interface_exists(CacheableSupportsMethodInterface::class)) {
            self::markTestSkipped('Test requires symfony/serializer:<=6.4');
        }

        self::assertTrue((new LegacyPagerfantaNormalizer(new PagerfantaNormalizer()))->hasCacheableSupportsMethod());
    }

    public function testItSerializesIterableData(): void
    {
        $serializer = new Serializer([new PagerfantaNormalizer()]);
        $items = ['1', '2', '3', '4', '5'];
        $pager = new Pagerfanta(new FixedAdapter(5, new \ArrayIterator($items)));

        $expectedResultArray = [
            'items' => $items,
            'pagination' => [
                'current_page' => $pager->getCurrentPage(),
                'has_previous_page' => $pager->hasPreviousPage(),
                'has_next_page' => $pager->hasNextPage(),
                'per_page' => $pager->getMaxPerPage(),
                'total_items' => $pager->getNbResults(),
                'total_pages' => $pager->getNbPages(),
            ],
        ];

        self::assertSame($expectedResultArray, $serializer->normalize($pager));
    }
}
