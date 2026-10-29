<?php declare(strict_types=1);

namespace BabDev\PagerfantaBundle\LiveComponent;

use Pagerfanta\Cursor\CursorEncoderInterface;
use Pagerfanta\CursorPagerInterface;
use Pagerfanta\Exception\InvalidCursorException;
use Pagerfanta\Exception\LogicException;
use Pagerfanta\PagerfantaInterface;
use Pagerfanta\Position\CursorPosition;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Contracts\Service\Attribute\Required;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveArg;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\Attribute\PreReRender;
use Symfony\UX\TwigComponent\Attribute\PostMount;

/**
 * Paginates a live component with a Pagerfanta pager.
 *
 * The position of the current page is kept in the component's state and the URL, using the "page" parameter for offset
 * pagers and the "cursor" parameter for cursor pagers. The links of the pager point to the route of the page the
 * component was first rendered on, so they keep working when the component is not connected.
 *
 * Out of range pages are normalized to the last page, and invalid cursors are reset to the first page.
 *
 * @template T
 */
trait ComponentWithPagerfantaTrait
{
    /**
     * The current page of an offset pager.
     */
    #[LiveProp(writable: true, url: true)]
    public int $page = 1;

    /**
     * The encoded cursor of the current page of a cursor pager, or null for the first page.
     */
    #[LiveProp(writable: true, url: true)]
    public ?string $cursor = null;

    /**
     * The route the links of the pager point to, the route of the page the component was first rendered on is used when not given.
     */
    #[LiveProp]
    public ?string $paginationRoute = null;

    /**
     * The parameters for the route the links of the pager point to.
     *
     * @var array<string, mixed>
     */
    #[LiveProp]
    public array $paginationRouteParams = [];

    /**
     * @var PagerfantaInterface<T>|CursorPagerInterface<T>|null
     */
    private PagerfantaInterface|CursorPagerInterface|null $pagerfantaPager = null;

    private ?CursorEncoderInterface $pagerfantaCursorEncoder = null;

    private ?RequestStack $pagerfantaRequestStack = null;

    /**
     * Creates the pager for the component, its position is set from the state of the component.
     *
     * @return PagerfantaInterface<T>|CursorPagerInterface<T>
     */
    abstract protected function createPager(): PagerfantaInterface|CursorPagerInterface;

    #[Required]
    public function setPagerfantaCursorEncoder(CursorEncoderInterface $cursorEncoder): void
    {
        $this->pagerfantaCursorEncoder = $cursorEncoder;
    }

    #[Required]
    public function setPagerfantaRequestStack(RequestStack $requestStack): void
    {
        $this->pagerfantaRequestStack = $requestStack;
    }

    /**
     * @return PagerfantaInterface<T>|CursorPagerInterface<T>
     */
    public function getPager(): PagerfantaInterface|CursorPagerInterface
    {
        return $this->pagerfantaPager ??= $this->resolvePager();
    }

    /**
     * Returns the options for the pagerfanta() Twig function, so the links of the pager point to the page instead of the live component endpoint.
     *
     * @return array{routeName: string, routeParams: array<string, mixed>}
     *
     * @throws LogicException if the route of the links is not known
     */
    public function getPagerfantaOptions(): array
    {
        if (null === $this->paginationRoute) {
            throw new LogicException(\sprintf('The route for the links of the pager of the "%s" component is not known, set the "paginationRoute" property when rendering the component.', static::class));
        }

        return [
            'routeName' => $this->paginationRoute,
            'routeParams' => $this->getPaginationRouteParams(),
        ];
    }

