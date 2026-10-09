<?php declare(strict_types=1);

namespace BabDev\PagerfantaBundle\Tests\RouteGenerator;

use BabDev\PagerfantaBundle\RouteGenerator\RequestAwareRouteGeneratorFactory;
use BabDev\PagerfantaBundle\RouteGenerator\RouterAwarePositionRouteGenerator;
use BabDev\PagerfantaBundle\Tests\CapturesDeprecations;
use Pagerfanta\Cursor\Base64JsonCursorEncoder;
use Pagerfanta\Exception\RuntimeException;
use Pagerfanta\Position\PagePosition;
use PHPUnit\Framework\Attributes\DoesNotPerformAssertions;
use PHPUnit\Framework\Attributes\Group;
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

/**
 * @group legacy
 */
#[Group('legacy')]
final class RequestAwareRouteGeneratorFactoryTest extends TestCase
{
    use CapturesDeprecations;

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
        } while ($request instanceof Request);
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

    public function testTheFactoryIsDeprecated(): void
    {
        $deprecations = $this->captureDeprecations(fn () => $this->createFactory());

        self::assertSame(['Since babdev/pagerfanta-bundle 4.7: The "BabDev\\PagerfantaBundle\\RouteGenerator\\RequestAwareRouteGeneratorFactory" class is deprecated, use the "BabDev\\PagerfantaBundle\\RouteGenerator\\RequestAwarePositionRouteGeneratorFactory" class instead.'], $deprecations);
    }

    public function testThePositionRouteGeneratorIsCreatedByTheReplacementFactory(): void
    {
        $routeCollection = new RouteCollection();
        $routeCollection->add('pagerfanta_view', new Route('/pagerfanta-view'));

        $generator = (new RequestAwareRouteGeneratorFactory(new UrlGenerator($routeCollection, new RequestContext()), $this->requestStack, PropertyAccess::createPropertyAccessor(), new Base64JsonCursorEncoder()))->createPositionRouteGenerator(['routeName' => 'pagerfanta_view']);

        self::assertInstanceOf(RouterAwarePositionRouteGenerator::class, $generator);
        self::assertSame('/pagerfanta-view?page=2', $generator(new PagePosition(2)));
    }
}
