<?php

declare(strict_types=1);

namespace YSOCode\Berry\Application\Middlewares;

use YSOCode\Berry\Domain\Payloads\ResolvedRoute;
use YSOCode\Berry\Infra\Http\MiddlewareInterface;
use YSOCode\Berry\Infra\Http\RequestHandlerInterface;
use YSOCode\Berry\Infra\Http\Response;
use YSOCode\Berry\Infra\Http\ServerRequest;

final class RouteParametersMiddleware implements MiddlewareInterface
{
    public function __construct(
        public ResolvedRoute $resolvedRoute
    ) {}

    /**
     * {@inheritDoc}
     */
    public function process(ServerRequest $request, RequestHandlerInterface $handler): Response
    {
        foreach ($this->resolvedRoute->parameters as $parameter) {
            $request = $request->withAttribute((string) $parameter->name, $parameter->value);
        }

        return $handler->handle($request);
    }
}
