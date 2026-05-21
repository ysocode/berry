<?php

declare(strict_types=1);

namespace YSOCode\Berry\Application\Handlers;

use YSOCode\Berry\Domain\Enums\HttpStatus;
use YSOCode\Berry\Infra\Http\ForbiddenHandlerInterface;
use YSOCode\Berry\Infra\Http\Response;
use YSOCode\Berry\Infra\Http\ServerRequest;

final readonly class ForbiddenHandler implements ForbiddenHandlerInterface
{
    /**
     * {@inheritDoc}
     */
    public function handle(ServerRequest $request): Response
    {
        return new Response(HttpStatus::FORBIDDEN);
    }
}
