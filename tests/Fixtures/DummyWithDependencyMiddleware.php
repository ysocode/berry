<?php

declare(strict_types=1);

namespace Tests\Fixtures;

use Closure;
use YSOCode\Berry\Infra\Http\Request;
use YSOCode\Berry\Infra\Http\Response;
use YSOCode\Berry\Infra\Stream\StreamFactory;

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

        return new Response($response->status, $response->headers, new StreamFactory()->createFromString($message));
    }
}
