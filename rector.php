<?php

declare(strict_types=1);

use Rector\CodeQuality\Rector\Class_\InlineConstructorDefaultToPropertyRector;
use Rector\Config\RectorConfig;
use Rector\Set\ValueObject\LevelSetList;
use Rector\Set\ValueObject\SetList;
use Rector\Laravel\Set\LaravelSetList;

return RectorConfig::configure()
    ->withPaths([
        __DIR__ . '/app',
        __DIR__ . '/config',
        __DIR__ . '/database',
        __DIR__ . '/routes',
        __DIR__ . '/tests',
    ])
    ->withSkip([
        __DIR__ . '/app/Http/Middleware/RedirectIfAuthenticated.php',
    ])
    ->withSets([
        SetList::CODE_QUALITY,      // Simplify expressions, remove dead code
        SetList::DEAD_CODE,         // Remove unreachable/unused code
        SetList::TYPE_DECLARATION,  // Add missing type hints
        LevelSetList::UP_TO_PHP_84, // Modernise to PHP 8.4 idioms
        LaravelSetList::LARAVEL_110,// Laravel-specific improvements
    ])
    ->withRules([
        InlineConstructorDefaultToPropertyRector::class,
    ]);
