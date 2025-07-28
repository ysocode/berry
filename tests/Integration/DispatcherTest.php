<?php

declare(strict_types=1);

namespace Tests\Integration;

use Closure;
use PHPUnit\Framework\TestCase;
use Tests\Fixtures\DummyController;
use Tests\Fixtures\DummyMiddleware;
use YSOCode\Berry\Dispatcher;
use YSOCode\Berry\Handler;
use YSOCode\Berry\Method;
use YSOCode\Berry\Middleware;
use YSOCode\Berry\Name;
use YSOCode\Berry\Path;
use YSOCode\Berry\Request;
use YSOCode\Berry\Response;
use YSOCode\Berry\Route;
use YSOCode\Berry\Router;
use YSOCode\Berry\Status;

final class DispatcherTest extends TestCase
{
    public function test_it_dispatches_request_to_correct_handler(): void
    {
        $router = new Router;

        $handler = fn (Request $request): Response => new Response(Status::OK, 'Hello from handler');
        $router->get(new Path('/hello'), $handler);

        $dispatcher = new Dispatcher($router);
        $response = $dispatcher->dispatch(new Request(Method::GET, new Path('/hello')));

        $this->assertEquals(Status::OK, $response->status);
        $this->assertEquals('Hello from handler', $response->body);
    }

    public function test_it_returns_404_when_route_is_not_found(): void
    {
        $router = new Router;
        $dispatcher = new Dispatcher($router);

        $response = $dispatcher->dispatch(new Request(Method::GET, new Path('/not-found')));

        $this->assertEquals(Status::NOT_FOUND, $response->status);
        $this->assertEquals('Route not found.', $response->body);
    }

    public function test_it_returns_405_when_method_is_not_allowed(): void
    {
        $router = new Router;
        $router->get(new Path('/hello'), fn (Request $request): Response => new Response(Status::OK, 'GET handler'));

        $dispatcher = new Dispatcher($router);
        $response = $dispatcher->dispatch(new Request(Method::POST, new Path('/hello')));

        $this->assertEquals(Status::METHOD_NOT_ALLOWED, $response->status);
        $this->assertEquals('Method not allowed.', $response->body);
    }

    public function test_it_matches_correct_route_among_multiple(): void
    {
        $router = new Router;
        $router->get(new Path('/hello'), fn (Request $request): Response => new Response(Status::OK, 'GET'));
        $router->post(new Path('/hello'), fn (Request $request): Response => new Response(Status::OK, 'POST'));

        $dispatcher = new Dispatcher($router);
        $response = $dispatcher->dispatch(new Request(Method::POST, new Path('/hello')));

        $this->assertEquals(Status::OK, $response->status);
        $this->assertEquals('POST', $response->body);
    }

    public function test_it_matches_nested_paths(): void
    {
        $router = new Router;
        $router->get(new Path('/user/profile'), fn (Request $request): Response => new Response(Status::OK, 'Profile'));

        $dispatcher = new Dispatcher($router);
        $response = $dispatcher->dispatch(new Request(Method::GET, new Path('/user/profile')));

        $this->assertEquals(Status::OK, $response->status);
        $this->assertEquals('Profile', $response->body);
    }

    public function test_it_returns_500_when_handler_does_not_return_response(): void
    {
        $router = new Router;
        $router->get(new Path('/broken'), fn (): string => 'not a response');

        $dispatcher = new Dispatcher($router);
        $response = $dispatcher->dispatch(new Request(Method::GET, new Path('/broken')));

        $this->assertEquals(Status::INTERNAL_SERVER_ERROR, $response->status);
        $this->assertEquals('Handler did not return a valid response.', $response->body);
    }

    public function test_it_allows_same_path_with_different_methods(): void
    {
        $router = new Router;
        $router->get(new Path('/multi'), fn (Request $request): Response => new Response(Status::OK, 'GET'));
        $router->post(new Path('/multi'), fn (Request $request): Response => new Response(Status::OK, 'POST'));

        $dispatcher = new Dispatcher($router);

        $getResponse = $dispatcher->dispatch(new Request(Method::GET, new Path('/multi')));
        $postResponse = $dispatcher->dispatch(new Request(Method::POST, new Path('/multi')));

        $this->assertEquals('GET', $getResponse->body);
        $this->assertEquals('POST', $postResponse->body);
    }

    public function test_it_handles_paths_with_hyphens(): void
    {
        $router = new Router;
        $router->get(new Path('/user-profile/view'), fn (Request $request): Response => new Response(Status::OK, 'Hyphen'));

        $dispatcher = new Dispatcher($router);
        $response = $dispatcher->dispatch(new Request(Method::GET, new Path('/user-profile/view')));

        $this->assertEquals(Status::OK, $response->status);
        $this->assertEquals('Hyphen', $response->body);
    }

