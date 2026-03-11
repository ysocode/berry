<?php

declare(strict_types=1);

namespace YSOCode\Berry\Domain\Types;

use Closure;
use InvalidArgumentException;
use Psr\Container\ContainerInterface;
use ReflectionFunction;
use ReflectionNamedType;
use ReflectionParameter;
use RuntimeException;
use YSOCode\Berry\Infra\Http\ClosureMiddlewareAdapter;
use YSOCode\Berry\Infra\Http\MiddlewareInterface;
use YSOCode\Berry\Infra\Http\RequestHandlerInterface;
use YSOCode\Berry\Infra\Http\Response;
use YSOCode\Berry\Infra\Http\ServerRequest;

final readonly class Middleware
{
    /**
     * @var class-string<MiddlewareInterface>|MiddlewareInterface|Closure(ServerRequest, RequestHandlerInterface): Response
     */
    public string|MiddlewareInterface|Closure $value;

    /**
     * @param  class-string<MiddlewareInterface>|MiddlewareInterface|Closure(ServerRequest, RequestHandlerInterface): Response  $value
     */
    public function __construct(string|MiddlewareInterface|Closure $value)
    {
        $isValid = self::validate($value);
        if ($isValid instanceof Error) {
            throw new InvalidArgumentException((string) $isValid);
        }

        $this->value = $value;
    }

    /**
     * @param  class-string<MiddlewareInterface>|MiddlewareInterface|Closure(ServerRequest, RequestHandlerInterface): Response  $value
     */
    public static function isValid(string|MiddlewareInterface|Closure $value): bool
    {
        return self::validate($value) === true;
    }

    /**
     * @param  class-string<MiddlewareInterface>|MiddlewareInterface|Closure(ServerRequest, RequestHandlerInterface): Response  $value
     */
    private static function validate(string|MiddlewareInterface|Closure $value): true|Error
    {
        if (is_string($value)) {
            return self::validateClassName($value);
        }

        if ($value instanceof Closure) {
            return self::validateClosure($value);
        }

        return true;
    }

    private static function validateClassName(string $className): true|Error
    {
        if ($className === '') {
            return new Error('Middleware cannot be empty.');
        }

        if (! class_exists($className)) {
            return new Error(sprintf('Middleware "%s" does not exist.', $className));
        }

        if (! is_subclass_of($className, MiddlewareInterface::class)) {
            return new Error(sprintf(
                'Middleware "%s" must implement %s.',
                $className,
                MiddlewareInterface::class
            ));
        }

        return true;
    }

    private static function validateClosure(Closure $closure): true|Error
    {
        $reflection = new ReflectionFunction($closure);

        if ($reflection->getNumberOfParameters() !== 2) {
            return new Error('Must accept exactly 2 parameters (ServerRequest, RequestHandlerInterface).');
        }

        [$first, $second] = array_pad($reflection->getParameters(), 2, null);
        if (! $first instanceof ReflectionParameter || ! $second instanceof ReflectionParameter) {
            return new Error('Must accept exactly 2 parameters (ServerRequest, RequestHandlerInterface).');
        }

        $firstType = $first->getType();
        if (! $firstType instanceof ReflectionNamedType || $firstType->getName() !== ServerRequest::class) {
            return new Error('First parameter of the middleware should be an instance of ServerRequest.');
        }

        $secondType = $second->getType();
        if (! $secondType instanceof ReflectionNamedType || $secondType->getName() !== RequestHandlerInterface::class) {
            return new Error('Second parameter of the middleware should be an instance of RequestHandlerInterface.');
        }

        $returnType = $reflection->getReturnType();
        if (! $returnType instanceof ReflectionNamedType || $returnType->getName() !== Response::class) {
            return new Error('The middleware function must return an instance of Response.');
        }

        return true;
    }

    public function resolve(ContainerInterface $container): MiddlewareInterface
    {
        if ($this->value instanceof MiddlewareInterface) {
            return $this->value;
        }

        if ($this->value instanceof Closure) {
            return new ClosureMiddlewareAdapter($this->value);
        }

        $resolved = $container->get($this->value);
        if (! $resolved instanceof MiddlewareInterface) {
            throw new RuntimeException(sprintf(
                'Middleware must implement MiddlewareInterface, got %s',
                get_debug_type($resolved)
            ));
        }

        return $resolved;
    }
}
