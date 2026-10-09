<?php declare(strict_types=1);

namespace BabDev\PagerfantaBundle\RouteGenerator;

use Pagerfanta\Cursor\CursorEncoderInterface;
use Pagerfanta\Exception\InvalidArgumentException;
use Pagerfanta\Position\CursorPosition;
use Pagerfanta\Position\PagePosition;
use Pagerfanta\Position\Position;
use Pagerfanta\RouteGenerator\PositionRouteGeneratorInterface;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\Component\PropertyAccess\PropertyPath;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Generates the URLs for the positions of a pager with the Symfony router.
 *
 * Page positions are set to the page parameter and cursor positions are encoded and set to the cursor parameter. The
 * parameter for the other kind of position is removed, so a URL never has both a page and a cursor.
 *
 * @phpstan-type RouteGeneratorOptions array{routeName: non-empty-string, pageParameter?: non-empty-string, cursorParameter?: non-empty-string, omitFirstPage?: bool, routeParams?: array<string, mixed>, referenceType?: UrlGeneratorInterface::*}
 */
final readonly class RouterAwarePositionRouteGenerator implements PositionRouteGeneratorInterface
{
    /**
     * @phpstan-param RouteGeneratorOptions $options
     *
     * @throws InvalidArgumentException if the route name is not set in the options
     */
    public function __construct(
        private UrlGeneratorInterface $router,
        private PropertyAccessorInterface $propertyAccessor,
        private CursorEncoderInterface $cursorEncoder,
        private array $options,
    ) {
        if (!isset($options['routeName'])) {
            throw new InvalidArgumentException(\sprintf('The "%s" class options requires a "routeName" parameter to be set.', self::class));
        }
    }

    /**
     * @throws InvalidArgumentException if the position is not supported
     */
    public function __invoke(Position $position): string
    {
        $pagePropertyPath = new PropertyPath($this->options['pageParameter'] ?? '[page]');
        $cursorPropertyPath = new PropertyPath($this->options['cursorParameter'] ?? '[cursor]');
        $routeParams = $this->options['routeParams'] ?? [];

        if ($position instanceof PagePosition) {
            $omitFirstPage = $this->options['omitFirstPage'] ?? false;

            $this->propertyAccessor->setValue($routeParams, $pagePropertyPath, $omitFirstPage && 1 === $position->page ? null : $position->page);
            $this->propertyAccessor->setValue($routeParams, $cursorPropertyPath, null);
        } elseif ($position instanceof CursorPosition) {
            $this->propertyAccessor->setValue($routeParams, $cursorPropertyPath, $this->cursorEncoder->encode($position->cursor));
            $this->propertyAccessor->setValue($routeParams, $pagePropertyPath, null);
        } else {
            throw new InvalidArgumentException(\sprintf('The "%s" route generator does not support "%s" positions.', self::class, get_debug_type($position)));
        }

        return $this->router->generate($this->options['routeName'], $routeParams, $this->options['referenceType'] ?? UrlGeneratorInterface::ABSOLUTE_PATH);
    }
}
