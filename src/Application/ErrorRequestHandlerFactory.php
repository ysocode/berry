<?php

declare(strict_types=1);

namespace YSOCode\Berry\Application;

use Closure;
use Psr\Container\ContainerInterface;
use YSOCode\Berry\Application\Handlers\InternalServerErrorHandler;
use YSOCode\Berry\Application\Handlers\MethodNotAllowedHandler;
use YSOCode\Berry\Application\Handlers\NotFoundHandler;
use YSOCode\Berry\Domain\Types\Error;
use YSOCode\Berry\Domain\Types\RequestHandler;
use YSOCode\Berry\Infra\Http\RequestHandlerInterface;
use YSOCode\Berry\Infra\Http\Response;
use YSOCode\Berry\Infra\Http\ServerRequest;

final readonly class ErrorRequestHandlerFactory
{
    public function __construct(
        private ContainerInterface $container
    ) {}

    public function createFromError(Error $error): RequestHandler
    {
        $handler = match (true) {
            $error->equals(new Error('Method not allowed.')) => $this->getHandler('method_not_allowed', MethodNotAllowedHandler::class),
            $error->equals(new Error('Route not found.')) => $this->getHandler('not_found', NotFoundHandler::class),
            default => $this->getHandler('internal_server_error', InternalServerErrorHandler::class),
        };

        if ($handler instanceof RequestHandler) {
            return $handler;
        }

        return new RequestHandler($handler);
    }

    /**
     * @param  class-string<RequestHandlerInterface>  $default
     * @return class-string<RequestHandlerInterface>|Closure(ServerRequest $request): Response|RequestHandler
     */
    private function getHandler(string $key, string $default): string|Closure|RequestHandler
    {
        /** @var class-string<RequestHandlerInterface>|Closure(ServerRequest $request): Response|RequestHandler $handler */
        $handler = $this->container->has($key) ? $this->container->get($key) : $default;

        return $handler;
    }
}
