<?php declare(strict_types=1);

namespace BabDev\PagerfantaBundle\Tests\Serializer\Handler;

use BabDev\PagerfantaBundle\Serializer\Handler\PagerfantaHandler;
use JMS\Serializer\EventDispatcher\EventDispatcher;
use JMS\Serializer\Exception\LogicException;
use JMS\Serializer\Handler\HandlerRegistry;
use JMS\Serializer\SerializationContext;
use JMS\Serializer\SerializerBuilder;
use JMS\Serializer\SerializerInterface;
use Pagerfanta\Adapter\FixedAdapter;
use Pagerfanta\Pagerfanta;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class PagerfantaHandlerTest extends TestCase
{
    public static function setUpBeforeClass(): void
    {
        if (!class_exists(SerializerBuilder::class)) {
            self::markTestSkipped('Test requires JMS Serializer');
        }
    }

    public function testSerializeToJson(): void
    {
        $pager = new Pagerfanta(new FixedAdapter(100, range(1, 5)));
        $pager->setMaxPerPage(5);

        self::assertJsonStringEqualsJsonString(
            '{"items":[1,2,3,4,5],"pagination":{"current_page":1,"has_previous_page":false,"has_next_page":true,"per_page":5,"total_items":100,"total_pages":20}}',
            $this->createSerializer()->serialize($pager, 'json'),
        );
    }

    /**
     * @return \Generator<string, array{array<array-key, string>, array<string, mixed>, string}>
     */
    public static function dataSerializeWithPreserveKeysContext(): \Generator
    {
        yield 'Context not set' => [[0 => 'item1', 2 => 'item2', 4 => 'item3'], [], '{"items":{"0":"item1","2":"item2","4":"item3"},"pagination":{"current_page":1,"has_previous_page":false,"has_next_page":false,"per_page":10,"total_items":3,"total_pages":1}}'];

        yield 'Context with preserve keys disabled' => [[0 => 'item1', 2 => 'item2', 4 => 'item3'], [PagerfantaHandler::PRESERVE_KEYS_KEY => false], '{"items":["item1","item2","item3"],"pagination":{"current_page":1,"has_previous_page":false,"has_next_page":false,"per_page":10,"total_items":3,"total_pages":1}}'];

        yield 'Context with preserve keys enabled' => [[0 => 'item1', 2 => 'item2', 4 => 'item3'], [PagerfantaHandler::PRESERVE_KEYS_KEY => true], '{"items":{"0":"item1","2":"item2","4":"item3"},"pagination":{"current_page":1,"has_previous_page":false,"has_next_page":false,"per_page":10,"total_items":3,"total_pages":1}}'];
    }

    /**
     * @dataProvider dataSerializeWithPreserveKeysContext
     */
    #[DataProvider('dataSerializeWithPreserveKeysContext')]
    public function testSerializeToJsonWithPreserveKeysContext(array $data, array $context, string $expectedJson): void
    {
        $pager = new Pagerfanta(new FixedAdapter(\count($data), $data));

        $serializationContext = new SerializationContext();

        foreach ($context as $key => $value) {
            $serializationContext->setAttribute($key, $value);
        }

        self::assertJsonStringEqualsJsonString(
            $expectedJson,
            $this->createSerializer()->serialize($pager, 'json', $serializationContext),
        );
    }

    public function testNormalizeRejectsInvalidPreserveKeysContext(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('The "pagerfanta_preserve_keys" context key must be a boolean value or null, "string" given.');

        $pager = new Pagerfanta(new FixedAdapter(100, range(1, 5)));
        $pager->setMaxPerPage(5);

        $serializationContext = new SerializationContext();
        $serializationContext->setAttribute(PagerfantaHandler::PRESERVE_KEYS_KEY, 'invalid');

        $this->createSerializer()->serialize($pager, 'json', $serializationContext);
    }

    private function createSerializer(): SerializerInterface
    {
        $registry = new HandlerRegistry();
        $registry->registerSubscribingHandler(new PagerfantaHandler());

        return SerializerBuilder::create($registry, new EventDispatcher())->build();
    }
}
