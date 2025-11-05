<?php

declare(strict_types=1);

namespace Tests\Factory\Domain;

use PHPUnit\Framework\TestCase;
use Tests\Fixtures\HelloWorldHandler;
use YSOCode\Berry\Domain\Entities\Route;
use YSOCode\Berry\Domain\Entities\RouteContext;
use YSOCode\Berry\Domain\Entities\RouteContextFactory;
use YSOCode\Berry\Domain\Entities\RouteParser;
use YSOCode\Berry\Domain\Entities\RouteRegistry;
use YSOCode\Berry\Domain\Enums\HttpMethod;
use YSOCode\Berry\Domain\Types\RequestHandler;
use YSOCode\Berry\Domain\Types\RoutePathPattern;
use YSOCode\Berry\Infra\Http\ServerRequest;
use YSOCode\Berry\Infra\Http\UriFactory;

final class RouteContextFactoryTest extends TestCase
{
    public function test_it_should_create_a_route_context_from_request(): void
    {
        $route = new Route(
            HttpMethod::GET,
            new RoutePathPattern('/'),
            new RequestHandler(HelloWorldHandler::class)
        );

        $routeParser = new RouteParser(new RouteRegistry);

        $routeContext = new RouteContextFactory()->createFromRequest(
            new ServerRequest(
                HttpMethod::GET,
                new UriFactory()->createFromString('https://example.com')
            )
                ->withAttribute(RouteContext::ATTRIBUTE_ROUTE, $route)
                ->withAttribute(RouteContext::ATTRIBUTE_ROUTE_PARSER, $routeParser)
        );

        $this->assertInstanceOf(RouteContext::class, $routeContext);
        $this->assertSame($route, $routeContext->route);
        $this->assertSame($routeParser, $routeContext->routeParser);
    }
}
