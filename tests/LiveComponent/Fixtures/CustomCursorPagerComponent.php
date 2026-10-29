<?php declare(strict_types=1);

namespace BabDev\PagerfantaBundle\Tests\LiveComponent\Fixtures;

use BabDev\PagerfantaBundle\LiveComponent\ComponentWithPagerfantaTrait;
use Pagerfanta\CursorPagerInterface;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;

/**
 * Paginates with a custom cursor pager which cannot be moved to another position.
 */
#[AsLiveComponent('CustomCursorPager', template: 'components/CursorPager.html.twig')]
final class CustomCursorPagerComponent
{
    /** @use ComponentWithPagerfantaTrait<int> */
    use ComponentWithPagerfantaTrait;
    use DefaultActionTrait;

    /**
     * Whether the pager implements a withPosition() method which does not return a cursor pager.
     */
    #[LiveProp]
    public bool $invalidWithPosition = false;

    /**
     * @return CursorPagerInterface<int>
     */
    protected function createPager(): CursorPagerInterface
    {
        return $this->invalidWithPosition ? new CursorPagerWithInvalidWithPosition() : new CursorPagerWithoutWithPosition();
    }
}
