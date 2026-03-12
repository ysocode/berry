<?php

declare(strict_types=1);

namespace Tests\Integration;

use DI\Container;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Tests\Fixtures\AuthOrderMiddleware;
use Tests\Fixtures\ContainerResolvedHandler;
use Tests\Fixtures\ContainerResolvedMessage;
use Tests\Fixtures\ContainerResolvedMiddleware;
use Tests\Fixtures\HelloWorldHandler;
use Tests\Fixtures\InspectRequestHandler;
use Tests\Fixtures\LoggingMiddleware;
use Tests\Fixtures\PermissionOrderMiddleware;
use Tests\Fixtures\PoweredByMiddleware;
use Tests\Fixtures\RoutePolicyOrderMiddleware;
use Tests\Fixtures\SessionOrderMiddleware;
use Tests\Fixtures\TraceLogOrderMiddleware;
use Tests\Traits\HeaderEmitterTrait;
use Tests\Traits\ServerEnvironmentSetupTrait;
use YSOCode\Berry\Application\Berry;
use YSOCode\Berry\Domain\Entities\Route;
use YSOCode\Berry\Domain\Entities\RouteContextFactory;
use YSOCode\Berry\Domain\Entities\RouteGroup;
use YSOCode\Berry\Domain\Entities\RouteParser;
use YSOCode\Berry\Domain\Enums\HttpStatus;
use YSOCode\Berry\Domain\Types\Attribute;
use YSOCode\Berry\Infra\Http\Response;
use YSOCode\Berry\Infra\Http\ResponseEmitter;
use YSOCode\Berry\Infra\Http\ResponseFactory;
use YSOCode\Berry\Infra\Http\ServerRequest;

final class BerryTest extends TestCase
{
    use HeaderEmitterTrait;
    use ServerEnvironmentSetupTrait;

    private Berry $berry;

    protected function setUp(): void
    {
        $this->initializeFakeEnvironment();

        $this->berry = $this->createBerry(new Container);
    }

    private function createBerry(Container $container): Berry
    {
        return new Berry(
            $container,
            responseEmitter: new ResponseEmitter($this->headerEmitter(...)),
        );
    }

    private function getEmittedStatus(): HttpStatus
    {
        $firstEmittedHeader = array_first($this->emittedHeaders);
        if (! is_array($firstEmittedHeader)) {
            throw new RuntimeException('No emitted headers found.');
        }

        $statusCode = $firstEmittedHeader['statusCode'] ?? null;
        if (! is_int($statusCode)) {
            throw new RuntimeException('Invalid emitted status code.');
        }

        return HttpStatus::from($statusCode);
    }

    public function test_it_should_run_a_route(): void
    {
        $this->berry->get('/', HelloWorldHandler::class);

        ob_start();
        $this->berry->run();
        $output = ob_get_clean();

        $status = $this->getEmittedStatus();

        $this->assertEquals(HttpStatus::OK, $status);
        $this->assertEquals('Hello, world!', $output);
    }

    public function test_it_should_resolve_route_handler_from_container_with_constructor_dependency(): void
    {
        $container = new Container;
        $container->set(ContainerResolvedMessage::class, new ContainerResolvedMessage('resolved-by-container'));

        $this->berry = $this->createBerry($container);

        $this->berry->get('/', ContainerResolvedHandler::class);

        ob_start();
        $this->berry->run();
        $output = ob_get_clean();

        $status = $this->getEmittedStatus();

        $this->assertEquals(HttpStatus::OK, $status);
        $this->assertEquals('resolved-by-container', $output);
    }

    public function test_it_should_resolve_route_middleware_from_container_with_constructor_dependency(): void
    {
        $container = new Container;
        $container->set(ContainerResolvedMessage::class, new ContainerResolvedMessage('resolved-by-route-middleware'));

        $this->berry = $this->createBerry($container);

        $this->berry->get('/', InspectRequestHandler::class)->appendMiddleware(ContainerResolvedMiddleware::class);

        ob_start();
        $this->berry->run();
        $output = ob_get_clean();

        $status = $this->getEmittedStatus();

        $this->assertEquals(HttpStatus::OK, $status);
        $this->assertEquals('Log: resolved-by-route-middleware. Powered by: Not powered.', $output);
    }

