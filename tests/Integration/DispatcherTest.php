<?php

declare(strict_types=1);

namespace Tests\Integration;

use Closure;
use DI\ContainerBuilder;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Tests\Fixtures\DummyController;
use Tests\Fixtures\DummyMiddleware;
use YSOCode\Berry\Application\Dispatcher;
use YSOCode\Berry\Application\Router;
use YSOCode\Berry\Domain\Entities\Route;
use YSOCode\Berry\Domain\ValueObjects\Handler;
use YSOCode\Berry\Domain\ValueObjects\Header;
use YSOCode\Berry\Domain\ValueObjects\HeaderName;
use YSOCode\Berry\Domain\ValueObjects\Method;
use YSOCode\Berry\Domain\ValueObjects\Middleware;
use YSOCode\Berry\Domain\ValueObjects\Name;
use YSOCode\Berry\Domain\ValueObjects\Path;
use YSOCode\Berry\Domain\ValueObjects\Status;
use YSOCode\Berry\Infra\Request;
use YSOCode\Berry\Infra\Response;
use YSOCode\Berry\Infra\StreamFactory;
use YSOCode\Berry\Infra\Uri;

final class DispatcherTest extends TestCase
{
    private ContainerInterface $container;

    private Uri $uri;

    protected function setUp(): void
    {
        parent::setUp();

        $builder = new ContainerBuilder;
        $builder->useAutowiring(true);

        $this->container = $builder->build();

        $this->uri = new Uri('https://example.com');
    }

    public function test_it_should_dispatch_request_to_correct_handler(): void
    {
        $router = new Router;

        $handler = fn (Request $request): Response => new Response(Status::OK, [], new StreamFactory()->createFromString('Hello from handler'));
        $router->get(new Path('/hello'), $handler);

        $dispatcher = new Dispatcher($router);
        $response = $dispatcher->dispatch(new Request(Method::GET, $this->uri->withPath(new Path('/hello'))), $this->container);

        $this->assertEquals(Status::OK, $response->status);
        $this->assertEquals('Hello from handler', (string) $response->body);
    }

    public function test_it_should_return_404_when_route_is_not_found(): void
    {
        $router = new Router;
        $dispatcher = new Dispatcher($router);

        $response = $dispatcher->dispatch(new Request(Method::GET, $this->uri->withPath(new Path('/not-found'))), $this->container);

        $this->assertEquals(Status::NOT_FOUND, $response->status);
        $this->assertEquals('Route not found.', (string) $response->body);
    }

    public function test_it_should_return_405_when_method_is_not_allowed(): void
    {
        $router = new Router;
        $router->get(new Path('/hello'), fn (Request $request): Response => new Response(Status::OK, [], new StreamFactory()->createFromString('GET handler')));

        $dispatcher = new Dispatcher($router);
        $response = $dispatcher->dispatch(new Request(Method::POST, $this->uri->withPath(new Path('/hello'))), $this->container);

        $this->assertEquals(Status::METHOD_NOT_ALLOWED, $response->status);
        $this->assertEquals('Method not allowed.', (string) $response->body);
    }

    public function test_it_should_match_correct_route_among_multiple(): void
    {
        $router = new Router;
        $router->get(new Path('/hello'), fn (Request $request): Response => new Response(Status::OK, [], new StreamFactory()->createFromString('GET')));
        $router->post(new Path('/hello'), fn (Request $request): Response => new Response(Status::OK, [], new StreamFactory()->createFromString('POST')));

        $dispatcher = new Dispatcher($router);
        $response = $dispatcher->dispatch(new Request(Method::POST, $this->uri->withPath(new Path('/hello'))), $this->container);

        $this->assertEquals(Status::OK, $response->status);
        $this->assertEquals('POST', (string) $response->body);
    }

    public function test_it_should_match_nested_paths(): void
    {
        $router = new Router;
        $router->get(new Path('/user/profile'), fn (Request $request): Response => new Response(Status::OK, [], new StreamFactory()->createFromString('Profile')));

        $dispatcher = new Dispatcher($router);
        $response = $dispatcher->dispatch(new Request(Method::GET, $this->uri->withPath(new Path('/user/profile'))), $this->container);

        $this->assertEquals(Status::OK, $response->status);
        $this->assertEquals('Profile', (string) $response->body);
    }

