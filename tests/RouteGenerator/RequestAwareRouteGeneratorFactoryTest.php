<?php declare(strict_types=1);

namespace BabDev\PagerfantaBundle\Tests\RouteGenerator;

use BabDev\PagerfantaBundle\RouteGenerator\RequestAwareRouteGeneratorFactory;
use Pagerfanta\Cursor\Base64JsonCursorEncoder;
use Pagerfanta\Cursor\Cursor;
use Pagerfanta\Exception\RuntimeException;
use Pagerfanta\Position\CursorPosition;
use Pagerfanta\Position\PagePosition;
use PHPUnit\Framework\Attributes\DoesNotPerformAssertions;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\Component\Routing\Generator\UrlGenerator;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

final class RequestAwareRouteGeneratorFactoryTest extends TestCase
{
    private MockObject&UrlGeneratorInterface $router;

    private RequestStack $requestStack;

    private MockObject&PropertyAccessorInterface $propertyAccessor;

    protected function setUp(): void
    {
        $this->router = $this->createMock(UrlGeneratorInterface::class);
        $this->requestStack = new RequestStack();
        $this->propertyAccessor = $this->createMock(PropertyAccessorInterface::class);
    }

    protected function tearDown(): void
    {
        do {
            $request = $this->requestStack->pop();
        } while (null !== $request);
    }

    /**
     * @doesNotPerformAssertions
     */
    #[DoesNotPerformAssertions]
    public function testTheGeneratorIsCreatedWhenResolvingTheRouteNameFromTheRequest(): void
    {
        $request = Request::create('/');
        $request->attributes->set('_route', 'pagerfanta_view');
        $request->attributes->set('_route_params', []);

        $this->requestStack->push($request);

        $this->createFactory()->create();
    }

    /**
     * @doesNotPerformAssertions
     */
    #[DoesNotPerformAssertions]
    public function testTheGeneratorIsCreatedWhenGivenARouteNameDuringASubrequest(): void
    {
        $masterRequest = Request::create('/');
        $masterRequest->attributes->set('_route', 'pagerfanta_view');
        $masterRequest->attributes->set('_route_params', []);

        $subRequest = Request::create('/_internal');

        $this->requestStack->push($masterRequest);
        $this->requestStack->push($subRequest);

        $this->createFactory()->create(['routeName' => 'pagerfanta_view']);
    }

    public function testTheGeneratorIsNotCreatedWhenARouteNameIsNotGivenDuringASubrequest(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('The request aware route generator can not guess the route when used in a sub-request, pass the "routeName" option to use this generator.');

        $masterRequest = Request::create('/');
        $masterRequest->attributes->set('_route', 'pagerfanta_view');
        $masterRequest->attributes->set('_route_params', []);

        $subRequest = Request::create('/_internal');

        $this->requestStack->push($masterRequest);
        $this->requestStack->push($subRequest);

        $this->createFactory()->create();
    }

    public function testTheGeneratorIsNotCreatedWhenARequestIsNotActive(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('The request aware route generator can not be used when there is not an active request.');

        $this->createFactory()->create();
    }

    private function createFactory(): RequestAwareRouteGeneratorFactory
    {
        return new RequestAwareRouteGeneratorFactory(
            $this->router,
            $this->requestStack,
            $this->propertyAccessor
        );
    }

    public function testAPositionRouteGeneratorIsCreatedForTheCurrentRequest(): void
    {
        $routeCollection = new RouteCollection();
        $routeCollection->add('pagerfanta_view', new Route('/pagerfanta-view'));

        $request = Request::create('/pagerfanta-view', 'GET', ['page' => '3', 'cursor' => 'stale', 'hello' => 'world']);
        $request->attributes->set('_route', 'pagerfanta_view');
        $request->attributes->set('_route_params', []);

        $this->requestStack->push($request);

        $generator = (new RequestAwareRouteGeneratorFactory(new UrlGenerator($routeCollection, new RequestContext()), $this->requestStack, PropertyAccess::createPropertyAccessor(), new Base64JsonCursorEncoder()))->createPositionRouteGenerator();

        $cursor = new Cursor(['p.id' => 42]);

        // The parameters from the request keep their position
        self::assertSame('/pagerfanta-view?page=4&hello=world', $generator(new PagePosition(4)));
        self::assertSame('/pagerfanta-view?cursor='.(new Base64JsonCursorEncoder())->encode($cursor).'&hello=world', $generator(new CursorPosition($cursor)));
    }

    public function testAPositionRouteGeneratorIsNotCreatedWhenARequestIsNotActive(): void
    {
        $this->expectException(RuntimeException::class);

        $this->createFactory()->createPositionRouteGenerator();
    }
}
