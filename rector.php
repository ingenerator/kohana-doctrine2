<?php
declare(strict_types=1);

use Rector\PHPUnit\CodeQuality\Rector\MethodCall\UseSpecificWillMethodRector;
use Rector\Config\RectorConfig;
use Rector\PHPUnit\Set\PHPUnitSetList;

return RectorConfig::configure()
    ->withPaths([
        __DIR__.'/test',
    ])
    ->withSets([
        PHPUnitSetList::COMPOSER_BASED,
    ])
    ->withRules([
        UseSpecificWillMethodRector::class,
    ])
    ->withRootFiles()
    ->withImportNames(
        removeUnusedImports: true,
    );
