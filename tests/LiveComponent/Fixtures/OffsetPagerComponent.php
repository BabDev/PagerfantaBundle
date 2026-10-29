<?php declare(strict_types=1);

namespace BabDev\PagerfantaBundle\Tests\LiveComponent\Fixtures;

use BabDev\PagerfantaBundle\LiveComponent\ComponentWithPagerfantaTrait;
use Pagerfanta\Adapter\ArrayAdapter;
use Pagerfanta\Pagerfanta;
use Pagerfanta\PagerfantaInterface;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;

/**
 * Paginates the numbers from the minimum up to 50, 10 per page.
 */
#[AsLiveComponent('OffsetPager', template: 'components/OffsetPager.html.twig')]
final class OffsetPagerComponent
{
    /** @use ComponentWithPagerfantaTrait<int> */
    use ComponentWithPagerfantaTrait;
    use DefaultActionTrait;

    #[LiveProp(writable: true, url: true, onUpdated: 'onMinimumUpdated')]
    public int $minimum = 1;

    public function onMinimumUpdated(): void
    {
        $this->resetPage();
    }

    /**
     * @return PagerfantaInterface<int>
     */
    protected function createPager(): PagerfantaInterface
    {
        return new Pagerfanta(new ArrayAdapter(range($this->minimum, 50)))->setMaxPerPage(10);
    }

    /**
     * @return array<string, mixed>
     */
    protected function getPaginationRouteParams(): array
    {
        return [...$this->paginationRouteParams, 'minimum' => $this->minimum];
    }
}
