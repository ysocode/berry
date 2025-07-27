<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use YSOCode\Berry\Dispatcher;
use YSOCode\Berry\Method;
use YSOCode\Berry\Path;
use YSOCode\Berry\Request;
use YSOCode\Berry\Response;
use YSOCode\Berry\Router;
use YSOCode\Berry\Status;

class DispatcherTest extends TestCase
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
}
