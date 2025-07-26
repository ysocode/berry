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

        $handler = new class
        {
            public function handle(Request $request): Response
            {
                return new Response(Status::OK, 'Hello from handler');
            }
        };

        $router->get(new Path('/hello'), $handler->handle(...));

        $dispatcher = new Dispatcher($router);

        $request = new Request(Method::GET, new Path('/hello'));
        $response = $dispatcher->dispatch($request);

        $this->assertEquals(Status::OK, $response->status);
        $this->assertEquals('Hello from handler', $response->body);
    }
}
