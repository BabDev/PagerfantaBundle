<?php declare(strict_types=1);

namespace BabDev\PagerfantaBundle\Tests\LiveComponent;

use BabDev\PagerfantaBundle\Tests\LiveComponent\Fixtures\LiveComponentKernel;
use BabDev\PagerfantaBundle\Tests\LiveComponent\Fixtures\OffsetPagerComponent;
use Pagerfanta\Twig\Extension\PagerfantaExtension;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\UX\LiveComponent\LiveComponentBundle;

final class ComponentWithPagerfantaTraitTest extends WebTestCase
{
    use InteractsWithLiveComponentsWhenInstalled;

    public static function setUpBeforeClass(): void
    {
        if (!class_exists(LiveComponentBundle::class)) {
            self::markTestSkipped('Test requires symfony/ux-live-component');
        }

        // The Twig packages are installed with symfony/ux-live-component, but the components are rendered with the Pagerfanta Twig integration
        if (!class_exists(PagerfantaExtension::class)) {
            self::markTestSkipped('Test requires pagerfanta/twig');
        }

        new Filesystem()->remove(sys_get_temp_dir().'/babdev_pagerfanta_bundle/live_component');
    }

    protected static function getKernelClass(): string
    {
        return LiveComponentKernel::class;
    }

    public function testThePageIsResolvedFromTheUrlAndTheLinksPointToThePage(): void
    {
        $client = self::createClient();
        $client->request('GET', '/offset?page=2');

        $html = (string) $client->getResponse()->getContent();

        self::assertResponseIsSuccessful();
        $this->assertStringContainsString('<li>11</li>', $html);
        $this->assertStringContainsString('<li>20</li>', $html);
        $this->assertStringNotContainsString('<li>21</li>', $html);
        $this->assertStringContainsString('href="/offset?minimum=1&amp;page=3" rel="next" data-action="live#action:prevent" data-live-action-param="goToPage" data-live-page-param="3">', $html);
        $this->assertStringContainsString('href="/offset?minimum=1&amp;page=1" data-action="live#action:prevent" data-live-action-param="goToPage" data-live-page-param="1">', $html);
    }

    public function testTheRouteParametersOfThePageAreKept(): void
    {
        $client = self::createClient();
        $client->request('GET', '/categories/books');

        self::assertResponseIsSuccessful();
        $this->assertStringContainsString('href="/categories/books?minimum=1&amp;page=2"', (string) $client->getResponse()->getContent());
    }

    public function testThePageIsChangedWithAnActionAndTheLinksStillPointToThePage(): void
    {
        $component = $this->createLiveComponent('OffsetPager', ['paginationRoute' => 'offset_pager'])
            ->call('goToPage', ['page' => 3]);

        $html = (string) $component->render();

        $this->assertStringContainsString('<li>21</li>', $html);
        $this->assertStringContainsString('<li>30</li>', $html);
        $this->assertStringContainsString('href="/offset?minimum=1&amp;page=4" rel="next"', $html);
        $this->assertStringNotContainsString('href="/_components', $html);

        $object = $component->component();

        $this->assertInstanceOf(OffsetPagerComponent::class, $object);
        $this->assertSame(3, $object->page);
    }

    public function testAnOutOfRangePageIsMovedToTheLastPage(): void
    {
        $component = $this->createLiveComponent('OffsetPager', ['paginationRoute' => 'offset_pager'])
            ->set('page', 99);

        $html = (string) $component->render();

        $this->assertStringContainsString('<li>41</li>', $html);
        $this->assertStringContainsString('<li>50</li>', $html);

        $object = $component->component();

        $this->assertInstanceOf(OffsetPagerComponent::class, $object);
        $this->assertSame(5, $object->page);
    }

    public function testAnOutOfRangePageInTheUrlIsMovedToTheLastPage(): void
    {
        $client = self::createClient();
        $client->request('GET', '/offset?page=99');

        self::assertResponseIsSuccessful();
        $this->assertStringContainsString('<li>50</li>', (string) $client->getResponse()->getContent());
    }

    public function testChangingAFilterReturnsToTheFirstPage(): void
    {
        $component = $this->createLiveComponent('OffsetPager', ['paginationRoute' => 'offset_pager'])
            ->call('goToPage', ['page' => 3])
            ->set('minimum', 5);

        $html = (string) $component->render();

        $this->assertStringContainsString('<li>5</li>', $html);
        $this->assertStringContainsString('<li>14</li>', $html);
        $this->assertStringContainsString('href="/offset?minimum=5&amp;page=2" rel="next"', $html);
    }

    public function testACursorPagerIsNavigatedWithTheEncodedCursorsOfItsLinks(): void
    {
        $client = self::createClient();
        $client->request('GET', '/cursor');

        $html = (string) $client->getResponse()->getContent();

        self::assertResponseIsSuccessful();
        $this->assertStringContainsString('<li>10</li>', $html);
        $this->assertMatchesRegularExpression('#href="/cursor\?cursor=([^"]+)" rel="next" data-action="live\#action:prevent" data-live-action-param="goToCursor" data-live-cursor-param="\1">#', $html);

        if (1 !== preg_match('#data-live-cursor-param="([^"]+)"#', $html, $matches)) {
            $this->fail('The cursor of the next link was not rendered.');
        }

        self::ensureKernelShutdown();

        $html = (string) $this->createLiveComponent('CursorPager', ['paginationRoute' => 'cursor_pager'])
            ->call('goToCursor', ['cursor' => html_entity_decode($matches[1])])
            ->render();

        $this->assertStringContainsString('<li>11</li>', $html);
        $this->assertStringContainsString('<li>20</li>', $html);
        $this->assertStringContainsString('rel="prev" data-action="live#action:prevent" data-live-action-param="goToCursor"', $html);
    }

    public function testAnInvalidCursorReturnsToTheFirstPage(): void
    {
        $client = self::createClient();
        $client->request('GET', '/cursor?cursor=tampered');

        self::assertResponseIsSuccessful();
        $this->assertStringContainsString('<li>1</li>', (string) $client->getResponse()->getContent());
    }

    public function testTheTemplateFromTheOptionsRendersTheDefaultTemplateWithTranslatedMessages(): void
    {
        $client = self::createClient();
        $client->request('GET', '/offset?page=2');

        $html = (string) $client->getResponse()->getContent();

        self::assertResponseIsSuccessful();
        $this->assertStringContainsString('<nav class="pagination">', $html);
        $this->assertStringContainsString('data-live-page-param="1">Précédent</a>', $html);
        $this->assertStringContainsString('data-live-page-param="3">Suivant</a>', $html);
    }

    public function testATemplateExtendingASuppliedTemplateRendersTranslatedMessages(): void
    {
        $client = self::createClient();
        $client->request('GET', '/cursor');

        self::assertResponseIsSuccessful();
        $this->assertMatchesRegularExpression('#data-live-cursor-param="[^"]+">Suivant</a>#', (string) $client->getResponse()->getContent());
    }

    public function testTheRouteOfTheLinksMustBeKnown(): void
    {
        $this->expectExceptionMessage('set the "paginationRoute" property when rendering the component');

        $this->createLiveComponent('OffsetPager')->render();
    }
}
