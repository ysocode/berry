<?php

declare(strict_types=1);

namespace Tests\Unit\Domain;

use PHPUnit\Framework\TestCase;
use ReflectionObject;
use RuntimeException;
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

        $putProfileRoute = new Route(HttpMethod::PUT, new RoutePathPattern('/users/{user}/profile'), HelloWorldHandler::class);
        $getUserRoute = new Route(HttpMethod::GET, new RoutePathPattern('/users/{user}'), HelloWorldHandler::class);
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
                    'users' => [
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

    public function test_it_should_return_a_route_when_exist(): void
    {
        $routeCollection = new RouteCollection;

        $putProfileRoute = new Route(HttpMethod::PUT, new RoutePathPattern('/users/{user}/profile'), HelloWorldHandler::class);
        $getUserRoute = new Route(HttpMethod::GET, new RoutePathPattern('/users/{user}'), HelloWorldHandler::class);
        $getArticleRoute = new Route(HttpMethod::GET, new RoutePathPattern('/article/{slug}'), HelloWorldHandler::class);

        $routeCollection->addRoute($putProfileRoute);
        $routeCollection->addRoute($getUserRoute);
        $routeCollection->addRoute($getArticleRoute);

        $actualPutProfileRoute = $routeCollection->getRouteByPath(new UriPath('/users/8847/profile'));
        $actualGetUserRoute = $routeCollection->getRouteByPath(new UriPath('/users/42'));
        $actualGetArticleRoute = $routeCollection->getRouteByPath(new UriPath('/article/example-slug'));

        $this->assertSame($putProfileRoute, $actualPutProfileRoute);
        $this->assertSame($getUserRoute, $actualGetUserRoute);
        $this->assertSame($getArticleRoute, $actualGetArticleRoute);
    }

    public function test_it_should_return_null_when_route_not_exist(): void
    {
        $routeCollection = new RouteCollection;

        $this->assertNull($routeCollection->getRouteByPath(new UriPath('/')));
    }

    public function test_it_should_reject_duplicated_path_pattern(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Route conflict: /users/{user}');

        $routeCollection = new RouteCollection;

        $getUserRoute = new Route(HttpMethod::GET, new RoutePathPattern('/users/{user}'), HelloWorldHandler::class);
        $deleteUserRoute = new Route(HttpMethod::DELETE, new RoutePathPattern('/users/{user}'), HelloWorldHandler::class);

        $routeCollection->addRoute($getUserRoute);
        $routeCollection->addRoute($deleteUserRoute);
    }
}