    public function test_it_should_return_500_when_handler_does_not_return_response(): void
    {
        $router = new Router;

        /** @phpstan-ignore-next-line */
        $router->get(new Path('/broken'), fn (Request $request): string => 'not a response');

        $dispatcher = new Dispatcher($router);
        $response = $dispatcher->dispatch(new Request(Method::GET, $this->uri->withPath(new Path('/broken'))), $this->container);

        $this->assertEquals(Status::INTERNAL_SERVER_ERROR, $response->status);
        $this->assertEquals('Handler did not return a valid response.', (string) $response->body);
    }

    public function test_it_should_allow_same_path_with_different_methods(): void
    {
        $router = new Router;
        $router->get(new Path('/multi'), fn (Request $request): Response => new Response(Status::OK, [], new StreamFactory()->createFromString('GET')));
        $router->post(new Path('/multi'), fn (Request $request): Response => new Response(Status::OK, [], new StreamFactory()->createFromString('POST')));

        $dispatcher = new Dispatcher($router);

        $getResponse = $dispatcher->dispatch(new Request(Method::GET, $this->uri->withPath(new Path('/multi'))), $this->container);
        $postResponse = $dispatcher->dispatch(new Request(Method::POST, $this->uri->withPath(new Path('/multi'))), $this->container);

        $this->assertEquals('GET', $getResponse->body);
        $this->assertEquals('POST', $postResponse->body);
    }

    public function test_it_should_handle_paths_with_hyphens(): void
    {
        $router = new Router;
        $router->get(new Path('/user-profile/view'), fn (Request $request): Response => new Response(Status::OK, [], new StreamFactory()->createFromString('Hyphen')));

        $dispatcher = new Dispatcher($router);
        $response = $dispatcher->dispatch(new Request(Method::GET, $this->uri->withPath(new Path('/user-profile/view'))), $this->container);

        $this->assertEquals(Status::OK, $response->status);
        $this->assertEquals('Hyphen', (string) $response->body);
    }

    public function test_it_should_handle_redirect_with_manual_url_and_named_route(): void
    {
        $router = new Router;

        $router->get(
            new Path('/new-location'),
            fn (Request $request): Response => new Response(Status::OK, [], new StreamFactory()->createFromString('New location')),
            new Name('new.location')
        );

        $router->get(
            new Path('/redirect-manual'),
            fn (Request $request): Response => new Response(Status::MOVED_PERMANENTLY, [
                new Header(new HeaderName('Location'), ['https://example.com/manual-url']),
            ])
        );

        $router->get(new Path('/redirect-named'), function () use ($router): Response {
            $targetRoute = $router->getRouteByName(new Name('new.location'));

            if (! $targetRoute instanceof Route) {
                return new Response(Status::NOT_FOUND, [], new StreamFactory()->createFromString('Target route not found.'));
            }

            $location = (string) $this->uri->withPath($targetRoute->path);

            return new Response(Status::MOVED_PERMANENTLY, [
                new Header(new HeaderName('Location'), [$location]),
            ]);
        });

        $dispatcher = new Dispatcher($router);

        $responseManual = $dispatcher->dispatch(new Request(Method::GET, $this->uri->withPath(new Path('/redirect-manual'))), $this->container);
        $responseNamed = $dispatcher->dispatch(new Request(Method::GET, $this->uri->withPath(new Path('/redirect-named'))), $this->container);

        $this->assertEquals(Status::MOVED_PERMANENTLY, $responseManual->status);
        $this->assertNull($responseManual->body);
        $this->assertEquals(['https://example.com/manual-url'], $responseManual->getHeader(new HeaderName('Location'))?->value);

        $this->assertEquals(Status::MOVED_PERMANENTLY, $responseNamed->status);
        $this->assertNull($responseNamed->body);
        $this->assertEquals(['https://example.com/new-location'], $responseNamed->getHeader(new HeaderName('Location'))?->value);
    }

