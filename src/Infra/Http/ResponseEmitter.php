<?php

declare(strict_types=1);

namespace YSOCode\Berry\Infra\Http;

use Closure;
use InvalidArgumentException;
use ReflectionFunction;
use ReflectionNamedType;
use ReflectionParameter;
use YSOCode\Berry\Domain\ValueObjects\Error;
use YSOCode\Berry\Domain\ValueObjects\Header;
use YSOCode\Berry\Domain\ValueObjects\HeaderName;

final readonly class ResponseEmitter
{
    /**
     * @var Closure(string, bool=, int=): void
     */
    private Closure $headerEmitter;

    /**
     * @param  Closure(string, bool=, int=): void|null  $headerEmitter
     */
    public function __construct(
        ?Closure $headerEmitter = null,
        private int $chunkSize = 4096,
    ) {
        $headerEmitter ??= header(...);

        $isValid = $this->validateHeaderEmitterSignature($headerEmitter);
        if ($isValid instanceof Error) {
            throw new InvalidArgumentException((string) $isValid);
        }

        $this->headerEmitter = $headerEmitter;
    }

    private function validateHeaderEmitterSignature(Closure $headerEmitter): true|Error
    {
        $reflection = new ReflectionFunction($headerEmitter);

        if ($reflection->getNumberOfParameters() !== 3) {
            return new Error('Must accept exactly 3 parameters (string, bool=, int=).');
        }

        [$first, $second, $third] = $reflection->getParameters();

        $isParameterTypesValid = $this->validateParameterTypes($first, $second, $third);
        if ($isParameterTypesValid instanceof Error) {
            return $isParameterTypesValid;
        }

        $isDefaultValuesValid = $this->validateDefaultValues($second, $third);
        if ($isDefaultValuesValid instanceof Error) {
            return $isDefaultValuesValid;
        }

        $returnType = $reflection->getReturnType();
        if (! $returnType instanceof ReflectionNamedType || $returnType->getName() !== 'void') {
            return new Error('The header emitter function must return void.');
        }

        return true;
    }

    private function validateParameterTypes(
        ReflectionParameter $first,
        ReflectionParameter $second,
        ReflectionParameter $third
    ): true|Error {
        $firstType = $first->getType();
        if (! $firstType instanceof ReflectionNamedType || $firstType->getName() !== 'string') {
            return new Error('First parameter of the header emitter should be a string.');
        }

        $secondType = $second->getType();
        if (! $secondType instanceof ReflectionNamedType || $secondType->getName() !== 'bool') {
            return new Error('Second parameter of the header emitter should be a boolean.');
        }

        $thirdType = $third->getType();
        if (! $thirdType instanceof ReflectionNamedType || $thirdType->getName() !== 'int') {
            return new Error('Third parameter of the header emitter should be an integer.');
        }

        return true;
    }

    private function validateDefaultValues(ReflectionParameter $second, ReflectionParameter $third): true|Error
    {
        if (! $second->isDefaultValueAvailable() || ! $third->isDefaultValueAvailable()) {
            return new Error('Second and third parameters of the header emitter must have default values.');
        }

        return true;
    }

    public function emit(Response $response): void
    {
        $this->emitStatus($response);
        $this->emitHeader($response);
        $this->emitBody($response);
    }

    private function emitStatus(Response $response): void
    {
        ($this->headerEmitter)(
            sprintf(
                'HTTP/%s %s %s',
                $response->version,
                $response->status->value,
                $response->status->getReasonPhrase()
            ),
            true,
            $response->status->value
        );
    }

    private function emitHeader(Response $response): void
    {
        foreach ($response->headers as $name => $header) {
            if ($name === 'set-cookie') {
                foreach ($header->values as $value) {
                    ($this->headerEmitter)($header->name.': '.$value, false);
                }

                continue;
            }

            ($this->headerEmitter)((string) $header);
        }
    }

    private function emitBody(Response $response): void
    {
        $body = $response->body;
        if ($body->isSeekable) {
            $body->rewind();
        }

        $amountToRead = $this->getAmountToRead($response);

        if (is_int($amountToRead)) {
            while ($amountToRead > 0 && ! $body->isFinished()) {
                $bytesToRead = min($this->chunkSize, $amountToRead);
                $data = $body->read($bytesToRead);
                echo $data;

                $amountToRead -= strlen($data);

                if (connection_status() !== CONNECTION_NORMAL) {
                    break;
                }
            }
        } else {
            while (! $body->isFinished()) {
                echo $body->read($this->chunkSize);

                if (connection_status() !== CONNECTION_NORMAL) {
                    break;
                }
            }
        }
    }

    private function getAmountToRead(Response $response): ?int
    {
        $contentAmountHeader = $response->getHeader(new HeaderName('Content-Length'));
        if (! $contentAmountHeader instanceof Header) {
            return null;
        }

        [$amountToRead] = $contentAmountHeader->values;
        if (is_numeric($amountToRead)) {
            return (int) $amountToRead;
        }

        return null;
    }
}
