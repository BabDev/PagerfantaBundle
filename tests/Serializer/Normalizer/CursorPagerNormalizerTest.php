<?php declare(strict_types=1);

namespace BabDev\PagerfantaBundle\Tests\Serializer\Normalizer;

use BabDev\PagerfantaBundle\Cursor\SignedCursorEncoder;
use BabDev\PagerfantaBundle\Serializer\Normalizer\CursorPagerNormalizer;
use Pagerfanta\Adapter\ArrayCursorAdapter;
use Pagerfanta\Adapter\CallbackCursorAdapter;
use Pagerfanta\Adapter\CursorSlice;
use Pagerfanta\Adapter\NullAdapter;
use Pagerfanta\CountableCursorPagerfanta;
use Pagerfanta\Cursor\Base64JsonCursorEncoder;
use Pagerfanta\Cursor\Cursor;
use Pagerfanta\Cursor\Direction;
use Pagerfanta\CursorPagerfanta;
use Pagerfanta\CursorPagerInterface;
use Pagerfanta\Pagerfanta;
use Pagerfanta\Position\CursorPosition;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Serializer\Exception\InvalidArgumentException;
use Symfony\Component\Serializer\Exception\LogicException;
use Symfony\Component\Serializer\Serializer;

final class CursorPagerNormalizerTest extends TestCase
{
    private SignedCursorEncoder $cursorEncoder;

    protected function setUp(): void
    {
        $this->cursorEncoder = new SignedCursorEncoder(new Base64JsonCursorEncoder(), 'secret');
    }

    private function createSerializer(): Serializer
    {
        return new Serializer([new CursorPagerNormalizer($this->cursorEncoder)]);
    }

    /**
     * @return ArrayCursorAdapter<array{id: int}>
     */
    private function createAdapter(): ArrayCursorAdapter
    {
        return new ArrayCursorAdapter(array_map(static fn (int $id): array => ['id' => $id], range(1, 7)), static fn (array $item): array => ['id' => $item['id']]);
    }

    public function testACountablePagerIsNormalizedWithTheTotal(): void
    {
        $pager = new CountableCursorPagerfanta($this->createAdapter(), 3, new CursorPosition(new Cursor(['id' => 3])));

        $this->assertSame(
            [
                'items' => [['id' => 4], ['id' => 5], ['id' => 6]],
                'pagination' => [
                    'per_page' => 3,
                    'has_previous_page' => true,
                    'has_next_page' => true,
                    'previous_cursor' => $this->cursorEncoder->encode(new Cursor(['id' => 4], Direction::Previous)),
                    'next_cursor' => $this->cursorEncoder->encode(new Cursor(['id' => 6])),
                    'total_items' => 7,
                ],
            ],
            $this->createSerializer()->normalize($pager),
        );
    }

    public function testAPagerWhichCannotCountIsNormalizedWithoutTheTotal(): void
    {
        $pager = new CursorPagerfanta($this->createAdapter(), 3);

        $this->assertSame(
            [
                'items' => [['id' => 1], ['id' => 2], ['id' => 3]],
                'pagination' => [
                    'per_page' => 3,
                    'has_previous_page' => false,
                    'has_next_page' => true,
                    'previous_cursor' => null,
                    'next_cursor' => $this->cursorEncoder->encode(new Cursor(['id' => 3])),
                ],
            ],
            $this->createSerializer()->normalize($pager),
        );
    }

    public function testTheLastPageHasNoNextCursor(): void
    {
        $this->assertSame(
            [
                'items' => [['id' => 7]],
                'pagination' => [
                    'per_page' => 3,
                    'has_previous_page' => true,
                    'has_next_page' => false,
                    'previous_cursor' => $this->cursorEncoder->encode(new Cursor(['id' => 7], Direction::Previous)),
                    'next_cursor' => null,
                ],
            ],
            $this->createSerializer()->normalize(new CursorPagerfanta($this->createAdapter(), 3, new CursorPosition(new Cursor(['id' => 6])))),
        );
    }

    public function testAForwardOnlyPagerHasNoPreviousCursor(): void
    {
        $adapter = new CallbackCursorAdapter(static fn (?Cursor $cursor, int $limit): CursorSlice => new CursorSlice([4], new Cursor(['id' => 4], Direction::Previous), new Cursor(['id' => 4])));

        $this->assertSame(
            [
                'items' => [4],
                'pagination' => [
                    'per_page' => 1,
                    'has_previous_page' => false,
                    'has_next_page' => true,
                    'previous_cursor' => null,
                    'next_cursor' => $this->cursorEncoder->encode(new Cursor(['id' => 4])),
                ],
            ],
            $this->createSerializer()->normalize(new CursorPagerfanta($adapter, 1, new CursorPosition(new Cursor(['id' => 3])))),
        );
    }

