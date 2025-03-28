<?php declare(strict_types=1);

namespace BabDev\PagerfantaBundle\Tests\Serializer\Normalizer;

use BabDev\PagerfantaBundle\Serializer\Normalizer\PagerfantaNormalizer;
use Pagerfanta\Adapter\FixedAdapter;
use Pagerfanta\Adapter\NullAdapter;
use Pagerfanta\Pagerfanta;
use Pagerfanta\PagerfantaInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Serializer\Exception\InvalidArgumentException;
use Symfony\Component\Serializer\Exception\LogicException;
use Symfony\Component\Serializer\Serializer;

final class PagerfantaNormalizerTest extends TestCase
{
    public function testNormalize(): void
    {
        $pager = new Pagerfanta(new NullAdapter(25));

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
     * @return \Generator<string, array{array<array-key, string>, array<string, mixed>, array<array-key, string>}>
     */
    public static function dataNormalizeWithPreserveKeysContext(): \Generator
    {
        yield 'Context not set' => [[0 => 'item1', 2 => 'item2', 4 => 'item3'], [], [0 => 'item1', 2 => 'item2', 4 => 'item3']];

        yield 'Context with preserve keys disabled' => [[0 => 'item1', 2 => 'item2', 4 => 'item3'], [PagerfantaNormalizer::PRESERVE_KEYS_KEY => false], ['item1', 'item2', 'item3']];

        yield 'Context with preserve keys enabled' => [[0 => 'item1', 2 => 'item2', 4 => 'item3'], [PagerfantaNormalizer::PRESERVE_KEYS_KEY => true], [0 => 'item1', 2 => 'item2', 4 => 'item3']];
    }

    /**
     * @dataProvider dataNormalizeWithPreserveKeysContext
     */
    public function testNormalizeWithPreserveKeysContext(array $data, array $context, array $expectedItems): void
    {
        $pager = new Pagerfanta(new FixedAdapter(\count($data), $data));

        $expectedResultArray = [
            'items' => $expectedItems,
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

        self::assertEquals($expectedResultArray, $serializer->normalize($pager, null, $context));
    }

    public function testNormalizeRejectsInvalidPreserveKeysContext(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('The "pagerfanta_preserve_keys" context key must be a boolean value or null, "string" given.');

        (new PagerfantaNormalizer())->normalize(new Pagerfanta(new NullAdapter(25)), null, [PagerfantaNormalizer::PRESERVE_KEYS_KEY => 'invalid']);
    }

    public function testNormalizeOnlyAcceptsPagerfantaInstances(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(\sprintf('The object must be an instance of "%s".', PagerfantaInterface::class));

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
