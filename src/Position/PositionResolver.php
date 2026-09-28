<?php declare(strict_types=1);

namespace BabDev\PagerfantaBundle\Position;

use Pagerfanta\Cursor\CursorEncoderInterface;
use Pagerfanta\Exception\InvalidCursorException;
use Pagerfanta\Exception\LessThan1CurrentPageException;
use Pagerfanta\Exception\NotValidCurrentPageException;
use Pagerfanta\Position\CursorPosition;
use Pagerfanta\Position\PagePosition;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\Component\PropertyAccess\PropertyPath;

/**
 * Resolves the position of the current page of a pager from a request.
 *
 * The parameters are read from the query and route parameters of the request, using the same "pageParameter" and
 * "cursorParameter" options as the route generators. A null position means the first page is requested.
 *
 * @phpstan-type PositionResolverOptions array{pageParameter?: non-empty-string, cursorParameter?: non-empty-string}
 */
final class PositionResolver
{
    public function __construct(
        private readonly PropertyAccessorInterface $propertyAccessor,
        private readonly CursorEncoderInterface $cursorEncoder,
    ) {}

    /**
     * Resolves the position for a pager which may use either pagination strategy, a cursor takes precedence over a page.
     *
     * @phpstan-param PositionResolverOptions $options
     *
     * @throws InvalidCursorException       if the cursor is not valid
     * @throws NotValidCurrentPageException if the page is not a positive integer
     */
    public function resolve(Request $request, array $options = []): PagePosition|CursorPosition|null
    {
        return $this->resolveCursorPosition($request, $options) ?? $this->resolvePagePosition($request, $options);
    }

    /**
     * Resolves the position for an offset pager, the cursor parameter is ignored.
     *
     * @phpstan-param PositionResolverOptions $options
     *
     * @throws NotValidCurrentPageException if the page is not a positive integer
     */
    public function resolvePagePosition(Request $request, array $options = []): ?PagePosition
    {
        $parameter = $options['pageParameter'] ?? '[page]';
        $page = $this->readParameter($request, $parameter);

        if (null === $page || '' === $page) {
            return null;
        }

        if (\is_string($page) && ctype_digit($page)) {
            $page = (int) $page;
        }

        if (!\is_int($page)) {
            throw new NotValidCurrentPageException(\sprintf('The "%s" parameter must be a positive integer.', $parameter));
        }

        if ($page < 1) {
            throw new LessThan1CurrentPageException();
        }

        return new PagePosition($page);
    }

    /**
     * Resolves the position for a cursor pager, the page parameter is ignored.
     *
     * @phpstan-param PositionResolverOptions $options
     *
     * @throws InvalidCursorException if the cursor is not valid
     */
    public function resolveCursorPosition(Request $request, array $options = []): ?CursorPosition
    {
        $parameter = $options['cursorParameter'] ?? '[cursor]';
        $cursor = $this->readParameter($request, $parameter);

        if (null === $cursor || '' === $cursor) {
            return null;
        }

        if (!\is_string($cursor)) {
            throw new InvalidCursorException(\sprintf('The "%s" parameter must be a string.', $parameter));
        }

        return new CursorPosition($this->cursorEncoder->decode($cursor));
    }

    private function readParameter(Request $request, string $parameter): mixed
    {
        $routeParams = $request->attributes->get('_route_params', []);

        $parameters = array_merge($request->query->all(), \is_array($routeParams) ? $routeParams : []);
        $propertyPath = new PropertyPath($parameter);

        if (!$this->propertyAccessor->isReadable($parameters, $propertyPath)) {
            return null;
        }

        return $this->propertyAccessor->getValue($parameters, $propertyPath);
    }
}
