<?php

declare(strict_types=1);

namespace Tests\Factory;

use PHPUnit\Framework\TestCase;
use RuntimeException;
use YSOCode\Berry\Infra\Http\ResponseFactory;

final class ResponseFactoryTest extends TestCase
{
    public function test_it_should_create_a_response_from_body(): void
    {
        $json = json_encode(['owner' => 'YSO Code', 'lib' => 'Berry']);
        if (! is_string($json)) {
            throw new RuntimeException('Failed to decode JSON.');
        }

        $response = new ResponseFactory()->fromBody($json);

        $this->assertEquals($json, (string) $response->body);
    }
}
