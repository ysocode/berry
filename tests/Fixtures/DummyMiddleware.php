<?php

declare(strict_types=1);

namespace Tests\Fixtures;

use Closure;
use YSOCode\Berry\Domain\ValueObjects\Status;
use YSOCode\Berry\Infra\Request;
use YSOCode\Berry\Infra\Response;
use YSOCode\Berry\Infra\StreamFactory;

final readonly class DummyMiddleware
{
    /**
     * @param  Closure(Request): Response  $next
     */
    public function execute(Request $request, Closure $next): Response
    {
        $response = $next($request);

        return new Response($response->status, [], new StreamFactory()->createFromString('dummy execute > '.$response->body));
    }

    /**
     * @param  Closure(Request): Response  $next
     */
    public function anotherMethod(Request $request, Closure $next): Response
    {
        $response = $next($request);

        return new Response($response->status, [], new StreamFactory()->createFromString('dummy execute > '.$response->body));
    }

    /**
     * @param  Closure(Request): Response  $next
     */
    private function privateMethod(Request $request, Closure $next): Response
    {
        $response = $next($request);

        return new Response($response->status, [], new StreamFactory()->createFromString('dummy execute > '.$response->body));
    }

    private function invalidParamCount(Request $request): Response
    {

        return new Response(Status::OK, [], new StreamFactory()->createFromString('dummy execute'));
    }

    /**
     * @param  Closure(Request): Response  $next
     */
    private function invalidParamType(string $request, Closure $next): Response
    {
        return new Response(Status::OK, [], new StreamFactory()->createFromString('dummy execute'));
    }

    /**
     * @param  Closure(Request): Response  $next
     */
    public function invalidReturnType(Request $request, Closure $next): string
    {
        $response = $next($request);

        return 'dummy execute > '.$response->body;
    }
}
