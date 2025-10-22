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
use YSOCode\Berry\Domain\ValueObjects\UriPath;

final class RouteCollectionTest extends TestCase
{
    public function test_it_should_add_a_route(): void
    {
        $routeCollection = new RouteCollection;

        $putProfileRoute = new Route(HttpMethod::PUT, new RoutePathPattern('/user/{user}/profile'), HelloWorldHandler::class);
        $getUserRoute = new Route(HttpMethod::GET, new RoutePathPattern('/user/{user}'), HelloWorldHandler::class);
        $getArticleRoute = new Route(HttpMethod::GET, new RoutePathPattern('/article/{slug}'), HelloWorldHandler::class);

        $routeCollection->addRoute($putProfileRoute);
        $routeCollection->addRoute($getUserRoute);
        $routeCollection->addRoute($getArticleRoute);

        $reflection = new ReflectionObject($routeCollection);
        $routeBySegments = $reflection->getProperty('routeBySegments');
        $routeBySegmentsValue = $routeBySegments->getValue($routeCollection);

        $expected = [
            '/' => [
                'children' => [
                    'user' => [
                        'children' => [
                            '{user}' => [
                                'children' => [
                                    'profile' => [
                                        'children' => [],
                                        'route' => $putProfileRoute,
                                    ],
                                ],
                                'route' => $getUserRoute,
                            ],
                        ],
                        'route' => null,
                    ],
                    'article' => [
                        'children' => [
                            '{slug}' => [
                                'children' => [],
                                'route' => $getArticleRoute,
                            ],
                        ],
                        'route' => null,
                    ],
                ],
                'route' => null,
            ],
        ];

        $this->assertSame($expected, $routeBySegmentsValue);
    }

    public function test_it_should_get_a_route_by_path(): void
    {
        $routeCollection = new RouteCollection;

        $putProfileRoute = new Route(HttpMethod::PUT, new RoutePathPattern('/user/{user}/profile'), HelloWorldHandler::class);
        $getUserRoute = new Route(HttpMethod::GET, new RoutePathPattern('/user/{user}'), HelloWorldHandler::class);
        $getArticleRoute = new Route(HttpMethod::GET, new RoutePathPattern('/article/{slug}'), HelloWorldHandler::class);

        $routeCollection->addRoute($putProfileRoute);
        $routeCollection->addRoute($getUserRoute);
        $routeCollection->addRoute($getArticleRoute);

        $actualPutProfileRoute = $routeCollection->getRouteByPath(new UriPath('/user/8847/profile'));
        $actualGetUserRoute = $routeCollection->getRouteByPath(new UriPath('/user/42'));
        $actualGetArticleRoute = $routeCollection->getRouteByPath(new UriPath('/article/example-slug'));

        $this->assertSame($putProfileRoute, $actualPutProfileRoute);
        $this->assertSame($getUserRoute, $actualGetUserRoute);
        $this->assertSame($getArticleRoute, $actualGetArticleRoute);
    }
}
