<?php

declare(strict_types=1);

namespace Tests\Integration;

use DI\ContainerBuilder;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Tests\Fixtures\DummyController;
use Tests\Fixtures\DummyWithDependencyController;
use Tests\Fixtures\DummyWithDependencyMiddleware;
use YSOCode\Berry\Application\Berry;
use YSOCode\Berry\Application\Dispatcher;
use YSOCode\Berry\Application\Router;
use YSOCode\Berry\Domain\ValueObjects\Handler;
use YSOCode\Berry\Domain\ValueObjects\Method;
use YSOCode\Berry\Domain\ValueObjects\Middleware;
use YSOCode\Berry\Domain\ValueObjects\Path;
use YSOCode\Berry\Domain\ValueObjects\Status;
use YSOCode\Berry\Infra\Request;
use YSOCode\Berry\Infra\Response;

final class BerryTest extends TestCase
{
    private ContainerInterface $container;

    protected function setUp(): void
    {
        parent::setUp();

        $builder = new ContainerBuilder;
        $builder->useAutowiring(true);

        $this->container = $builder->build();
    }

    public function test_it_should_process_request_and_send_response(): void
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

    public function test_it_should_resolve_handler_dependencies_via_container(): void
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

    public function test_it_should_resolve_middleware_dependencies_via_container(): void
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
