<?php

declare(strict_types=1);

namespace YSOCode\Berry\Application;

use Psr\Container\ContainerInterface;
use RuntimeException;
use YSOCode\Berry\Application\Handlers\BadRequestHandler;
use YSOCode\Berry\Application\Handlers\ForbiddenHandler;
use YSOCode\Berry\Application\Handlers\InternalServerErrorHandler;
use YSOCode\Berry\Application\Handlers\MethodNotAllowedHandler;
use YSOCode\Berry\Application\Handlers\NotFoundHandler;
use YSOCode\Berry\Application\Handlers\ServiceUnavailableHandler;
use YSOCode\Berry\Application\Handlers\UnauthorizedHandler;
use YSOCode\Berry\Domain\Types\Error;
use YSOCode\Berry\Domain\Types\RequestHandler;
use YSOCode\Berry\Infra\Http\BadRequestHandlerInterface;
use YSOCode\Berry\Infra\Http\ForbiddenHandlerInterface;
use YSOCode\Berry\Infra\Http\InternalServerErrorHandlerInterface;
use YSOCode\Berry\Infra\Http\MethodNotAllowedHandlerInterface;
use YSOCode\Berry\Infra\Http\NotFoundHandlerInterface;
use YSOCode\Berry\Infra\Http\ServiceUnavailableHandlerInterface;
use YSOCode\Berry\Infra\Http\UnauthorizedHandlerInterface;

final readonly class ErrorRequestHandlerFactory
{
    public function __construct(
        private ContainerInterface $container
    ) {}

    public function createFromError(Error $error): RequestHandler
    {
        $handler = match (true) {
            $error->equals(new Error('Bad request.')) => $this->getHandler(BadRequestHandlerInterface::class, BadRequestHandler::class),
            $error->equals(new Error('Unauthorized.')) => $this->getHandler(UnauthorizedHandlerInterface::class, UnauthorizedHandler::class),
            $error->equals(new Error('Forbidden.')) => $this->getHandler(ForbiddenHandlerInterface::class, ForbiddenHandler::class),
            $error->equals(new Error('Method not allowed.')) => $this->getHandler(MethodNotAllowedHandlerInterface::class, MethodNotAllowedHandler::class),
            $error->equals(new Error('Route not found.')) => $this->getHandler(NotFoundHandlerInterface::class, NotFoundHandler::class),
            $error->equals(new Error('Service unavailable.')) => $this->getHandler(ServiceUnavailableHandlerInterface::class, ServiceUnavailableHandler::class),
            default => $this->getHandler(InternalServerErrorHandlerInterface::class, InternalServerErrorHandler::class),
        };

        return new RequestHandler($handler);
    }

    /**
     * @template T of BadRequestHandlerInterface|UnauthorizedHandlerInterface|ForbiddenHandlerInterface|MethodNotAllowedHandlerInterface|NotFoundHandlerInterface|InternalServerErrorHandlerInterface|ServiceUnavailableHandlerInterface
     *
     * @param  class-string<T>  $id
     * @param  class-string<T>  $default
     * @return class-string<T>|T
     */
    private function getHandler(string $id, string $default): string|BadRequestHandlerInterface|UnauthorizedHandlerInterface|ForbiddenHandlerInterface|MethodNotAllowedHandlerInterface|NotFoundHandlerInterface|InternalServerErrorHandlerInterface|ServiceUnavailableHandlerInterface
    {
        if (! $this->container->has($id)) {
            return $default;
        }

        $handler = $this->container->get($id);

        if (is_string($handler)) {
            if (! class_exists($handler)) {
                throw new RuntimeException(sprintf(
                    'The container returned a string for "%s", but the class "%s" does not exist.',
                    $id,
                    $handler
                ));
            }

            if (! is_subclass_of($handler, $id)) {
                throw new RuntimeException(sprintf(
                    'The class "%s" returned for "%s" does not implement %s.',
                    $handler,
                    $id,
                    $id
                ));
            }

            return $handler;
        }

        if (! $handler instanceof $id) {
            throw new RuntimeException(sprintf(
                'The instance returned for "%s" must implement %s.',
                $id,
                $id
            ));
        }

        return $handler;
    }
}
