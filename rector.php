<?php declare(strict_types=1);

use Rector\Config\RectorConfig;

return RectorConfig::configure()
    ->withPaths([
        __DIR__.'/config',
        __DIR__.'/src',
        __DIR__.'/tests',
    ])
    ->withImportNames(importShortClasses: false)
    ->withComposerBased()
    ->withPhpSets()
    ->withPHPStanConfigs([__DIR__.'/phpstan.neon'])
    ->withPreparedSets(codeQuality: true, phpunitCodeQuality: true)
;
