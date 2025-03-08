<?php

declare(strict_types=1);

use PrismPHP\Prism\Rectors\ReorderMethodsRector;
use Rector\CodeQuality\Rector\Class_\InlineConstructorDefaultToPropertyRector;
use Rector\Config\RectorConfig;
use Rector\Set\ValueObject\LevelSetList;
use Rector\Set\ValueObject\SetList;

return RectorConfig::configure()
    ->withParallel()
    ->withPaths([
        __DIR__.'/src',
        __DIR__.'/tests',
    ])
    ->withRules([
        InlineConstructorDefaultToPropertyRector::class,
        ReorderMethodsRector::class,
    ])
    ->withSets([
        LevelSetList::UP_TO_PHP_83,
        SetList::CODE_QUALITY,
        SetList::DEAD_CODE,
        SetList::EARLY_RETURN,
        SetList::TYPE_DECLARATION,
        SetList::PRIVATIZATION,
    ]);
