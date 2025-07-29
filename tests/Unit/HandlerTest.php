<?php

declare(strict_types=1);

use DI\ContainerBuilder;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Tests\Fixtures\DummyController;
use YSOCode\Berry\Domain\ValueObjects\Handler;
use YSOCode\Berry\Domain\ValueObjects\Method;
use YSOCode\Berry\Domain\ValueObjects\Path;
use YSOCode\Berry\Domain\ValueObjects\Status;
use YSOCode\Berry\Infra\Request;

final class HandlerTest extends TestCase
{
    private ContainerInterface $container;

    protected function setUp(): void
    {
        parent::setUp();

        $builder = new ContainerBuilder;
        $builder->useAutowiring(true);

        $this->container = $builder->build();
    }

    public function test_it_should_accept_valid_handler(): void
    {
        $this->expectNotToPerformAssertions();

        new Handler(DummyController::class, 'index');
    }

    public function test_it_should_reject_nonexistent_class(): void
    {
        $this->expectException(InvalidArgumentException::class);

        /** @phpstan-ignore-next-line */
        new Handler('Invalid\Class', 'handle');
    }

    public function test_it_should_reject_nonexistent_method(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new Handler(DummyController::class, 'invalidMethod');
    }

    public function test_it_should_reject_private_method(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new Handler(DummyController::class, 'privateMethod');
    }

    public function test_it_should_reject_method_with_wrong_param_count(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new Handler(DummyController::class, 'invalidParamCount');
    }

    public function test_it_should_reject_method_with_wrong_param_type(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new Handler(DummyController::class, 'invalidParamType');
    }

    public function test_it_should_reject_method_with_wrong_return_type(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new Handler(DummyController::class, 'invalidReturnType');
    }

    public function test_it_should_check_equality_between_handlers(): void
    {
        $a = new Handler(DummyController::class, 'index');
        $b = new Handler(DummyController::class, 'index');
        $c = new Handler(DummyController::class, 'anotherMethod');

        $this->assertTrue($a->equals($b));
        $this->assertFalse($a->equals($c));
    }

    public function test_it_should_invoke_handler_and_return_response(): void
    {
        $handler = new Handler(DummyController::class, 'index');

        $response = $handler->invoke(new Request(Method::GET, new Path('/path')), $this->container);

        $this->assertEquals(Status::OK, $response->status);
        $this->assertEquals('ok', $response->body);
    }
}
