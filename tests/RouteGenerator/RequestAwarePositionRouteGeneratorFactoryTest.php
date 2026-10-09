<?php declare(strict_types=1);

namespace BabDev\PagerfantaBundle\Tests\RouteGenerator;

use BabDev\PagerfantaBundle\RouteGenerator\RequestAwarePositionRouteGeneratorFactory;
use Pagerfanta\Cursor\Base64JsonCursorEncoder;
use Pagerfanta\Cursor\Cursor;
use Pagerfanta\Exception\InvalidArgumentException;
use Pagerfanta\Exception\RuntimeException;
use Pagerfanta\Position\CursorPosition;
use Pagerfanta\Position\PagePosition;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\Routing\Generator\UrlGenerator;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

final class RequestAwarePositionRouteGeneratorFactoryTest extends TestCase
{
    private RequestStack $requestStack;

    protected function setUp(): void
    {
        $this->requestStack = new RequestStack();
    }

    protected function tearDown(): void
    {
        do {
            $request = $this->requestStack->pop();
        } while (null !== $request);
    }

    private function createFactory(): RequestAwarePositionRouteGeneratorFactory
    {
        $routeCollection = new RouteCollection();
        $routeCollection->add('pagerfanta_view', new Route('/pagerfanta-view'));
        $routeCollection->add('other_view', new Route('/other-view'));

        return new RequestAwarePositionRouteGeneratorFactory(new UrlGenerator($routeCollection, new RequestContext()), $this->requestStack, PropertyAccess::createPropertyAccessor(), new Base64JsonCursorEncoder());
    }

    private function pushRequest(): void
    {
        $request = Request::create('/pagerfanta-view', 'GET', ['page' => '3', 'cursor' => 'stale', 'hello' => 'world']);
        $request->attributes->set('_route', 'pagerfanta_view');
        $request->attributes->set('_route_params', []);

        $this->requestStack->push($request);
    }

    public function testAGeneratorIsCreatedForTheCurrentRequest(): void
    {
        $this->pushRequest();

        $generator = $this->createFactory()->createPositionRouteGenerator();

        $cursor = new Cursor(['p.id' => 42]);

        // The parameters from the request keep their position
        self::assertSame('/pagerfanta-view?page=4&hello=world', $generator(new PagePosition(4)));
        self::assertSame('/pagerfanta-view?cursor='.(new Base64JsonCursorEncoder())->encode($cursor).'&hello=world', $generator(new CursorPosition($cursor)));
    }

    public function testTheRouteParametersFromTheOptionsOverrideTheRequestParameters(): void
    {
        $this->pushRequest();

        self::assertSame('/pagerfanta-view?page=2&hello=there', $this->createFactory()->createPositionRouteGenerator(['routeParams' => ['hello' => 'there']])(new PagePosition(2)));
    }

    public function testAGeneratorIsCreatedForAGivenRouteDuringASubrequest(): void
    {
        $this->pushRequest();
        $this->requestStack->push(Request::create('/_internal'));

        self::assertSame('/other-view?page=2', $this->createFactory()->createPositionRouteGenerator(['routeName' => 'other_view'])(new PagePosition(2)));
    }

    public function testAGeneratorIsNotCreatedWhenARouteNameIsNotGivenDuringASubrequest(): void
    {
        $this->pushRequest();
        $this->requestStack->push(Request::create('/_internal'));

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('The request aware route generator can not guess the route when used in a sub-request, pass the "routeName" option to use this generator.');

        $this->createFactory()->createPositionRouteGenerator();
    }

    public function testAGeneratorIsNotCreatedWhenARequestIsNotActive(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('The request aware route generator can not be used when there is not an active request.');

        $this->createFactory()->createPositionRouteGenerator();
    }

    public function testTheRouteParametersOptionMustBeAnArray(): void
    {
        $this->pushRequest();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The "routeParams" option must be an array, "string" given.');

        $this->createFactory()->createPositionRouteGenerator(['routeParams' => 'hello=world']);
    }
}