    public function testTheCursorsAreSignedWithTheBundleEncoder(): void
    {
        $normalized = $this->createSerializer()->normalize(new CursorPagerfanta($this->createAdapter(), 3));

        $this->assertIsArray($normalized);
        $this->assertIsArray($normalized['pagination']);

        $nextCursor = $normalized['pagination']['next_cursor'];

        $this->assertIsString($nextCursor);
        $this->assertMatchesRegularExpression('/^[A-Za-z0-9_-]+\.[A-Za-z0-9_-]{43}$/', $nextCursor);
        $this->assertEquals(new Cursor(['id' => 3]), $this->cursorEncoder->decode($nextCursor));
    }

    /**
     * Creates a pager whose adapter returns keyed items, which the cursor adapters are not required to reindex.
     *
     * @param array<array-key, string> $items
     *
     * @return CursorPagerfanta<string>
     */
    private function createPagerWithKeyedItems(array $items): CursorPagerfanta
    {
        // @phpstan-ignore argument.type
        return new CursorPagerfanta(new CallbackCursorAdapter(static fn (?Cursor $cursor, int $limit): CursorSlice => new CursorSlice($items)));
    }

    /**
     * @return \Generator<string, array{array<array-key, string>, array<string, mixed>, array<array-key, string>}>
     */
    public static function dataNormalizeWithPreserveKeysContext(): \Generator
    {
        yield 'Context not set' => [[0 => 'item1', 2 => 'item2', 4 => 'item3'], [], [0 => 'item1', 2 => 'item2', 4 => 'item3']];

        yield 'Context with preserve keys disabled' => [[0 => 'item1', 2 => 'item2', 4 => 'item3'], [CursorPagerNormalizer::PRESERVE_KEYS_KEY => false], ['item1', 'item2', 'item3']];

        yield 'Context with preserve keys enabled' => [[0 => 'item1', 2 => 'item2', 4 => 'item3'], [CursorPagerNormalizer::PRESERVE_KEYS_KEY => true], [0 => 'item1', 2 => 'item2', 4 => 'item3']];

        yield 'Context with preserve keys unset' => [[0 => 'item1', 2 => 'item2', 4 => 'item3'], [CursorPagerNormalizer::PRESERVE_KEYS_KEY => null], [0 => 'item1', 2 => 'item2', 4 => 'item3']];
    }

    /**
     * @param array<array-key, string> $data
     * @param array<string, mixed>     $context
     * @param array<array-key, string> $expectedItems
     *
     * @dataProvider dataNormalizeWithPreserveKeysContext
     */
    #[DataProvider('dataNormalizeWithPreserveKeysContext')]
    public function testNormalizeWithPreserveKeysContext(array $data, array $context, array $expectedItems): void
    {
        $this->assertSame(
            [
                'items' => $expectedItems,
                'pagination' => [
                    'per_page' => 10,
                    'has_previous_page' => false,
                    'has_next_page' => false,
                    'previous_cursor' => null,
                    'next_cursor' => null,
                ],
            ],
            $this->createSerializer()->normalize($this->createPagerWithKeyedItems($data), null, $context),
        );
    }

    public function testNormalizeRejectsInvalidPreserveKeysContext(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('The "pagerfanta_preserve_keys" context key must be a boolean value or null, "string" given.');

        new CursorPagerNormalizer($this->cursorEncoder)->normalize(new CursorPagerfanta($this->createAdapter()), null, [CursorPagerNormalizer::PRESERVE_KEYS_KEY => 'invalid']);
    }

    public function testOnlyCursorPagersAreSupported(): void
    {
        $normalizer = new CursorPagerNormalizer($this->cursorEncoder);

        $this->assertTrue($normalizer->supportsNormalization(new CursorPagerfanta($this->createAdapter())));
        $this->assertFalse($normalizer->supportsNormalization(new Pagerfanta(new NullAdapter(5))));
        $this->assertArrayHasKey(CursorPagerInterface::class, $normalizer->getSupportedTypes(null));
    }

    public function testNormalizeOnlyAcceptsCursorPagers(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(\sprintf('The object must be an instance of "%s".', CursorPagerInterface::class));

        new CursorPagerNormalizer($this->cursorEncoder)->normalize(new \stdClass());
    }
}
