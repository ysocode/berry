<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\Php80\Rector\Class_\ClassPropertyAssignToConstructorPromotionRector;
use Rector\Php81\Rector\ClassMethod\NewInInitializerRector;
use Rector\TypeDeclaration\Rector\StmtsAwareInterface\DeclareStrictTypesRector;

return RectorConfig::configure()
    ->withPaths([
        __DIR__.'/src',
        __DIR__.'/tests',
    ])
    ->withRules([
        DeclareStrictTypesRector::class,
    ])
    ->withImportNames()
    ->withPreparedSets(
        deadCode: true,
        codeQuality: true,
        codingStyle: true,
        typeDeclarations: true,
        privatization: true,
        earlyReturn: true,
    )
    ->withPhpSets()
    ->withSkip([
        ClassPropertyAssignToConstructorPromotionRector::class => [
            __DIR__.'/src/Application/Berry.php',
            __DIR__.'/src/Domain/Entities/RouteGroup.php',
            __DIR__.'/src/Infra/Stream/Stream.php',
        ],
        NewInInitializerRector::class => [
            __DIR__.'/src/Application/Berry.php',
            __DIR__.'/src/Domain/Entities/RouteGroup.php',
        ],
    ]);
