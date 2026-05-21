<?php

declare(strict_types=1);

namespace Tests\Unit\Application;

use DI\Container;
use PHPUnit\Framework\TestCase;
use YSOCode\Berry\Application\ErrorRequestHandlerFactory;
use YSOCode\Berry\Domain\Enums\HttpMethod;
use YSOCode\Berry\Domain\Enums\HttpStatus;
use YSOCode\Berry\Domain\Types\Error;
use YSOCode\Berry\Infra\Http\ForbiddenHandlerInterface;
use YSOCode\Berry\Infra\Http\Response;
use YSOCode\Berry\Infra\Http\ServerRequest;
use YSOCode\Berry\Infra\Http\UriFactory;

final class ErrorRequestHandlerFactoryTest extends TestCase
{
    public function test_it_resolves_default_handlers_for_known_http_errors(): void
    {
        $expectedStatusByErrorMessage = [
            'Bad request.' => HttpStatus::BAD_REQUEST,
            'Unauthorized.' => HttpStatus::UNAUTHORIZED,
            'Forbidden.' => HttpStatus::FORBIDDEN,
            'Route not found.' => HttpStatus::NOT_FOUND,
            'Method not allowed.' => HttpStatus::METHOD_NOT_ALLOWED,
            'Service unavailable.' => HttpStatus::SERVICE_UNAVAILABLE,
        ];

        foreach ($expectedStatusByErrorMessage as $errorMessage => $expectedStatus) {
            $response = $this->handle(new Error($errorMessage), new Container);

            $this->assertSame($expectedStatus, $response->status);
        }
    }

    public function test_it_resolves_unknown_errors_as_internal_server_error(): void
    {
        $response = $this->handle(new Error('Unexpected error.'), new Container);

        $this->assertSame(HttpStatus::INTERNAL_SERVER_ERROR, $response->status);
    }

    public function test_it_uses_custom_container_handler_when_available(): void
    {
        $container = new Container;
        $container->set(ForbiddenHandlerInterface::class, new CustomForbiddenHandler);

        $response = $this->handle(new Error('Forbidden.'), $container);

        $this->assertSame(HttpStatus::RESET_CONTENT, $response->status);
    }

    private function handle(Error $error, Container $container): Response
    {
        $handler = new ErrorRequestHandlerFactory($container)->createFromError($error)->resolve($container);

        return $handler->handle($this->request());
    }

    private function request(): ServerRequest
    {
        return new ServerRequest(HttpMethod::GET, new UriFactory()->createFromString('https://example.com'));
    }
}

final class CustomForbiddenHandler implements ForbiddenHandlerInterface
{
    public function handle(ServerRequest $request): Response
    {
        return new Response(HttpStatus::RESET_CONTENT);
    }
}
