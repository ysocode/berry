<?php

declare(strict_types=1);

namespace Tests\Integration;

use DI\Container;
use DI\ContainerBuilder;
use PHPUnit\Framework\TestCase;
use Tests\Fixtures\DummyController;
use Tests\Fixtures\DummyWithDependencyController;
use Tests\Fixtures\DummyWithDependencyMiddleware;
use YSOCode\Berry\Berry;
use YSOCode\Berry\Dispatcher;
use YSOCode\Berry\Handler;
use YSOCode\Berry\Method;
use YSOCode\Berry\Middleware;
use YSOCode\Berry\Path;
use YSOCode\Berry\Request;
use YSOCode\Berry\Response;
use YSOCode\Berry\Router;
use YSOCode\Berry\Status;

final class BerryTest extends TestCase
{
    private Container $container;

    protected function setUp(): void
    {
        parent::setUp();

        $builder = new ContainerBuilder;
        $builder->useAutowiring(true);

        $this->container = $builder->build();
    }

    public function test_berry_processes_request_and_sends_response(): void
    {
        $router = new Router;

        $router->get(new Path('/hello'), new Handler(DummyController::class, 'index'));

        $dispatcher = new Dispatcher($router);
        $berry = new Berry($dispatcher, $this->container);

        $request = new Request(Method::GET, new Path('/hello'));

        ob_start();
        $berry->run($request);
        $output = ob_get_clean();

        $this->assertSame('ok', $output);
        $this->assertSame(200, http_response_code());
    }

    public function test_handler_resolves_dependencies_via_container(): void
    {
        $router = new Router;

        $router->get(new Path('/handler'), new Handler(DummyWithDependencyController::class, 'index'));

        $dispatcher = new Dispatcher($router);
        $berry = new Berry($dispatcher, $this->container);

        $request = new Request(Method::GET, new Path('/handler'));

        ob_start();
        $berry->run($request);
        $output = ob_get_clean();

        $this->assertSame('Hello from service', $output);
        $this->assertSame(200, http_response_code());
    }

    public function test_middleware_resolves_dependencies_via_container(): void
    {
        $router = new Router;

        $router->get(new Path('/middleware'), fn (Request $request): Response => new Response(Status::OK, 'handler'));

        $dispatcher = new Dispatcher($router);

        $dispatcher->addMiddleware(new Middleware(DummyWithDependencyMiddleware::class, 'execute'));

        $berry = new Berry($dispatcher, $this->container);

        $request = new Request(Method::GET, new Path('/middleware'));

        ob_start();
        $berry->run($request);
        $output = ob_get_clean();

        $this->assertSame('Hello from service > handler', $output);
        $this->assertSame(200, http_response_code());
    }
}
