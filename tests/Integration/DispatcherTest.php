<?php

declare(strict_types=1);

namespace Tests\Integration;

use LogicException;
use PHPUnit\Framework\TestCase;
use YSOCode\Berry\Dispatcher;
use YSOCode\Berry\Method;
use YSOCode\Berry\Path;
use YSOCode\Berry\Request;
use YSOCode\Berry\Response;
use YSOCode\Berry\Router;
use YSOCode\Berry\Status;

final class DispatcherTest extends TestCase
{
    public function test_it_dispatches_request_to_correct_handler(): void
    {
        $router = new Router;

        $handler = fn (): Response => new Response(Status::OK, 'Hello from handler');

        $router->get(new Path('/hello'), $handler);

        $dispatcher = new Dispatcher($router);

        $request = new Request(Method::GET, new Path('/hello'));
        $response = $dispatcher->dispatch($request);

        $this->assertEquals(Status::OK, $response->status);
        $this->assertEquals('Hello from handler', $response->body);
    }

    public function test_it_returns_404_when_route_is_not_found(): void
    {
        $router = new Router;
        $dispatcher = new Dispatcher($router);

        $request = new Request(Method::GET, new Path('/not-found'));
        $response = $dispatcher->dispatch($request);

        $this->assertEquals(Status::NOT_FOUND, $response->status);
        $this->assertEquals('Route not found.', $response->body);
    }

    public function test_it_returns_405_when_method_is_not_allowed(): void
    {
        $router = new Router;
        $router->get(new Path('/hello'), fn (): Response => new Response(Status::OK, 'GET handler'));

        $dispatcher = new Dispatcher($router);
        $request = new Request(Method::POST, new Path('/hello'));
        $response = $dispatcher->dispatch($request);

        $this->assertEquals(Status::METHOD_NOT_ALLOWED, $response->status);
        $this->assertEquals('Method not allowed.', $response->body);
    }

    public function test_it_matches_correct_route_among_multiple(): void
    {
        $router = new Router;
        $router->get(new Path('/hello'), fn (): Response => new Response(Status::OK, 'GET'));
        $router->post(new Path('/hello'), fn (): Response => new Response(Status::OK, 'POST'));

        $dispatcher = new Dispatcher($router);
        $request = new Request(Method::POST, new Path('/hello'));
        $response = $dispatcher->dispatch($request);

        $this->assertEquals(Status::OK, $response->status);
        $this->assertEquals('POST', $response->body);
    }

    public function test_it_matches_nested_paths(): void
    {
        $router = new Router;
        $router->get(new Path('/user/profile'), fn (): Response => new Response(Status::OK, 'Profile'));

        $dispatcher = new Dispatcher($router);
        $request = new Request(Method::GET, new Path('/user/profile'));
        $response = $dispatcher->dispatch($request);

        $this->assertEquals(Status::OK, $response->status);
        $this->assertEquals('Profile', $response->body);
    }

    public function test_it_returns_500_if_handler_does_not_return_response(): void
    {
        $router = new Router;
        $router->get(new Path('/broken'), fn (): string => 'not a response');

        $dispatcher = new Dispatcher($router);
        $request = new Request(Method::GET, new Path('/broken'));
        $response = $dispatcher->dispatch($request);

        $this->assertEquals(Status::INTERNAL_SERVER_ERROR, $response->status);
        $this->assertEquals('Handler did not return a valid response.', $response->body);
    }

    public function test_it_throws_exception_if_route_already_exists(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Route GET /duplicate already exists.');

        $router = new Router;
        $router->get(new Path('/duplicate'), fn (): Response => new Response(Status::OK, 'First'));
        $router->get(new Path('/duplicate'), fn (): Response => new Response(Status::OK, 'Second'));
    }

    public function test_it_allows_same_path_with_different_methods(): void
    {
        $router = new Router;
        $router->get(new Path('/multi'), fn (): Response => new Response(Status::OK, 'GET'));
        $router->post(new Path('/multi'), fn (): Response => new Response(Status::OK, 'POST'));

        $dispatcher = new Dispatcher($router);

        $getResponse = $dispatcher->dispatch(new Request(Method::GET, new Path('/multi')));
        $postResponse = $dispatcher->dispatch(new Request(Method::POST, new Path('/multi')));

        $this->assertEquals('GET', $getResponse->body);
        $this->assertEquals('POST', $postResponse->body);
    }

    public function test_it_handles_path_with_hyphens(): void
    {
        $router = new Router;
        $router->get(new Path('/user-profile/view'), fn (): Response => new Response(Status::OK, 'Hyphen'));

        $dispatcher = new Dispatcher($router);
        $response = $dispatcher->dispatch(new Request(Method::GET, new Path('/user-profile/view')));

        $this->assertEquals(Status::OK, $response->status);
        $this->assertEquals('Hyphen', $response->body);
    }
}
