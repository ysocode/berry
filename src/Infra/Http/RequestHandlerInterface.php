<?php

declare(strict_types=1);

namespace YSOCode\Berry\Infra\Http;

interface RequestHandlerInterface
{
    public function handle(ServerRequest $request): Response;
}