    public function test_it_handles_redirect_with_manual_url_and_named_route(): void
    {
        $router = new Router;

        $router->get(
            new Path('/new-location'),
            fn (Request $request): Response => new Response(Status::OK, 'New location'),
            new Name('new.location')
        );

        $router->get(
            new Path('/redirect-manual'),
            fn (Request $request): Response => new Response(Status::MOVED_PERMANENTLY, null, [
                'Location' => 'https://example.com/manual-url',
            ])
        );

        $router->get(new Path('/redirect-named'), function () use ($router): Response {
            $targetRoute = $router->getRouteByName(new Name('new.location'));
            $location = $targetRoute instanceof Route ? (string) $targetRoute->path : '/fallback';

            return new Response(Status::MOVED_PERMANENTLY, null, [
                'Location' => $location,
            ]);
        });

        $dispatcher = new Dispatcher($router);

        $responseManual = $dispatcher->dispatch(new Request(Method::GET, new Path('/redirect-manual')));
        $responseNamed = $dispatcher->dispatch(new Request(Method::GET, new Path('/redirect-named')));

        $this->assertEquals(Status::MOVED_PERMANENTLY, $responseManual->status);
        $this->assertNull($responseManual->body);
        $this->assertEquals('https://example.com/manual-url', $responseManual->headers['Location']);

        $this->assertEquals(Status::MOVED_PERMANENTLY, $responseNamed->status);
        $this->assertNull($responseNamed->body);
        $this->assertEquals('/new-location', $responseNamed->headers['Location']);
    }

    public function test_it_dispatches_handler_defined_as_value_object(): void
    {
        $router = new Router;

        $router->get(new Path('/handler'), new Handler(DummyController::class, 'index'));

        $dispatcher = new Dispatcher($router);

        $response = $dispatcher->dispatch(new Request(Method::GET, new Path('/handler')));

        $this->assertEquals(Status::OK, $response->status);
        $this->assertEquals('ok', $response->body);
    }

    public function test_it_dispatches_with_one_global_middleware(): void
    {
        $router = new Router;

        $router->get(new Path('/one'), fn (Request $request): Response => new Response(Status::OK, 'handler'));

        $dispatcher = new Dispatcher($router);

        $dispatcher->addMiddleware(function (Request $request, Closure $next): Response {
            $response = $next($request);

            return new Response($response->status, 'mw1 > '.$response->body);
        });

        $response = $dispatcher->dispatch(new Request(Method::GET, new Path('/one')));

        $this->assertSame('mw1 > handler', $response->body);
    }

    public function test_it_dispatches_with_multiple_global_middlewares_in_order(): void
    {
        $router = new Router;

        $router->get(new Path('/multi'), fn (Request $request): Response => new Response(Status::OK, 'handler'));

        $dispatcher = new Dispatcher($router);

        $dispatcher->addMiddleware(function (Request $request, Closure $next): Response {
            $response = $next($request);

            return new Response($response->status, 'mw1 > '.$response->body);
        });

        $dispatcher->addMiddleware(function (Request $request, Closure $next): Response {
            $response = $next($request);

            return new Response($response->status, 'mw2 > '.$response->body);
        });

        $response = $dispatcher->dispatch(new Request(Method::GET, new Path('/multi')));

        $this->assertSame('mw1 > mw2 > handler', $response->body);
    }

    public function test_it_stops_middleware_chain_if_response_returned_early(): void
    {
        $router = new Router;

        $router->get(new Path('/stop'), fn (Request $r): Response => new Response(Status::OK, 'handler'));

        $dispatcher = new Dispatcher($router);

        $dispatcher->addMiddleware(fn (Request $request, Closure $next): Response => new Response(Status::FORBIDDEN, 'blocked by mw'));

        $dispatcher->addMiddleware(function (Request $request, Closure $next): Response {
            $response = $next($request);

            return new Response($response->status, 'mw2 > '.$response->body);
        });

        $response = $dispatcher->dispatch(new Request(Method::GET, new Path('/stop')));

        $this->assertSame(Status::FORBIDDEN, $response->status);
        $this->assertSame('blocked by mw', $response->body);
    }

    public function test_it_returns_500_when_middleware_does_not_return_response(): void
    {
        $router = new Router;

        $router->get(new Path('/hello'), fn (Request $request): Response => new Response(Status::OK, 'OK'));

        $dispatcher = new Dispatcher($router);

        $dispatcher->addMiddleware(function (Request $request, Closure $next): void {
            $next($request);
        });

        $response = $dispatcher->dispatch(new Request(Method::GET, new Path('/hello')));

        $this->assertSame(Status::INTERNAL_SERVER_ERROR, $response->status);
        $this->assertSame('Middleware chain did not return a valid response.', $response->body);
    }

    public function test_it_accepts_middleware_as_value_object(): void
    {
        $router = new Router;
        $router->get(new Path('/test'), fn (Request $request): Response => new Response(Status::OK, 'ok'));

        $dispatcher = new Dispatcher($router);

        $dispatcher->addMiddleware(new Middleware(DummyMiddleware::class, 'execute'));
        $response = $dispatcher->dispatch(new Request(Method::GET, new Path('/test')));

        $this->assertSame('dummy execute > ok', $response->body);
    }
}
