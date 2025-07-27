<?php

declare(strict_types=1);

namespace YSOCode\Berry;

use Closure;
use InvalidArgumentException;
use LogicException;
use ReflectionMethod;
use ReflectionNamedType;

final readonly class Middleware
{
    public string $class;

    public string $method;

    /**
     * @param  class-string  $class
     */
    public function __construct(string $class, string $method)
    {
        $validation = self::validate($class, $method);
        if ($validation instanceof Error) {
            throw new InvalidArgumentException((string) $validation);
        }

        $this->class = $class;
        $this->method = $method;
    }

    /**
     * @param  class-string  $class
     */
    public static function isValid(string $class, string $method): bool
    {
        return self::validate($class, $method) === true;
    }

    /**
     * @param  class-string  $class
     */
    private static function validate(string $class, string $method): true|Error
    {
        if (! class_exists($class)) {
            return new Error("Middleware class {$class} does not exist.");
        }

        if (! method_exists($class, $method)) {
            return new Error("Method {$class}::{$method} does not exist.");
        }

        $reflection = new ReflectionMethod($class, $method);

        if (! $reflection->isPublic()) {
            return new Error("Middleware method {$class}::{$method} is not public.");
        }

        $params = $reflection->getParameters();
        if (count($params) !== 2) {
            return new Error("Middleware method {$class}::{$method} must accept exactly 2 parameters.");
        }

        $param1Type = $params[0]->getType();
        if (! $param1Type instanceof ReflectionNamedType || $param1Type->getName() !== Request::class) {
            return new Error("First parameter of {$class}::{$method} must be type-hinted as Request.");
        }

        $param2Type = $params[1]->getType();
        if (! $param2Type instanceof ReflectionNamedType || $param2Type->getName() !== Closure::class) {
            return new Error("Second parameter of {$class}::{$method} must be type-hinted as Closure.");
        }

        $returnType = $reflection->getReturnType();
        if (! $returnType instanceof ReflectionNamedType || $returnType->getName() !== Response::class) {
            return new Error("Middleware method {$class}::{$method} must have return type Response.");
        }

        return true;
    }

    public function equals(self $other): bool
    {
        return $this->class === $other->class && $this->method === $other->method;
    }

    /**
     * @param  Closure(Request): Response  $next
     */
    public function invoke(Request $request, Closure $next): Response
    {
        $instance = new $this->class;

        $method = $this->method;

        $response = $instance->$method($request, $next);

        if (! $response instanceof Response) {
            throw new LogicException("Middleware method {$this->class}::{$this->method} must return an instance of Response.");
        }

        return $response;
    }
}
