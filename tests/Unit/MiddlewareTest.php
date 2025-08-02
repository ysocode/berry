<?php

declare(strict_types=1);

namespace Tests\Unit;

use DI\ContainerBuilder;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Tests\Fixtures\DummyMiddleware;
use YSOCode\Berry\Domain\ValueObjects\Method;
use YSOCode\Berry\Domain\ValueObjects\Middleware;
use YSOCode\Berry\Domain\ValueObjects\Status;
use YSOCode\Berry\Infra\Request;
use YSOCode\Berry\Infra\Response;
use YSOCode\Berry\Infra\StreamFactory;
use YSOCode\Berry\Infra\Uri;

final class MiddlewareTest extends TestCase
{
    private ContainerInterface $container;

    protected function setUp(): void
    {
        parent::setUp();

        $builder = new ContainerBuilder;
        $builder->useAutowiring(true);

        $this->container = $builder->build();
    }

    public function test_it_should_accept_valid_middleware(): void
    {
        $this->expectNotToPerformAssertions();

        new Middleware(DummyMiddleware::class, 'execute');
    }

    public function test_it_should_reject_nonexistent_class(): void
    {
        $this->expectException(InvalidArgumentException::class);

        /** @phpstan-ignore-next-line */
        new Middleware('Invalid\Class', 'execute');
    }

    public function test_it_should_reject_nonexistent_method(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new Middleware(DummyMiddleware::class, 'nonexistent');
    }

    public function test_it_should_reject_private_method(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new Middleware(DummyMiddleware::class, 'privateMethod');
    }

    public function test_it_should_reject_method_with_wrong_param_count(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new Middleware(DummyMiddleware::class, 'invalidParamCount');
    }

    public function test_it_should_reject_method_with_wrong_param_type(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new Middleware(DummyMiddleware::class, 'invalidParamType');
    }

    public function test_it_should_reject_method_with_wrong_return_type(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new Middleware(DummyMiddleware::class, 'invalidReturnType');
    }

    public function test_it_should_check_equality_between_middlewares(): void
    {
        $a = new Middleware(DummyMiddleware::class, 'execute');
        $b = new Middleware(DummyMiddleware::class, 'execute');
        $c = new Middleware(DummyMiddleware::class, 'anotherMethod');

        $this->assertTrue($a->equals($b));
        $this->assertFalse($a->equals($c));
    }

    public function test_it_should_invoke_middleware_and_return_response(): void
    {
        $middleware = new Middleware(DummyMiddleware::class, 'execute');

        $request = new Request(Method::GET, new Uri('https://example.com'));

        $response = $middleware->invoke($request, fn (Request $r): Response => new Response(Status::OK, [], new StreamFactory()->createFromString('next')), $this->container);

        $this->assertEquals(Status::OK, $response->status);
        $this->assertEquals('dummy execute > next', $response->body);
    }

    public function test_it_should_check_validity_statistically(): void
    {
        $this->assertTrue(Middleware::isValid(DummyMiddleware::class, 'execute'));
    }
}
