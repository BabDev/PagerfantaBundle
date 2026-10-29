<?php declare(strict_types=1);

namespace BabDev\PagerfantaBundle\Tests\LiveComponent\Fixtures;

use BabDev\PagerfantaBundle\LiveComponent\ComponentWithPagerfantaTrait;
use Pagerfanta\Adapter\CallbackCursorAdapter;
use Pagerfanta\Adapter\CursorSlice;
use Pagerfanta\Cursor\Cursor;
use Pagerfanta\Cursor\Direction;
use Pagerfanta\CursorPagerfanta;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\DefaultActionTrait;

/**
 * Paginates the numbers from 1 to 50 with a cursor on the number, 10 per page.
 */
#[AsLiveComponent('CursorPager', template: 'components/CursorPager.html.twig')]
final class CursorPagerComponent
{
    /** @use ComponentWithPagerfantaTrait<int> */
    use ComponentWithPagerfantaTrait;
    use DefaultActionTrait;

    /**
     * @return CursorPagerfanta<int>
     */
    protected function createPager(): CursorPagerfanta
    {
        $adapter = new CallbackCursorAdapter(
            static function (?Cursor $cursor, int $limit): CursorSlice {
                $items = range(1, 50);

                if ($cursor instanceof Cursor) {
                    $id = $cursor->fields['id'];

                    $items = Direction::Previous === $cursor->direction
                        ? \array_slice(array_values(array_filter($items, static fn (int $item): bool => $item < $id)), -$limit)
                        : array_values(array_filter($items, static fn (int $item): bool => $item > $id));
                }

                $items = \array_slice($items, 0, $limit);
                $first = $items[0];
                $last = $items[\count($items) - 1];

                return new CursorSlice(
                    $items,
                    $first > 1 ? new Cursor(['id' => $first], Direction::Previous) : null,
                    $last < 50 ? new Cursor(['id' => $last]) : null,
                );
            },
            true,
        );

        return new CursorPagerfanta($adapter, 10);
    }
}
