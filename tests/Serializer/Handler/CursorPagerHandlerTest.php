<?php declare(strict_types=1);

namespace BabDev\PagerfantaBundle\Tests\Serializer\Handler;

use BabDev\PagerfantaBundle\Cursor\SignedCursorEncoder;
use BabDev\PagerfantaBundle\Serializer\Handler\CursorPagerHandler;
use JMS\Serializer\EventDispatcher\EventDispatcher;
use JMS\Serializer\Exception\LogicException;
use JMS\Serializer\Handler\HandlerRegistry;
use JMS\Serializer\SerializationContext;
use JMS\Serializer\SerializerBuilder;
use JMS\Serializer\SerializerInterface;
use Pagerfanta\Adapter\ArrayCursorAdapter;
use Pagerfanta\Adapter\CallbackCursorAdapter;
use Pagerfanta\Adapter\CursorSlice;
use Pagerfanta\CountableCursorPagerfanta;
use Pagerfanta\Cursor\Base64JsonCursorEncoder;
use Pagerfanta\Cursor\Cursor;
use Pagerfanta\Cursor\Direction;
use Pagerfanta\CursorPagerfanta;
use Pagerfanta\Position\CursorPosition;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * @note The {@see CursorPagerHandler::PRESERVE_KEYS_KEY} constant value is inlined to avoid autoloader issues when the JMS packages are not installed
 */
final class CursorPagerHandlerTest extends TestCase
{
    private SignedCursorEncoder $cursorEncoder;

    public static function setUpBeforeClass(): void
    {
        if (!class_exists(SerializerBuilder::class)) {
            self::markTestSkipped('Test requires JMS Serializer');
        }
    }

    protected function setUp(): void
    {
        $this->cursorEncoder = new SignedCursorEncoder(new Base64JsonCursorEncoder(), 'secret');
    }

    /**
     * @return ArrayCursorAdapter<int>
     */
    private function createAdapter(): ArrayCursorAdapter
    {
        return new ArrayCursorAdapter(range(1, 7), static fn (int $item): array => ['id' => $item]);
    }

    public function testACountablePagerIsSerializedWithTheTotal(): void
    {
        $pager = new CountableCursorPagerfanta($this->createAdapter(), 3, new CursorPosition(new Cursor(['id' => 3])));

        $this->assertJsonStringEqualsJsonString(
            json_encode([
                'items' => [4, 5, 6],
                'pagination' => [
                    'per_page' => 3,
                    'has_previous_page' => true,
                    'has_next_page' => true,
                    'previous_cursor' => $this->cursorEncoder->encode(new Cursor(['id' => 4], Direction::Previous)),
                    'next_cursor' => $this->cursorEncoder->encode(new Cursor(['id' => 6])),
                    'total_items' => 7,
                ],
            ], \JSON_THROW_ON_ERROR),
            $this->createSerializer()->serialize($pager, 'json'),
        );
    }

    public function testAPagerWhichCannotCountIsSerializedWithoutTheTotal(): void
    {
        $pager = new CursorPagerfanta($this->createAdapter(), 3, new CursorPosition(new Cursor(['id' => 6])));

        // The JMS Serializer omits null values unless the context enables serializing them
        $this->assertJsonStringEqualsJsonString(
            json_encode([
                'items' => [7],
                'pagination' => [
                    'per_page' => 3,
                    'has_previous_page' => true,
                    'has_next_page' => false,
                    'previous_cursor' => $this->cursorEncoder->encode(new Cursor(['id' => 7], Direction::Previous)),
                ],
            ], \JSON_THROW_ON_ERROR),
            $this->createSerializer()->serialize($pager, 'json'),
        );
    }

