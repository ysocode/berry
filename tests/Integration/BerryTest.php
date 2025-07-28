<?php

declare(strict_types=1);

namespace Tests\Integration;

use DI\Container;
use PHPUnit\Framework\TestCase;
use Tests\Fixtures\DummyController;
use YSOCode\Berry\Berry;
use YSOCode\Berry\Dispatcher;
use YSOCode\Berry\Handler;
use YSOCode\Berry\Method;
use YSOCode\Berry\Path;
use YSOCode\Berry\Request;
use YSOCode\Berry\Router;

final class BerryTest extends TestCase
{
    private Container $container;

    protected function setUp(): void
    {
        parent::setUp();
        $this->container = new Container;
    }

    public function test_it_resolves_string_handler_using_container(): void
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
}
