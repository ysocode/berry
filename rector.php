<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\DeadCode\Rector\ClassMethod\RemoveEmptyClassMethodRector;
use Rector\DeadCode\Rector\ClassMethod\RemoveUnusedConstructorParamRector;
use Rector\DeadCode\Rector\ClassMethod\RemoveUnusedPrivateMethodParameterRector;
use Rector\DeadCode\Rector\ClassMethod\RemoveUnusedPrivateMethodRector;
use Rector\DeadCode\Rector\ClassMethod\RemoveUnusedPublicMethodParameterRector;
use Rector\DeadCode\Rector\FunctionLike\RemoveDeadReturnRector;
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
        typeDeclarations: true,
        privatization: true,
        earlyReturn: true,
        strictBooleans: true
    )
    ->withSkip([
        RemoveUnusedConstructorParamRector::class => [
            __DIR__.'/tests/Fixtures/DummyController.php',
            __DIR__.'/tests/Fixtures/DummyMiddleware.php',
            __DIR__.'/tests/Fixtures/DummyWithDependencyController.php',
        ],
        RemoveUnusedPublicMethodParameterRector::class => [
            __DIR__.'/tests/Fixtures/DummyController.php',
            __DIR__.'/tests/Fixtures/DummyMiddleware.php',
            __DIR__.'/tests/Fixtures/DummyWithDependencyController.php',
        ],
        RemoveUnusedPrivateMethodParameterRector::class => [
            __DIR__.'/tests/Fixtures/DummyController.php',
            __DIR__.'/tests/Fixtures/DummyMiddleware.php',
            __DIR__.'/tests/Fixtures/DummyWithDependencyController.php',
        ],
        RemoveUnusedPrivateMethodRector::class => [
            __DIR__.'/tests/Fixtures/DummyController.php',
            __DIR__.'/tests/Fixtures/DummyMiddleware.php',
            __DIR__.'/tests/Fixtures/DummyWithDependencyController.php',
        ],
        RemoveEmptyClassMethodRector::class => [
            __DIR__.'/tests/Fixtures/DummyController.php',
            __DIR__.'/tests/Fixtures/DummyMiddleware.php',
            __DIR__.'/tests/Fixtures/DummyWithDependencyController.php',
        ],
        RemoveDeadReturnRector::class => [
            __DIR__.'/tests/Fixtures/DummyController.php',
            __DIR__.'/tests/Fixtures/DummyMiddleware.php',
            __DIR__.'/tests/Fixtures/DummyWithDependencyController.php',
        ],
    ])
    ->withPhpSets();
