<?php declare(strict_types=1);

namespace BabDev\PagerfantaBundle\Tests\LiveComponent\Fixtures;

use Pagerfanta\Position\CursorPosition;

/**
 * A cursor pager whose withPosition() method does not return a cursor pager.
 */
final class CursorPagerWithInvalidWithPosition extends CursorPagerWithoutWithPosition
{
    public function withPosition(?CursorPosition $position): \stdClass
    {
        return new \stdClass();
    }
}
