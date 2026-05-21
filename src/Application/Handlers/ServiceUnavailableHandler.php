<?php

declare(strict_types=1);

namespace YSOCode\Berry\Application\Handlers;

use YSOCode\Berry\Domain\Enums\HttpStatus;
use YSOCode\Berry\Infra\Http\Response;
use YSOCode\Berry\Infra\Http\ServerRequest;
use YSOCode\Berry\Infra\Http\ServiceUnavailableHandlerInterface;

final readonly class ServiceUnavailableHandler implements ServiceUnavailableHandlerInterface
{
    /**
     * {@inheritDoc}
     */
    public function handle(ServerRequest $request): Response
    {
        return new Response(HttpStatus::SERVICE_UNAVAILABLE);
    }
}
