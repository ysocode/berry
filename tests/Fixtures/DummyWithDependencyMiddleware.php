<?php

declare(strict_types=1);

namespace Tests\Fixtures;

use Closure;
use YSOCode\Berry\Request;
use YSOCode\Berry\Response;

final readonly class DummyWithDependencyMiddleware
{
    public function __construct(private DummyService $service) {}

    /**
     * @param  Closure(Request): Response  $next
     */
    public function execute(Request $request, Closure $next): Response
    {
        $response = $next($request);
        $message = $this->service->getMessage().' > '.$response->body;

        return new Response($response->status, $message, $response->headers);
    }
}
