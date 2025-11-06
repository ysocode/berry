<?php

declare(strict_types=1);

namespace Tests\Unit\Application;

use PHPUnit\Framework\TestCase;
use Tests\Fixtures\HelloWorldHandler;
use YSOCode\Berry\Application\RouteResolver;
use YSOCode\Berry\Domain\Entities\RouteRegistry;
use YSOCode\Berry\Domain\Enums\HttpMethod;
use YSOCode\Berry\Domain\Payloads\ResolvedRoute;
use YSOCode\Berry\Domain\Types\PathParameter;
use YSOCode\Berry\Domain\Types\PathParameterName;
use YSOCode\Berry\Domain\Types\RequestHandler;
use YSOCode\Berry\Domain\Types\RoutePathPattern;
use YSOCode\Berry\Domain\Types\UriPath;
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

        $this->assertInstanceOf(ResolvedRoute::class, $resolvedRoute);

        $userParameter = $resolvedRoute->getParameter(new PathParameterName('user'));
        $postParameter = $resolvedRoute->getParameter(new PathParameterName('post'));

        $this->assertInstanceOf(PathParameter::class, $userParameter);
        $this->assertEquals('user', (string) $userParameter->name);
        $this->assertEquals('42', $userParameter->value);
        $this->assertInstanceOf(PathParameter::class, $postParameter);
        $this->assertEquals('post', (string) $postParameter->name);
        $this->assertEquals('99', $postParameter->value);
    }

    public function test_it_should_resolve_a_route_with_base_path(): void
    {
        $routeRegistry = new RouteRegistry;

        $routeRegistry->map(
            HttpMethod::GET,
            new RoutePathPattern('/users/{user}/posts/{post}'),
            new RequestHandler(HelloWorldHandler::class)
        );

        $resolver = new RouteResolver($routeRegistry);
        $resolver->setBasePath(new UriPath('/api/v1'));

        $request = new ServerRequest(
            HttpMethod::GET,
            new UriFactory()->createFromString('https://example.com/api/v1/users/42/posts/99')
        );

        $resolvedRoute = $resolver->resolve($request);

        $this->assertInstanceOf(ResolvedRoute::class, $resolvedRoute);

        $userParameter = $resolvedRoute->getParameter(new PathParameterName('user'));
        $postParameter = $resolvedRoute->getParameter(new PathParameterName('post'));

        $this->assertInstanceOf(PathParameter::class, $userParameter);
        $this->assertEquals('user', (string) $userParameter->name);
        $this->assertEquals('42', $userParameter->value);
        $this->assertInstanceOf(PathParameter::class, $postParameter);
        $this->assertEquals('post', (string) $postParameter->name);
        $this->assertEquals('99', $postParameter->value);
    }
}