    public function test_it_should_handle_single_global_middleware(): void
    {
        $this->berry->appendMiddleware(LoggingMiddleware::class);

        $this->berry->get('/', InspectRequestHandler::class);

        ob_start();
        $this->berry->run();
        $output = ob_get_clean();

        $status = $this->getEmittedStatus();

        $this->assertEquals(HttpStatus::OK, $status);
        $this->assertEquals('Log: 1997-08-22 00:00:00. Powered by: Not powered.', $output);
    }

    public function test_it_should_handle_multiple_global_middlewares(): void
    {
        $this->berry->appendMiddlewares([LoggingMiddleware::class, PoweredByMiddleware::class]);

        $this->berry->get('/', InspectRequestHandler::class);

        ob_start();
        $this->berry->run();
        $output = ob_get_clean();

        $status = $this->getEmittedStatus();

        $this->assertEquals(HttpStatus::OK, $status);
        $this->assertEquals('Log: 1997-08-22 00:00:00. Powered by: Berry.', $output);
    }

    public function test_it_should_execute_middlewares_with_globals_before_group_and_route(): void
    {
        $this->berry->appendMiddlewares([
            SessionOrderMiddleware::class,
            TraceLogOrderMiddleware::class,
        ]);

        $this->berry->group(function (RouteGroup $group): void {
            $group->get('/', function (ServerRequest $request): Response {
                $orderAttribute = $request->getAttribute('middleware-order');
                $order = $orderAttribute->value ?? [];

                if (! is_array($order)) {
                    throw new RuntimeException('Invalid middleware-order attribute value.');
                }

                $json = json_encode($order);
                if (! is_string($json)) {
                    throw new RuntimeException('Failed to encode middleware-order as JSON.');
                }

                return new ResponseFactory()->createFromString($json);
            })->appendMiddleware(RoutePolicyOrderMiddleware::class);
        })->appendMiddlewares([
            AuthOrderMiddleware::class,
            PermissionOrderMiddleware::class,
        ]);

        ob_start();
        $this->berry->run();
        $output = ob_get_clean();

        $status = $this->getEmittedStatus();
        $expectedJson = json_encode(['session', 'trace-log', 'auth', 'permission', 'route-policy']);

        $this->assertEquals(HttpStatus::OK, $status);
        $this->assertEquals($expectedJson, $output);
    }

    public function test_it_should_handle_method_not_allowed_error(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';

        $this->berry->get('/', HelloWorldHandler::class);

        $this->berry->run();

        $status = $this->getEmittedStatus();

        $this->assertEquals(HttpStatus::METHOD_NOT_ALLOWED, $status);
    }

    public function test_it_should_handle_not_found_error(): void
    {
        $_SERVER['REQUEST_URI'] = '/path/to/resource?query=param';

        $this->berry->run();

        $status = $this->getEmittedStatus();

        $this->assertEquals(HttpStatus::NOT_FOUND, $status);
    }

    public function test_it_should_register_a_route_inside_a_group(): void
    {
        $this->berry->group(function (RouteGroup $group): void {
            $group->get('/', HelloWorldHandler::class);
        });

        ob_start();
        $this->berry->run();
        $output = ob_get_clean();

        $status = $this->getEmittedStatus();

        $this->assertEquals(HttpStatus::OK, $status);
        $this->assertEquals('Hello, world!', $output);
    }

    public function test_it_should_register_a_route_inside_a_group_with_single_middleware(): void
    {
        $this->berry->group(function (RouteGroup $group): void {
            $group->get('/', InspectRequestHandler::class);
        })->appendMiddleware(LoggingMiddleware::class);

        ob_start();
        $this->berry->run();
        $output = ob_get_clean();

        $status = $this->getEmittedStatus();

        $this->assertEquals(HttpStatus::OK, $status);
        $this->assertEquals('Log: 1997-08-22 00:00:00. Powered by: Not powered.', $output);
    }

