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
use YSOCode\Berry\Infra\Http\ClosureHandlerAdapter;
use YSOCode\Berry\Infra\Http\RequestHandlerInterface;
use YSOCode\Berry\Infra\Http\Response;
use YSOCode\Berry\Infra\Http\ServerRequest;

final readonly class RequestHandler
{
    /**
     * @var class-string<RequestHandlerInterface>|RequestHandlerInterface|Closure(ServerRequest): Response
     */
    public string|RequestHandlerInterface|Closure $value;

    /**
     * @param  class-string<RequestHandlerInterface>|RequestHandlerInterface|Closure(ServerRequest $request): Response  $value
     */
    public function __construct(string|RequestHandlerInterface|Closure $value)
    {
        $isValid = self::validate($value);
        if ($isValid instanceof Error) {
            throw new InvalidArgumentException((string) $isValid);
        }

        $this->value = $value;
    }

    /**
     * @param  class-string<RequestHandlerInterface>|RequestHandlerInterface|Closure(ServerRequest $request): Response  $value
     */
    public static function isValid(string|RequestHandlerInterface|Closure $value): bool
    {
        return self::validate($value) === true;
    }

    /**
     * @param  class-string<RequestHandlerInterface>|RequestHandlerInterface|Closure(ServerRequest $request): Response  $value
     */
    private static function validate(string|RequestHandlerInterface|Closure $value): true|Error
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
            return new Error('Request handler cannot be empty.');
        }

        if (! class_exists($className)) {
            return new Error(sprintf('Request handler "%s" does not exist.', $className));
        }

        if (! is_subclass_of($className, RequestHandlerInterface::class)) {
            return new Error(sprintf(
                'Request handler "%s" must implement %s.',
                $className,
                RequestHandlerInterface::class
            ));
        }

        return true;
    }

    private static function validateClosure(Closure $closure): true|Error
    {
        $reflection = new ReflectionFunction($closure);

        if ($reflection->getNumberOfParameters() !== 1) {
            return new Error('Must accept exactly 1 parameter (ServerRequest).');
        }

        $first = array_first($reflection->getParameters());
        if (! $first instanceof ReflectionParameter) {
            return new Error('Must accept exactly 1 parameter (ServerRequest).');
        }

        $firstType = $first->getType();
        if (! $firstType instanceof ReflectionNamedType || $firstType->getName() !== ServerRequest::class) {
            return new Error('First parameter of the request handler should be an instance of ServerRequest.');
        }

        $returnType = $reflection->getReturnType();
        if (! $returnType instanceof ReflectionNamedType || $returnType->getName() !== Response::class) {
            return new Error('The request handler function must return an instance of Response.');
        }

        return true;
    }

    public function resolve(ContainerInterface $container): RequestHandlerInterface
    {
        if ($this->value instanceof RequestHandlerInterface) {
            return $this->value;
        }

        if ($this->value instanceof Closure) {
            return new ClosureHandlerAdapter($this->value);
        }

        $resolved = $container->get($this->value);
        if (! $resolved instanceof RequestHandlerInterface) {
            throw new RuntimeException(sprintf(
                'Request handler must implement RequestHandlerInterface, got %s',
                get_debug_type($resolved)
            ));
        }

        return $resolved;
    }
}
