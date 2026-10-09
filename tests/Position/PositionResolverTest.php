<?php declare(strict_types=1);

namespace BabDev\PagerfantaBundle\Tests\Position;

use BabDev\PagerfantaBundle\Cursor\SignedCursorEncoder;
use BabDev\PagerfantaBundle\Position\PositionResolver;
use Pagerfanta\Cursor\Base64JsonCursorEncoder;
use Pagerfanta\Cursor\Cursor;
use Pagerfanta\Cursor\Direction;
use Pagerfanta\Exception\InvalidCursorException;
use Pagerfanta\Exception\LessThan1CurrentPageException;
use Pagerfanta\Exception\NotValidCurrentPageException;
use Pagerfanta\Position\CursorPosition;
use Pagerfanta\Position\PagePosition;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PropertyAccess\PropertyAccess;

final class PositionResolverTest extends TestCase
{
    private SignedCursorEncoder $cursorEncoder;

    private PositionResolver $resolver;

    protected function setUp(): void
    {
        $this->cursorEncoder = new SignedCursorEncoder(new Base64JsonCursorEncoder(), 'secret');
        $this->resolver = new PositionResolver(PropertyAccess::createPropertyAccessor(), $this->cursorEncoder);
    }

    public function testNoPositionIsResolvedForTheFirstPage(): void
    {
        $request = Request::create('/');

        self::assertNull($this->resolver->resolve($request));
        self::assertNull($this->resolver->resolvePagePosition($request));
        self::assertNull($this->resolver->resolveCursorPosition($request));
    }

    public function testNoPositionIsResolvedForEmptyParameters(): void
    {
        self::assertNull($this->resolver->resolve(Request::create('/', 'GET', ['page' => '', 'cursor' => ''])));
    }

    public function testAPagePositionIsResolvedFromTheQuery(): void
    {
        $request = Request::create('/', 'GET', ['page' => '3']);

        self::assertEquals(new PagePosition(3), $this->resolver->resolvePagePosition($request));
        self::assertEquals(new PagePosition(3), $this->resolver->resolve($request));
    }

    public function testAPagePositionIsResolvedFromTheRouteParameters(): void
    {
        $request = Request::create('/posts/3');
        $request->attributes->set('_route_params', ['page' => 3]);

        self::assertEquals(new PagePosition(3), $this->resolver->resolvePagePosition($request));
    }

    public function testAPagePositionIsResolvedFromACustomParameter(): void
    {
        $request = Request::create('/', 'GET', ['filters' => ['page' => '2']]);

        self::assertEquals(new PagePosition(2), $this->resolver->resolvePagePosition($request, ['pageParameter' => '[filters][page]']));
    }

    public static function dataInvalidPages(): \Generator
    {
        yield 'not a number' => ['abc', NotValidCurrentPageException::class];
        yield 'decimal' => ['1.5', NotValidCurrentPageException::class];
        yield 'negative' => ['-1', NotValidCurrentPageException::class];
        yield 'array' => [['1'], NotValidCurrentPageException::class];
        yield 'zero' => ['0', LessThan1CurrentPageException::class];
    }

    /**
     * @param class-string<\Throwable> $exception
     *
     * @dataProvider dataInvalidPages
     */
    #[DataProvider('dataInvalidPages')]
    public function testAnInvalidPageIsRejected(mixed $page, string $exception): void
    {
        $this->expectException($exception);

        $this->resolver->resolvePagePosition(Request::create('/', 'GET', ['page' => $page]));
    }

    public function testACursorPositionIsResolvedFromTheQuery(): void
    {
        $cursor = new Cursor(['p.id' => 42], Direction::Previous);
        $request = Request::create('/', 'GET', ['cursor' => $this->cursorEncoder->encode($cursor)]);

        self::assertEquals(new CursorPosition($cursor), $this->resolver->resolveCursorPosition($request));
        self::assertEquals(new CursorPosition($cursor), $this->resolver->resolve($request));
    }

    public function testACursorPositionIsResolvedFromACustomParameter(): void
    {
        $cursor = new Cursor(['p.id' => 42]);
        $request = Request::create('/', 'GET', ['after' => $this->cursorEncoder->encode($cursor)]);

        self::assertEquals(new CursorPosition($cursor), $this->resolver->resolveCursorPosition($request, ['cursorParameter' => '[after]']));
    }

    public static function dataInvalidCursors(): \Generator
    {
        $encoded = new SignedCursorEncoder(new Base64JsonCursorEncoder(), 'secret')->encode(new Cursor(['p.id' => 42]));

        yield 'unsigned' => [new Base64JsonCursorEncoder()->encode(new Cursor(['p.id' => 42]))];
        yield 'tampered' => [substr($encoded, 0, -2).'xx'];
        yield 'garbage' => ['not a cursor'];
        yield 'array' => [[$encoded]];
    }

    /**
     * @dataProvider dataInvalidCursors
     */
    #[DataProvider('dataInvalidCursors')]
    public function testAnInvalidCursorIsRejected(mixed $cursor): void
    {
        $this->expectException(InvalidCursorException::class);

        $this->resolver->resolveCursorPosition(Request::create('/', 'GET', ['cursor' => $cursor]));
    }

    public function testACursorTakesPrecedenceOverAPageWhenResolvingEitherPosition(): void
    {
        $cursor = new Cursor(['p.id' => 42]);
        $request = Request::create('/', 'GET', ['page' => '3', 'cursor' => $this->cursorEncoder->encode($cursor)]);

        self::assertEquals(new CursorPosition($cursor), $this->resolver->resolve($request));
    }

    public function testTheOtherParameterIsIgnoredWhenResolvingASpecificPosition(): void
    {
        self::assertEquals(new PagePosition(3), $this->resolver->resolvePagePosition(Request::create('/', 'GET', ['page' => '3', 'cursor' => 'tampered'])));
        self::assertNull($this->resolver->resolveCursorPosition(Request::create('/', 'GET', ['page' => 'abc'])));
    }
}
