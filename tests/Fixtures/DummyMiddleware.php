<?php

declare(strict_types=1);

namespace Tests\Fixtures;

use Closure;
use YSOCode\Berry\Request;
use YSOCode\Berry\Response;

final class DummyMiddleware
{
    /**
     * @param  Closure(Request, Closure): Response  $next
     */
    public function execute(Request $request, Closure $next): Response
    {
        $response = $next($request);

        return new Response($response->status, 'dummy execute > '.$response->body);
    }
}