    public function test_it_should_register_a_route_inside_a_group_with_multiple_middlewares(): void
    {
        $this->berry->group(function (RouteGroup $group): void {
            $group->get('/', InspectRequestHandler::class);
        })->appendMiddlewares([LoggingMiddleware::class, PoweredByMiddleware::class]);

        ob_start();
        $this->berry->run();
        $output = ob_get_clean();

        $status = $this->getEmittedStatus();

        $this->assertEquals(HttpStatus::OK, $status);
        $this->assertEquals('Log: 1997-08-22 00:00:00. Powered by: Berry.', $output);
    }

    public function test_it_should_register_a_route_inside_a_group_with_prefix(): void
    {
        $this->berry->group(function (RouteGroup $group): void {
            $group->get('/{user}/profile', HelloWorldHandler::class);
        })->addPrefix('/users');

        $_SERVER['REQUEST_URI'] = '/users/8847/profile?query=param';

        ob_start();
        $this->berry->run();
        $output = ob_get_clean();

        $status = $this->getEmittedStatus();

        $this->assertEquals(HttpStatus::OK, $status);
        $this->assertEquals('Hello, world!', $output);
    }

    public function test_it_should_resolve_a_route_with_parameters(): void
    {
        $this->berry->get('/users/{user}/posts/{post}', function (ServerRequest $request): Response {
            $userAttribute = $request->getAttribute('user');
            $postAttribute = $request->getAttribute('post');

            if (! $userAttribute instanceof Attribute || ! $postAttribute instanceof Attribute) {
                throw new RuntimeException('Attributes not found.');
            }

            $json = json_encode(['user' => $userAttribute->value, 'post' => $postAttribute->value]);
            if (! is_string($json)) {
                throw new RuntimeException('Failed to decode JSON.');
            }

            return new ResponseFactory()->createFromString($json);
        });

        $_SERVER['REQUEST_URI'] = '/users/42/posts/99?query=param';

        ob_start();
        $this->berry->run();
        $output = ob_get_clean();

        $status = $this->getEmittedStatus();
        $expectedJson = json_encode(['user' => '42', 'post' => '99']);

        $this->assertEquals(HttpStatus::OK, $status);
        $this->assertEquals($expectedJson, $output);
    }

    public function test_it_should_retrieve_route_context(): void
    {
        $this->berry->get('/', function (ServerRequest $request): Response {
            $routeContext = new RouteContextFactory()->createFromRequest($request);

            $this->assertInstanceOf(Route::class, $routeContext->route);
            $this->assertInstanceOf(RouteParser::class, $routeContext->routeParser);

            return new ResponseFactory()->createFromString('Hello, world!');
        });

        ob_start();
        $this->berry->run();
        $output = ob_get_clean();

        $status = $this->getEmittedStatus();

        $this->assertEquals(HttpStatus::OK, $status);
        $this->assertEquals('Hello, world!', $output);
    }

    public function test_it_should_resolve_a_route_with_base_path(): void
    {
        $_SERVER['REQUEST_URI'] = '/api/v1/users/42';
        $_SERVER['SCRIPT_NAME'] = '/api/v1/index.php';
        $_SERVER['QUERY_STRING'] = '';

        $this->berry->setBasePath('/api/v1');

        $this->berry->get('/users/{user}', function (ServerRequest $request): Response {
            $userAttribute = $request->getAttribute('user');
            if (! $userAttribute instanceof Attribute) {
                throw new RuntimeException('Attribute not found.');
            }

            $json = json_encode(['user' => $userAttribute->value]);
            if (! is_string($json)) {
                throw new RuntimeException('Failed to decode JSON.');
            }

            return new ResponseFactory()->createFromString($json);
        });

        ob_start();
        $this->berry->run();
        $output = ob_get_clean();

        $status = $this->getEmittedStatus();
        $expectedJson = json_encode(['user' => '42']);

        $this->assertEquals(HttpStatus::OK, $status);
        $this->assertEquals($expectedJson, $output);

    }
}
