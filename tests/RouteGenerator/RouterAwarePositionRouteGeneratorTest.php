<?php declare(strict_types=1);

namespace BabDev\PagerfantaBundle\Tests\RouteGenerator;

use BabDev\PagerfantaBundle\RouteGenerator\RouterAwarePositionRouteGenerator;
use Pagerfanta\Cursor\Base64JsonCursorEncoder;
use Pagerfanta\Cursor\Cursor;
use Pagerfanta\Exception\InvalidArgumentException;
use Pagerfanta\Position\CursorPosition;
use Pagerfanta\Position\PagePosition;
use Pagerfanta\Position\Position;
use PHPUnit\Framework\TestCase;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\Routing\Generator\UrlGenerator;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

final class RouterAwarePositionRouteGeneratorTest extends TestCase
{
    /**
     * @param array{pageParameter?: non-empty-string, cursorParameter?: non-empty-string, omitFirstPage?: bool, routeParams?: array<string, mixed>, referenceType?: UrlGeneratorInterface::*} $options
     */
    private function createGenerator(array $options = []): RouterAwarePositionRouteGenerator
    {
        $routeCollection = new RouteCollection();
        $routeCollection->add('pagerfanta_view', new Route('/pagerfanta-view'));

        return new RouterAwarePositionRouteGenerator(
            new UrlGenerator($routeCollection, new RequestContext()),
            PropertyAccess::createPropertyAccessor(),
            new Base64JsonCursorEncoder(),
            ['routeName' => 'pagerfanta_view', ...$options],
        );
    }

    private function encode(Cursor $cursor): string
    {
        return (new Base64JsonCursorEncoder())->encode($cursor);
    }

    public function testARouteIsGeneratedForAPage(): void
    {
        self::assertSame('/pagerfanta-view?page=1', $this->createGenerator()(new PagePosition(1)));
    }

    public function testARouteIsGeneratedForTheFirstPageWithTheFirstPageOmitted(): void
    {
        $generator = $this->createGenerator(['omitFirstPage' => true]);

        self::assertSame('/pagerfanta-view', $generator(new PagePosition(1)));
        self::assertSame('/pagerfanta-view?page=2', $generator(new PagePosition(2)));
    }

    public function testARouteIsGeneratedForAPageWithACustomPageParameter(): void
    {
        self::assertSame('/pagerfanta-view?custom_page=2', $this->createGenerator(['pageParameter' => '[custom_page]'])(new PagePosition(2)));
    }

    public function testARouteIsGeneratedForACursor(): void
    {
        $cursor = new Cursor(['p.id' => 42]);

        self::assertSame('/pagerfanta-view?cursor='.$this->encode($cursor), $this->createGenerator()(new CursorPosition($cursor)));
    }

    public function testARouteIsGeneratedForACursorWithACustomCursorParameter(): void
    {
        $cursor = new Cursor(['p.id' => 42]);

        self::assertSame('/pagerfanta-view?after='.$this->encode($cursor), $this->createGenerator(['cursorParameter' => '[after]'])(new CursorPosition($cursor)));
    }

    public function testTheCursorParameterIsRemovedForAPageAndThePageParameterIsRemovedForACursor(): void
    {
        $cursor = new Cursor(['p.id' => 42]);

        $generator = $this->createGenerator(['routeParams' => ['page' => 3, 'cursor' => 'stale', 'hello' => 'world']]);

        self::assertSame('/pagerfanta-view?page=2&hello=world', $generator(new PagePosition(2)));
        self::assertSame('/pagerfanta-view?cursor='.$this->encode($cursor).'&hello=world', $generator(new CursorPosition($cursor)));
    }

    public function testARouteIsGeneratedWithAnAbsoluteUrl(): void
    {
        self::assertSame('http://localhost/pagerfanta-view?page=2', $this->createGenerator(['referenceType' => UrlGeneratorInterface::ABSOLUTE_URL])(new PagePosition(2)));
    }

    public function testAnUnsupportedPositionIsRejected(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->createGenerator()(new class implements Position {});
    }

    public function testARouteIsNotGeneratedWhenTheRouteNameParameterIsMissing(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new RouterAwarePositionRouteGenerator(
            new UrlGenerator(new RouteCollection(), new RequestContext()),
            PropertyAccess::createPropertyAccessor(),
            new Base64JsonCursorEncoder(),
            [], // @phpstan-ignore argument.type
        );
    }
}
