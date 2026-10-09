<?php declare(strict_types=1);

namespace BabDev\PagerfantaBundle\Tests\Cursor;

use BabDev\PagerfantaBundle\Cursor\SignedCursorEncoder;
use Pagerfanta\Cursor\Base64JsonCursorEncoder;
use Pagerfanta\Cursor\Cursor;
use Pagerfanta\Cursor\CursorEncoderInterface;
use Pagerfanta\Cursor\Direction;
use Pagerfanta\Exception\InvalidArgumentException;
use Pagerfanta\Exception\InvalidCursorException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class SignedCursorEncoderTest extends TestCase
{
    private function createEncoder(string $secret = 'secret'): SignedCursorEncoder
    {
        return new SignedCursorEncoder(new Base64JsonCursorEncoder(), $secret);
    }

    public function testACursorSurvivesARoundTrip(): void
    {
        $cursor = new Cursor(['p.createdAt' => '2026-09-28 12:00:00', 'p.id' => 42], Direction::Previous);

        $encoder = $this->createEncoder();

        $this->assertEquals($cursor, $encoder->decode($encoder->encode($cursor)));
    }

    public function testTheEncodedCursorIsTheDecoratedEncodingWithASignature(): void
    {
        $cursor = new Cursor(['p.id' => 42]);

        $encoded = $this->createEncoder()->encode($cursor);

        $this->assertStringStartsWith(new Base64JsonCursorEncoder()->encode($cursor).'.', $encoded);
        $this->assertMatchesRegularExpression('/^[A-Za-z0-9_-]+\.[A-Za-z0-9_-]{43}$/', $encoded, 'The encoded cursor is URL-safe');
    }

    public function testThePayloadMayContainTheSeparator(): void
    {
        $decorated = new class implements CursorEncoderInterface {
            public function encode(Cursor $cursor): string
            {
                return 'with.separators.'.$cursor->fields['id'];
            }

            public function decode(string $encoded): Cursor
            {
                return new Cursor(['id' => (int) substr($encoded, \strlen('with.separators.'))]);
            }
        };

        $encoder = new SignedCursorEncoder($decorated, 'secret');

        $this->assertEquals(new Cursor(['id' => 42]), $encoder->decode($encoder->encode(new Cursor(['id' => 42]))));
    }

    public static function dataTamperedCursors(): \Generator
    {
        $encoder = new SignedCursorEncoder(new Base64JsonCursorEncoder(), 'secret');
        $encoded = $encoder->encode(new Cursor(['p.id' => 42]));
        [$payload, $signature] = explode('.', $encoded);

        $tamperedPayload = new Base64JsonCursorEncoder()->encode(new Cursor(['p.id' => 43]));

        yield 'unsigned cursor' => [$payload];
        yield 'empty signature' => [$payload.'.'];
        yield 'altered payload' => [$tamperedPayload.'.'.$signature];
        yield 'altered signature' => [$payload.'.'.strrev($signature)];
        yield 'truncated signature' => [substr($encoded, 0, -1)];
        yield 'signed with another secret' => [new SignedCursorEncoder(new Base64JsonCursorEncoder(), 'another secret')->encode(new Cursor(['p.id' => 42]))];
        yield 'empty string' => [''];
    }

    /**
     * @dataProvider dataTamperedCursors
     */
    #[DataProvider('dataTamperedCursors')]
    public function testATamperedCursorIsRejectedBeforeItIsDecoded(string $encoded): void
    {
        $decorated = $this->createMock(CursorEncoderInterface::class);
        $decorated->method('encode')->willReturnCallback(static fn (Cursor $cursor): string => new Base64JsonCursorEncoder()->encode($cursor));
        $decorated->expects($this->never())->method('decode');

        $this->expectException(InvalidCursorException::class);

        new SignedCursorEncoder($decorated, 'secret')->decode($encoded);
    }

    public function testAnInvalidCursorWithAValidSignatureIsRejectedByTheDecoratedEncoder(): void
    {
        // The decorated encoder produces a payload which it cannot decode, so the payload is correctly signed but not a valid cursor
        $decorated = new class implements CursorEncoderInterface {
            public function encode(Cursor $cursor): string
            {
                return 'not-a-cursor';
            }

            public function decode(string $encoded): Cursor
            {
                return new Base64JsonCursorEncoder()->decode($encoded);
            }
        };

        $encoder = new SignedCursorEncoder($decorated, 'secret');

        $this->expectException(InvalidCursorException::class);

        $encoder->decode($encoder->encode(new Cursor(['id' => 1])));
    }

    public function testTheSecretMustNotBeEmpty(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->createEncoder('');
    }
}
