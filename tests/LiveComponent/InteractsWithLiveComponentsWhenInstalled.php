<?php declare(strict_types=1);

namespace BabDev\PagerfantaBundle\Tests\LiveComponent;

use Symfony\UX\LiveComponent\Test\InteractsWithLiveComponents;

// Test classes using a missing trait cannot be loaded, so the trait is only used when symfony/ux-live-component is installed and the tests are skipped otherwise
if (trait_exists(InteractsWithLiveComponents::class)) {
    trait InteractsWithLiveComponentsWhenInstalled
    {
        use InteractsWithLiveComponents;
    }
} else {
    trait InteractsWithLiveComponentsWhenInstalled {}
}
