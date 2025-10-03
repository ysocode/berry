<?php

declare(strict_types=1);

namespace YSOCode\Berry\Domain\Entities;

use Closure;
use RuntimeException;
use YSOCode\Berry\Domain\Enums\HttpMethod;
use YSOCode\Berry\Domain\Enums\RouteEvent;
use YSOCode\Berry\Domain\ValueObjects\Name;
use YSOCode\Berry\Domain\ValueObjects\UriPath;
use YSOCode\Berry\Infra\Http\MiddlewareInterface;
use YSOCode\Berry\Infra\Http\RequestHandlerInterface;
use YSOCode\Berry\Infra\Http\Response;
use YSOCode\Berry\Infra\Http\ServerRequest;

final class Route
{
    /** @var array<string, array<Closure(self, array<string, mixed>): void>> */
    private array $listeners = [];

    /**
     * @param  class-string<RequestHandlerInterface>|Closure(ServerRequest $request): Response  $handler
     * @param  array<class-string<MiddlewareInterface>|Closure(ServerRequest $request, RequestHandlerInterface $handler): Response>  $middlewares
     */
    public function __construct(
        public readonly HttpMethod $method,
        private(set) UriPath $path,
        public readonly string|Closure $handler,
        private(set) ?Name $name = null,
        private(set) array $middlewares = []
    ) {}

    /**
     * @param  Closure(self, array<string, mixed>): void  $listener
     */
    public function on(RouteEvent $event, Closure $listener): self
    {
        $this->listeners[$event->name][] = $listener;

        return $this;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function emit(RouteEvent $event, array $data = []): void
    {
        foreach ($this->listeners[$event->name] ?? [] as $listener) {
            $listener($this, $data);
        }
    }

    public function addPrefix(UriPath $prefix): self
    {
        $this->path = $this->path->prepend($prefix);

        return $this;
    }

    public function setName(Name $name): self
    {
        if ($this->name instanceof Name) {
            throw new RuntimeException('Name is already set.');
        }

        $this->emit(RouteEvent::NAME_CHANGED, ['name' => $name]);

        $this->name = $name;

        return $this;
    }

    /**
     * @param  class-string<MiddlewareInterface>|Closure(ServerRequest $request, RequestHandlerInterface $handler): Response  $middleware
     */
    public function addMiddleware(string|Closure $middleware): self
    {
        $this->middlewares[] = $middleware;

        return $this;
    }

    /**
     * @param  array<class-string<MiddlewareInterface>|Closure(ServerRequest $request, RequestHandlerInterface $handler): Response>  $middlewares
     */
    public function addMiddlewares(array $middlewares): self
    {
        $this->middlewares = array_merge($this->middlewares, $middlewares);

        return $this;
    }
}
