<?php

declare(strict_types=1);

namespace Tests\Fixtures;

use YSOCode\Berry\Request;
use YSOCode\Berry\Response;
use YSOCode\Berry\Status;

final class DummyController
{
    public function index(Request $request): Response
    {
        return new Response(Status::OK, 'ok');
    }
}
