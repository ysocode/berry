<?php

declare(strict_types=1);

namespace YSOCode\Berry;

use InvalidArgumentException;
use LogicException;

final readonly class Handler
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
            return new Error("Handler class {$class} does not exist.");
        }

        if (! method_exists($class, $method)) {
            return new Error("Method {$class}::{$method} does not exist.");
        }

        return true;
    }

    public function equals(self $other): bool
    {
        return $this->class === $other->class && $this->method === $other->method;
    }

    public function invoke(Request $request): Response
    {
        $instance = new $this->class;

        $method = $this->method;

        $response = $instance->$method($request);

        if (! $response instanceof Response) {
            throw new LogicException("Handler method {$this->class}::{$this->method} must return an instance of Response.");
        }

        return $response;
    }
}
