<?php

declare(strict_types=1);

namespace Tests\Unit\Application;

use PHPUnit\Framework\TestCase;
use Tests\Fixtures\HelloWorldHandler;
use YSOCode\Berry\Application\RouteResolver;
use YSOCode\Berry\Domain\Entities\RouteRegistry;
use YSOCode\Berry\Domain\Enums\HttpMethod;
use YSOCode\Berry\Domain\Payloads\ResolvedRoute;
use YSOCode\Berry\Domain\ValueObjects\RequestHandler;
use YSOCode\Berry\Domain\ValueObjects\RoutePathPattern;
use YSOCode\Berry\Infra\Http\ServerRequest;
use YSOCode\Berry\Infra\Http\UriFactory;

final class RouteResolverTest extends TestCase
{
    public function test_it_should_resolve_a_route(): void
    {
        $routeRegistry = new RouteRegistry;

        $routeRegistry->map(
            HttpMethod::GET,
            new RoutePathPattern('/users/{user}/posts/{post}'),
            new RequestHandler(HelloWorldHandler::class)
        );

        $resolver = new RouteResolver($routeRegistry);

        $request = new ServerRequest(
            HttpMethod::GET,
            new UriFactory()->createFromString('https://example.com/users/42/posts/99')
        );

        $resolvedRoute = $resolver->resolve($request);

        $expectedParameters = ['user' => '42', 'post' => '99'];

        $this->assertInstanceOf(ResolvedRoute::class, $resolvedRoute);
        $this->assertSame($expectedParameters, $resolvedRoute->parameters);
    }
}
