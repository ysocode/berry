<?php

declare(strict_types=1);

namespace YSOCode\Berry\Domain\Payloads;

use YSOCode\Berry\Domain\Entities\Route;
use YSOCode\Berry\Domain\Types\PathParameter;
use YSOCode\Berry\Domain\Types\PathParameterName;

final class ResolvedRoute
{
    /**
     * @var array<string, PathParameter>
     */
    public private(set) array $parameters = [];

    /**
     * @param  array<PathParameter>  $parameters
     */
    public function __construct(
        public readonly Route $route,
        array $parameters = [],
    ) {
        $this->setParameters($parameters);
    }

    /**
     * @param  array<PathParameter>  $parameters
     */
    private function setParameters(array $parameters): void
    {
        foreach ($parameters as $parameter) {
            $this->parameters[(string) $parameter->name] = $parameter;
        }
    }

    public function hasParameter(PathParameterName $name): bool
    {
        return isset($this->parameters[(string) $name]);
    }

    public function getParameter(PathParameterName $name): ?PathParameter
    {
        return $this->parameters[(string) $name] ?? null;
    }
}