    public function test_it_should_dispatch_handler_defined_as_value_object(): void
    {
        $router = new Router;

        $router->get(new Path('/handler'), new Handler(DummyController::class, 'index'));

        $dispatcher = new Dispatcher($router);

        $response = $dispatcher->dispatch(new Request(Method::GET, $this->uri->withPath(new Path('/handler'))), $this->container);

        $this->assertEquals(Status::OK, $response->status);
        $this->assertEquals('ok', (string) $response->body);
    }

    public function test_it_should_dispatch_with_one_global_middleware(): void
    {
        $router = new Router;

        $router->get(new Path('/one'), fn (Request $request): Response => new Response(Status::OK, [], new StreamFactory()->createFromString('handler')));

        $dispatcher = new Dispatcher($router);

        $dispatcher->addMiddleware(function (Request $request, Closure $next): Response {
            $response = $next($request);

            return new Response($response->status, [], new StreamFactory()->createFromString('mw1 > '.$response->body));
        });

        $response = $dispatcher->dispatch(new Request(Method::GET, $this->uri->withPath(new Path('/one'))), $this->container);

        $this->assertSame('mw1 > handler', (string) $response->body);
    }

    public function test_it_should_dispatch_with_multiple_global_middlewares_in_order(): void
    {
        $router = new Router;

        $router->get(new Path('/multi'), fn (Request $request): Response => new Response(Status::OK, [], new StreamFactory()->createFromString('handler')));

        $dispatcher = new Dispatcher($router);

        $dispatcher->addMiddleware(function (Request $request, Closure $next): Response {
            $response = $next($request);

            return new Response($response->status, [], new StreamFactory()->createFromString('mw1 > '.$response->body));
        });

        $dispatcher->addMiddleware(function (Request $request, Closure $next): Response {
            $response = $next($request);

            return new Response($response->status, [], new StreamFactory()->createFromString('mw2 > '.$response->body));
        });

        $response = $dispatcher->dispatch(new Request(Method::GET, $this->uri->withPath(new Path('/multi'))), $this->container);

        $this->assertSame('mw1 > mw2 > handler', (string) $response->body);
    }

    public function test_it_should_stop_middleware_chain_if_response_returned_early(): void
    {
        $router = new Router;

        $router->get(new Path('/stop'), fn (Request $r): Response => new Response(Status::OK, [], new StreamFactory()->createFromString('handler')));

        $dispatcher = new Dispatcher($router);

        $dispatcher->addMiddleware(fn (Request $request, Closure $next): Response => new Response(Status::FORBIDDEN, [], new StreamFactory()->createFromString('blocked by mw')));

        $dispatcher->addMiddleware(function (Request $request, Closure $next): Response {
            $response = $next($request);

            return new Response($response->status, [], new StreamFactory()->createFromString('mw2 > '.$response->body));
        });

        $response = $dispatcher->dispatch(new Request(Method::GET, $this->uri->withPath(new Path('/stop'))), $this->container);

        $this->assertSame(Status::FORBIDDEN, $response->status);
        $this->assertSame('blocked by mw', (string) $response->body);
    }

    public function test_it_should_return_500_when_middleware_does_not_return_response(): void
    {
        $router = new Router;

        $router->get(new Path('/hello'), fn (Request $request): Response => new Response(Status::OK, [], new StreamFactory()->createFromString('OK')));

        $dispatcher = new Dispatcher($router);

        /** @phpstan-ignore-next-line */
        $dispatcher->addMiddleware(function (Request $request, Closure $next): void {
            $next($request);
        });

        $response = $dispatcher->dispatch(new Request(Method::GET, $this->uri->withPath(new Path('/hello'))), $this->container);

        $this->assertSame(Status::INTERNAL_SERVER_ERROR, $response->status);
        $this->assertSame('Middleware chain did not return a valid response.', (string) $response->body);
    }

    public function test_it_should_accept_middleware_as_value_object(): void
    {
        $router = new Router;
        $router->get(new Path('/test'), fn (Request $request): Response => new Response(Status::OK, [], new StreamFactory()->createFromString('ok')));

        $dispatcher = new Dispatcher($router);

        $dispatcher->addMiddleware(new Middleware(DummyMiddleware::class, 'execute'));
        $response = $dispatcher->dispatch(new Request(Method::GET, $this->uri->withPath(new Path('/test'))), $this->container);

        $this->assertSame('dummy execute > ok', (string) $response->body);
    }
}