    public function testTheMissingCursorsAreSerializedAsNullWhenTheContextSerializesNulls(): void
    {
        $pager = new CursorPagerfanta($this->createAdapter(), 3);

        $this->assertJsonStringEqualsJsonString(
            json_encode([
                'items' => [1, 2, 3],
                'pagination' => [
                    'per_page' => 3,
                    'has_previous_page' => false,
                    'has_next_page' => true,
                    'previous_cursor' => null,
                    'next_cursor' => $this->cursorEncoder->encode(new Cursor(['id' => 3])),
                ],
            ], \JSON_THROW_ON_ERROR),
            $this->createSerializer()->serialize($pager, 'json', SerializationContext::create()->setSerializeNull(true)),
        );
    }

    public function testAForwardOnlyPagerHasNoPreviousCursor(): void
    {
        $adapter = new CallbackCursorAdapter(static fn (?Cursor $cursor, int $limit): CursorSlice => new CursorSlice([4], new Cursor(['id' => 4], Direction::Previous), new Cursor(['id' => 4])));

        $this->assertJsonStringEqualsJsonString(
            json_encode([
                'items' => [4],
                'pagination' => [
                    'per_page' => 1,
                    'has_previous_page' => false,
                    'has_next_page' => true,
                    'previous_cursor' => null,
                    'next_cursor' => $this->cursorEncoder->encode(new Cursor(['id' => 4])),
                ],
            ], \JSON_THROW_ON_ERROR),
            $this->createSerializer()->serialize(new CursorPagerfanta($adapter, 1, new CursorPosition(new Cursor(['id' => 3]))), 'json', SerializationContext::create()->setSerializeNull(true)),
        );
    }

    /**
     * @return \Generator<string, array{array<array-key, string>, array<string, mixed>, string}>
     */
    public static function dataSerializeWithPreserveKeysContext(): \Generator
    {
        yield 'Context not set' => [[0 => 'item1', 2 => 'item2', 4 => 'item3'], [], '{"items":{"0":"item1","2":"item2","4":"item3"},"pagination":{"per_page":10,"has_previous_page":false,"has_next_page":false}}'];

        yield 'Context with preserve keys disabled' => [[0 => 'item1', 2 => 'item2', 4 => 'item3'], ['pagerfanta_preserve_keys' => false], '{"items":["item1","item2","item3"],"pagination":{"per_page":10,"has_previous_page":false,"has_next_page":false}}'];

        yield 'Context with preserve keys enabled' => [[0 => 'item1', 2 => 'item2', 4 => 'item3'], ['pagerfanta_preserve_keys' => true], '{"items":{"0":"item1","2":"item2","4":"item3"},"pagination":{"per_page":10,"has_previous_page":false,"has_next_page":false}}'];
    }

    /**
     * @param array<array-key, string> $data
     * @param array<string, mixed>     $context
     *
     * @dataProvider dataSerializeWithPreserveKeysContext
     */
    #[DataProvider('dataSerializeWithPreserveKeysContext')]
    public function testSerializeToJsonWithPreserveKeysContext(array $data, array $context, string $expectedJson): void
    {
        // @phpstan-ignore argument.type
        $pager = new CursorPagerfanta(new CallbackCursorAdapter(static fn (?Cursor $cursor, int $limit): CursorSlice => new CursorSlice($data)));

        $serializationContext = new SerializationContext();

        foreach ($context as $key => $value) {
            $serializationContext->setAttribute($key, $value);
        }

        $this->assertJsonStringEqualsJsonString(
            $expectedJson,
            $this->createSerializer()->serialize($pager, 'json', $serializationContext),
        );
    }

    public function testSerializeRejectsInvalidPreserveKeysContext(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('The "pagerfanta_preserve_keys" context key must be a boolean value or null, "string" given.');

        $serializationContext = new SerializationContext();
        $serializationContext->setAttribute('pagerfanta_preserve_keys', 'invalid');

        $this->createSerializer()->serialize(new CursorPagerfanta($this->createAdapter()), 'json', $serializationContext);
    }

    private function createSerializer(): SerializerInterface
    {
        $registry = new HandlerRegistry();
        $registry->registerSubscribingHandler(new CursorPagerHandler($this->cursorEncoder));

        return SerializerBuilder::create($registry, new EventDispatcher())->build();
    }
}
