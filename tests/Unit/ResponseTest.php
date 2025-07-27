<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use YSOCode\Berry\Response;
use YSOCode\Berry\Status;

final class ResponseTest extends TestCase
{
    public function test_it_adds_headers_immutably(): void
    {
        $response = new Response(Status::OK);
        $newResponse = $response->withHeader('Content-Type', 'application/json');

        $this->assertNotSame($response, $newResponse);
        $this->assertArrayHasKey('Content-Type', $newResponse->headers);
        $this->assertSame('application/json', $newResponse->headers['Content-Type']);
        $this->assertEmpty($response->headers);
    }

    public function test_it_can_change_status(): void
    {
        $response = new Response(Status::OK);
        $newResponse = $response->withStatus(Status::NOT_FOUND);

        $this->assertNotSame($response, $newResponse);
        $this->assertSame(Status::NOT_FOUND, $newResponse->status);
        $this->assertSame(Status::OK, $response->status);
    }
}
