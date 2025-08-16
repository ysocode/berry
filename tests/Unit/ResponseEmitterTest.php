<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use YSOCode\Berry\Domain\ValueObjects\Header;
use YSOCode\Berry\Domain\ValueObjects\HeaderName;
use YSOCode\Berry\Domain\ValueObjects\HttpStatus;
use YSOCode\Berry\Infra\Http\Response;
use YSOCode\Berry\Infra\Http\ResponseEmitter;
use YSOCode\Berry\Infra\Stream\StreamFactory;

final class ResponseEmitterTest extends TestCase
{
    /**
     * @var array<array{header: string, replace: bool, statusCode: int}>
     */
    private array $emittedHeaders = [];

    protected function setUp(): void
    {
        $this->emittedHeaders = [];
    }

    private function createResponse(): Response
    {
        $body = new StreamFactory()->createFromString('Hello, world!');

        return new Response(
            HttpStatus::OK,
            [
                new Header(new HeaderName('Content-Type'), ['text/javascript; charset=utf-8']),
                new Header(new HeaderName('Content-Encoding'), ['deflate', 'gzip']),
            ],
            $body,
        );
    }

    private function headerEmitter(string $header, bool $replace = true, int $statusCode = 0): void
    {
        $this->emittedHeaders[] = [
            'header' => $header,
            'replace' => $replace,
            'statusCode' => $statusCode,
        ];
    }

    public function test_it_should_create_a_valid_response_emitter(): void
    {
        $response = $this->createResponse();
        $responseEmitter = new ResponseEmitter($this->headerEmitter(...));

        ob_start();
        $responseEmitter->emit($response);
        $output = ob_get_clean();

        $this->assertEquals('Hello, world!', $output);

        $emittedHeaders = array_column($this->emittedHeaders, 'header');

        $this->assertContains(
            sprintf(
                'HTTP/%s %s %s',
                $response->version,
                $response->status->value,
                $response->status->getReasonPhrase()
            ),
            $emittedHeaders
        );

        $this->assertContains('Content-Type: text/javascript; charset=utf-8', $emittedHeaders);
        $this->assertContains('Content-Encoding: deflate, gzip', $emittedHeaders);
    }
}
