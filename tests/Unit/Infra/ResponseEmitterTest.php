<?php

declare(strict_types=1);

namespace Tests\Unit\Infra;

use Closure;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Tests\Traits\HeaderEmitterTrait;
use YSOCode\Berry\Domain\Enums\HttpStatus;
use YSOCode\Berry\Domain\Types\Header;
use YSOCode\Berry\Domain\Types\HeaderName;
use YSOCode\Berry\Infra\Http\Response;
use YSOCode\Berry\Infra\Http\ResponseEmitter;
use YSOCode\Berry\Infra\Stream\StreamFactory;

final class ResponseEmitterTest extends TestCase
{
    use HeaderEmitterTrait;

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

    /**
     * @return array<string, array{Closure, string}>
     */
    public static function invalidHeaderEmitters(): array
    {
        return [
            'no parameters' => [
                function (): void {},
                'Must accept exactly 3 parameters (string, bool=, int=).',
            ],
            'first param not string' => [
                function (int $header, bool $replace = true, int $code = 0): void {},
                'First parameter of the header emitter should be a string.',
            ],
            'second param not boolean' => [
                function (string $header, string $replace, int $code = 0): void {},
                'Second parameter of the header emitter should be a boolean.',
            ],
            'third param not integer' => [
                function (string $header, bool $replace = true, array $code = []): void {},
                'Third parameter of the header emitter should be an integer.',
            ],
            'missing default values' => [
                function (string $header, bool $replace, int $code): void {},
                'Second and third parameters of the header emitter must have default values.',
            ],
            'wrong return type' => [
                fn (string $header, bool $replace = true, int $code = 0): int => 0,
                'The header emitter function must return void.',
            ],
        ];
    }

    public function test_it_should_create_a_valid_response_emitter(): void
    {
        $response = $this->createResponse();
        $responseEmitter = new ResponseEmitter($this->headerEmitter(...));

        ob_start();
        $responseEmitter->emit($response);
        $output = ob_get_clean();

        $emittedHeaders = array_column($this->emittedHeaders, 'header');

        $this->assertEquals('Hello, world!', $output);
        $this->assertContains(
            sprintf(
                'HTTP/%s %s %s',
                $response->version->value,
                $response->status->value,
                $response->status->getReasonPhrase()
            ),
            $emittedHeaders
        );
        $this->assertContains('Content-Type: text/javascript; charset=utf-8', $emittedHeaders);
        $this->assertContains('Content-Encoding: deflate, gzip', $emittedHeaders);
    }

    #[DataProvider('invalidHeaderEmitters')]
    public function test_it_should_not_create_a_response_emitter(
        Closure $headerEmitter,
        string $expectedMessage
    ): void {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage($expectedMessage);

        new ResponseEmitter($headerEmitter);
    }

    public function test_it_should_emit_body_based_on_content_length_header(): void
    {
        $response = $this->createResponse();
        $response = $response->withHeader('Content-Length', ['2']);

        $responseEmitter = new ResponseEmitter($this->headerEmitter(...));

        ob_start();
        $responseEmitter->emit($response);
        $output = ob_get_clean();

        $this->assertEquals('He', $output);
    }

    public function test_it_should_emit_set_cookie_headers_with_replace_false(): void
    {
        $response = $this->createResponse();
        $response = $response->withHeader('Set-Cookie', ['cookie1=1', 'cookie2=2']);

        $responseEmitter = new ResponseEmitter($this->headerEmitter(...));

        ob_start();
        $responseEmitter->emit($response);
        ob_get_clean();

        $setCookieEmittedHeaders = array_values(
            array_filter(
                $this->emittedHeaders,
                fn (array $header): bool => str_contains((string) $header['header'], 'Set-Cookie')
            )
        );
        $firstSetCookieEmittedHeader = array_first($setCookieEmittedHeaders);
        $secondSetCookieEmittedHeader = array_last($setCookieEmittedHeaders);

        $this->assertIsArray($firstSetCookieEmittedHeader);
        $this->assertIsArray($secondSetCookieEmittedHeader);

        $this->assertEquals('Set-Cookie: cookie1=1', $firstSetCookieEmittedHeader['header']);
        $this->assertFalse($firstSetCookieEmittedHeader['replace']);
        $this->assertEquals('Set-Cookie: cookie2=2', $secondSetCookieEmittedHeader['header']);
        $this->assertFalse($secondSetCookieEmittedHeader['replace']);
    }
}
