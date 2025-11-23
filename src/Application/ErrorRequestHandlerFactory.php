<?php

declare(strict_types=1);

namespace YSOCode\Berry\Application;

use Psr\Container\ContainerInterface;
use YSOCode\Berry\Application\Handlers\InternalServerErrorHandler;
use YSOCode\Berry\Application\Handlers\MethodNotAllowedHandler;
use YSOCode\Berry\Application\Handlers\NotFoundHandler;
use YSOCode\Berry\Domain\Types\Error;
use YSOCode\Berry\Domain\Types\RequestHandler;
use YSOCode\Berry\Infra\Http\InternalServerErrorHandlerInterface;
use YSOCode\Berry\Infra\Http\MethodNotAllowedHandlerInterface;
use YSOCode\Berry\Infra\Http\NotFoundHandlerInterface;

final readonly class ErrorRequestHandlerFactory
{
    public function __construct(
        private ContainerInterface $container
    ) {}

    public function createFromError(Error $error): RequestHandler
    {
        $handler = match (true) {
            $error->equals(new Error('Method not allowed.')) => $this->getHandler(MethodNotAllowedHandlerInterface::class, MethodNotAllowedHandler::class),
            $error->equals(new Error('Route not found.')) => $this->getHandler(NotFoundHandlerInterface::class, NotFoundHandler::class),
            default => $this->getHandler(InternalServerErrorHandlerInterface::class, InternalServerErrorHandler::class),
        };

        return new RequestHandler($handler);
    }

    /**
     * @template T of MethodNotAllowedHandlerInterface|NotFoundHandlerInterface|InternalServerErrorHandlerInterface
     *
     * @param  class-string<T>  $id
     * @param  class-string<T>  $default
     * @return class-string<T>|T
     */
    private function getHandler(string $id, string $default): string|MethodNotAllowedHandlerInterface|NotFoundHandlerInterface|InternalServerErrorHandlerInterface
    {
        if ($this->container->has($id)) {
            $handler = $this->container->get($id);
            if (
                is_string($handler) &&
                class_exists($handler) &&
                is_subclass_of($handler, $id)
            ) {
                return $handler;
            }

            if ($handler instanceof $id) {
                return $handler;
            }
        }

        return $default;
    }
}
