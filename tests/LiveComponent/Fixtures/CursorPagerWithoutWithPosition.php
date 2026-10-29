<?php declare(strict_types=1);

namespace BabDev\PagerfantaBundle\Tests\LiveComponent\Fixtures;

use Pagerfanta\CursorPagerInterface;
use Pagerfanta\Exception\LogicException;
use Pagerfanta\Position\CursorPosition;

/**
 * A cursor pager on the first and only page, which does not implement the withPosition() method required in Pagerfanta 5.0.
 *
 * @implements CursorPagerInterface<int>
 */
class CursorPagerWithoutWithPosition implements CursorPagerInterface
{
    public function getCurrentPageResults(): iterable
    {
        return [1];
    }

    public function getMaxPerPage(): int
    {
        return 10;
    }

    public function haveToPaginate(): bool
    {
        return false;
    }

    public function hasPreviousPage(): bool
    {
        return false;
    }

    public function hasNextPage(): bool
    {
        return false;
    }

    public function getPreviousPosition(): CursorPosition
    {
        throw new LogicException('There is no previous page.');
    }

    public function getNextPosition(): CursorPosition
    {
        throw new LogicException('There is no next page.');
    }

    public function getCurrentPosition(): ?CursorPosition
    {
        return null;
    }

    public function supportsBackwardNavigation(): bool
    {
        return false;
    }

    /**
     * @return \ArrayIterator<int, int>
     */
    public function getIterator(): \ArrayIterator
    {
        return new \ArrayIterator([1]);
    }
}
