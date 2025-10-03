<?php

declare(strict_types=1);

namespace Tests\Integration;

use DI\Container;
use PHPUnit\Framework\TestCase;
use Tests\Fixtures\HelloWorldHandler;
use Tests\Fixtures\InspectRequestHandler;
use Tests\Fixtures\LoggingMiddleware;
use Tests\Fixtures\PoweredByMiddleware;
use Tests\Support\ServerEnvironmentSetupTrait;
use YSOCode\Berry\Application\Berry;
use YSOCode\Berry\Domain\Enums\HttpStatus;
use YSOCode\Berry\Domain\ValueObjects\UriPath;
use YSOCode\Berry\Infra\Http\ResponseEmitter;

final class BerryTest extends TestCase
{
    use ServerEnvironmentSetupTrait;

    private Berry $berry;

    /**
     * @var array<array{header: string, replace: bool, statusCode: int}>
     */
    private array $emittedHeaders = [];

    protected function setUp(): void
    {
        $this->initializeFakeEnvironment();

        $this->berry = new Berry(
            new Container,
            responseEmitter: new ResponseEmitter($this->headerEmitter(...)),
        );
    }

    private function headerEmitter(string $header, bool $replace = true, int $statusCode = 0): void
    {
        $this->emittedHeaders[] = [
            'header' => $header,
            'replace' => $replace,
            'statusCode' => $statusCode,
        ];
    }

    public function test_it_should_run_a_route(): void
    {
        $this->berry->get(
            new UriPath('/'),
            HelloWorldHandler::class
        );

        ob_start();
        $this->berry->run();
        $output = ob_get_clean();

        $status = HttpStatus::from($this->emittedHeaders[0]['statusCode']);

        $this->assertEquals(HttpStatus::OK, $status);
        $this->assertEquals('Hello, world!', $output);
    }

    public function test_it_should_handle_single_global_middleware(): void
    {
        $this->berry->addMiddleware(LoggingMiddleware::class);

        $this->berry->get(
            new UriPath('/'),
            InspectRequestHandler::class
        );

        ob_start();
        $this->berry->run();
        $output = ob_get_clean();

        $status = HttpStatus::from($this->emittedHeaders[0]['statusCode']);

        $this->assertEquals(HttpStatus::OK, $status);
        $this->assertEquals('Log: 1997-08-22 00:00:00. Powered by: Not powered.', $output);
    }

    public function test_it_should_handle_multiple_global_middlewares(): void
    {
        $this->berry->addMiddlewares([LoggingMiddleware::class, PoweredByMiddleware::class]);

        $this->berry->get(
            new UriPath('/'),
            InspectRequestHandler::class
        );

        ob_start();
        $this->berry->run();
        $output = ob_get_clean();

        $status = HttpStatus::from($this->emittedHeaders[0]['statusCode']);

        $this->assertEquals(HttpStatus::OK, $status);
        $this->assertEquals('Log: 1997-08-22 00:00:00. Powered by: Berry.', $output);
    }

    public function test_it_should_handle_method_not_allowed_error(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';

        $this->berry->get(
            new UriPath('/'),
            HelloWorldHandler::class
        );

        $this->berry->run();

        $status = HttpStatus::from($this->emittedHeaders[0]['statusCode']);

        $this->assertEquals(HttpStatus::METHOD_NOT_ALLOWED, $status);
    }

    public function test_it_should_handle_not_found_error(): void
    {
        $_SERVER['REQUEST_URI'] = '/path/to/resource?query=param';

        $this->berry->run();

        $status = HttpStatus::from($this->emittedHeaders[0]['statusCode']);

        $this->assertEquals(HttpStatus::NOT_FOUND, $status);
    }
}
