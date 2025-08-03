<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use YSOCode\Berry\Domain\ValueObjects\Header;
use YSOCode\Berry\Domain\ValueObjects\HeaderName;
use YSOCode\Berry\Domain\ValueObjects\Status;
use YSOCode\Berry\Infra\Http\Response;
use YSOCode\Berry\Infra\Http\ResponseEmitter;
use YSOCode\Berry\Infra\Stream\StreamFactory;

class ResponseEmitterTest extends TestCase
{
    /**
     * @var array<string>
     */
    private array $capturedHeaders = [];

    private ?string $capturedStatusLine = null;

    public function test_it_should_emit_response_with_status_headers_and_body(): void
    {
        global $emittedHeaders;
        $emittedHeaders = [];

        $response = new Response(
            Status::OK,
            [
                new Header(new HeaderName('Content-Type'), ['text/plain']),
                new Header(new HeaderName('Content-Length'), ['11']),
            ],
            new StreamFactory()->createFromString('Hello World')
        );

        $emitter = $this->getMockBuilder(ResponseEmitter::class)
            ->onlyMethods(['emitStatusLine', 'emitHeaders'])
            ->getMock();

        $emitter->method('emitStatusLine')
            ->willReturnCallback(function (Response $response): void {
                $this->capturedStatusLine = sprintf(
                    'HTTP/%s %d %s',
                    $response->protocolVersion,
                    $response->status->value,
                    $response->status->reason()
                );
            });

        $emitter->method('emitHeaders')
            ->willReturnCallback(function (Response $response): void {
                foreach ($response->headers as $name => $header) {
                    $this->capturedHeaders[] = sprintf('%s: %s', $name, implode(', ', $header->value));
                }
            });

        ob_start();
        $emitter->emit($response);
        $output = ob_get_clean();

        $this->assertSame('Hello World', $output);
        $this->assertSame('HTTP/1.1 200 OK', $this->capturedStatusLine);
        $this->assertContains('Content-Type: text/plain', $this->capturedHeaders);
        $this->assertContains('Content-Length: 11', $this->capturedHeaders);
    }

    public function test_it_should_not_emit_body_for_empty_response(): void
    {
        $response = new Response(
            Status::NO_CONTENT,
            [],
            new StreamFactory()->createFromString('')
        );

        $emitter = new ResponseEmitter;

        ob_start();
        $emitter->emit($response);
        $output = ob_get_clean();

        $this->assertSame('', $output);
    }

    public function test_it_should_emit_body_in_chunks(): void
    {
        $data = str_repeat('a', 10000);
        $stream = new StreamFactory()->createFromString($data);

        $response = new Response(
            Status::OK,
            [
                new Header(new HeaderName('Content-Length'), [(string) strlen($data)]),
            ],
            $stream
        );

        $emitter = new ResponseEmitter(4096);

        ob_start();
        $emitter->emit($response);
        $output = ob_get_clean();

        $this->assertSame($data, $output);
    }

    public function test_it_should_fallback_to_stream_size_when_content_length_missing(): void
    {
        $data = 'abc123';
        $stream = new StreamFactory()->createFromString($data);

        $response = new Response(
            Status::OK,
            [],
            $stream
        );

        $emitter = new ResponseEmitter;

        ob_start();
        $emitter->emit($response);
        $output = ob_get_clean();

        $this->assertSame($data, $output);
    }
}