    #[LiveAction]
    public function goToPage(#[LiveArg] int $page): void
    {
        $this->page = max(1, $page);
        $this->cursor = null;
        $this->pagerfantaPager = null;
    }

    #[LiveAction]
    public function goToCursor(#[LiveArg] string $cursor): void
    {
        $this->page = 1;
        $this->cursor = $cursor;
        $this->pagerfantaPager = null;
    }

    /**
     * Returns to the first page, such as when the filters of the component change.
     */
    public function resetPage(): void
    {
        $this->page = 1;
        $this->cursor = null;
        $this->pagerfantaPager = null;
    }

    /**
     * Resolves the route of the links and the position of the pager when the component is first rendered.
     *
     * @internal
     */
    #[PostMount]
    public function mountPagerfanta(): void
    {
        if (null === $this->paginationRoute) {
            $this->resolvePaginationRoute();
        }

        $this->getPager();
    }

    /**
     * Resolves the position of the pager before the component is rendered again, after its state is changed.
     *
     * @internal
     */
    #[PreReRender]
    public function prepareReRenderPagerfanta(): void
    {
        $this->pagerfantaPager = null;
        $this->getPager();
    }

    /**
     * Returns the parameters for the route the links of the pager point to, override this method to add the filters of the component.
     *
     * @return array<string, mixed>
     */
    protected function getPaginationRouteParams(): array
    {
        return $this->paginationRouteParams;
    }

    /**
     * @return PagerfantaInterface<T>|CursorPagerInterface<T>
     */
    private function resolvePager(): PagerfantaInterface|CursorPagerInterface
    {
        return $this->positionPager($this->createPager());
    }

    /**
     * Sets the position of the pager from the state of the component, normalizing the state to the position of the pager.
     *
     * @param PagerfantaInterface<T>|CursorPagerInterface<T> $pager
     *
     * @return PagerfantaInterface<T>|CursorPagerInterface<T>
     *
     * @throws LogicException if a cursor pager cannot be moved to another position
     */
    private function positionPager(PagerfantaInterface|CursorPagerInterface $pager): PagerfantaInterface|CursorPagerInterface
    {
        if ($pager instanceof PagerfantaInterface) {
            $this->cursor = null;

            // A page which no longer exists, such as after the filters are changed, is moved to the last page
            $pager->setNormalizeOutOfRangePages(true);
            $pager->setCurrentPage(max(1, $this->page));

            $this->page = $pager->getCurrentPage();

            return $pager;
        }

        $this->page = 1;

        // Cursor pagers are not required to implement withPosition() until Pagerfanta 5.0
        if (!method_exists($pager, 'withPosition')) {
            throw new LogicException(\sprintf('The "%s" pager created by the "%s" component must implement a withPosition() method to be paginated by the component.', get_debug_type($pager), static::class));
        }

        $positionedPager = $pager->withPosition($this->resolveCursorPosition());

        if (!$positionedPager instanceof CursorPagerInterface) {
            throw new LogicException(\sprintf('The withPosition() method of the "%s" pager must return an instance of "%s", "%s" returned.', get_debug_type($pager), CursorPagerInterface::class, get_debug_type($positionedPager)));
        }

        return $positionedPager;
    }

    private function resolveCursorPosition(): ?CursorPosition
    {
        if (null === $this->cursor) {
            return null;
        }

        if (null === $this->pagerfantaCursorEncoder) {
            throw new LogicException(\sprintf('The cursor encoder was not set to the "%s" component, ensure the component is autowired.', static::class));
        }

        try {
            return new CursorPosition($this->pagerfantaCursorEncoder->decode($this->cursor));
        } catch (InvalidCursorException) {
            // A cursor which cannot be decoded, such as one which was tampered with, returns to the first page
            $this->cursor = null;

            return null;
        }
    }

    private function resolvePaginationRoute(): void
    {
        $request = $this->pagerfantaRequestStack?->getMainRequest();

        // The route of the page is unknown when the component is first rendered by another live component
        if (null === $request || $request->attributes->has('_live_component')) {
            return;
        }

        $route = $request->attributes->get('_route');

        if (!\is_string($route)) {
            return;
        }

        $routeParams = $request->attributes->get('_route_params', []);

        $this->paginationRoute = $route;
        $this->paginationRouteParams = \is_array($routeParams) ? array_filter($routeParams, \is_string(...), \ARRAY_FILTER_USE_KEY) : [];
    }
}
