<?php

declare(strict_types=1);

namespace Tests\Unit\Domain;

use PHPUnit\Framework\TestCase;
use ReflectionObject;
use Tests\Fixtures\HelloWorldHandler;
use YSOCode\Berry\Domain\Entities\Route;
use YSOCode\Berry\Domain\Entities\RouteCollection;
use YSOCode\Berry\Domain\Enums\HttpMethod;
use YSOCode\Berry\Domain\ValueObjects\RoutePathPattern;

final class RouteCollectionTest extends TestCase
{
    public function test_it_should_add_a_route(): void
    {
        $routeCollection = new RouteCollection;

        $putProfile = new Route([HttpMethod::PUT], new RoutePathPattern('/user/{user}/profile'), HelloWorldHandler::class);
        $getUser = new Route([HttpMethod::GET], new RoutePathPattern('/user/{user}'), HelloWorldHandler::class);
        $deleteUser = new Route([HttpMethod::DELETE], new RoutePathPattern('/user/{user}'), HelloWorldHandler::class);
        $getArticle = new Route([HttpMethod::GET], new RoutePathPattern('/article/{slug:[a-z\-]+}'), HelloWorldHandler::class);

        $routeCollection->addRoute($putProfile);
        $routeCollection->addRoute($getUser);
        $routeCollection->addRoute($deleteUser);
        $routeCollection->addRoute($getArticle);

        $reflection = new ReflectionObject($routeCollection);
        $routeBySegments = $reflection->getProperty('routeBySegments');
        $routeBySegmentsValue = $routeBySegments->getValue($routeCollection);

        $expected = [
            'user' => [
                'children' => [
                    '{user}' => [
                        'children' => [
                            'profile' => [
                                'children' => [],
                                'routes' => [$putProfile],
                            ],
                        ],
                        'routes' => [$getUser, $deleteUser],
                    ],
                ],
                'routes' => [],
            ],
            'article' => [
                'children' => [
                    '{slug:[a-z\-]+}' => [
                        'children' => [],
                        'routes' => [$getArticle],
                    ],
                ],
                'routes' => [],
            ],
        ];

        $this->assertSame($expected, $routeBySegmentsValue);
    }
}
