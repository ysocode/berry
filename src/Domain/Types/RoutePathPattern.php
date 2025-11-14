<?php

declare(strict_types=1);

namespace YSOCode\Berry\Domain\Types;

use InvalidArgumentException;
use RuntimeException;
use Stringable;

final readonly class RoutePathPattern implements Stringable
{
    public string $value;

    public function __construct(string $value)
    {
        $isValid = self::validate($value);
        if ($isValid instanceof Error) {
            throw new InvalidArgumentException((string) $isValid);
        }

        $this->value = $this->normalize($value);
    }

    private function normalize(string $value): string
    {
        if ($value === '/') {
            return $value;
        }

        return rtrim($value, '/');
    }

    public static function isValid(string $value): bool
    {
        return self::validate($value) === true;
    }

    private static function validate(string $value): true|Error
    {
        if (! str_starts_with($value, '/')) {
            return new Error('Route path pattern must start with "/".');
        }

        $pattern = '/^(?:[A-Za-z0-9\-._~!$&\'()*+,;=:@\/]|%[0-9A-Fa-f]{2}|\{\w+\})*$/';
        if (in_array(preg_match($pattern, $value), [0, false], true)) {
            return new Error('Route path pattern contains invalid characters.');
        }

        return true;
    }

    /**
     * @return array<string>
     */
    public function getSegments(): array
    {
        $segments = array_values(array_filter(explode('/', $this->value)));
        array_unshift($segments, '/');

        return $segments;
    }

    /**
     * @return array<PathParameter>
     */
    public function getParameters(UriPath $path): array
    {
        $pathSegments = $path->getSegments();
        $pathPatternSegments = $this->getSegments();

        $pathSegmentsCount = count($pathSegments);
        $pathPatternSegmentsCount = count($pathPatternSegments);

        if ($pathSegmentsCount !== $pathPatternSegmentsCount) {
            throw new RuntimeException(sprintf(
                'Cannot extract parameters: path "%s" does not match pattern "%s". Segment count mismatch (%d vs %d).',
                (string) $path,
                (string) $this,
                $pathSegmentsCount,
                $pathPatternSegmentsCount,
            ));
        }

        $parameters = [];
        foreach ($pathPatternSegments as $index => $pathPatternSegment) {
            if (str_starts_with($pathPatternSegment, '{') && str_ends_with($pathPatternSegment, '}')) {
                $parameters[] = new PathParameter(new PathParameterName($pathPatternSegment), $pathSegments[$index]);
            }
        }

        return $parameters;
    }

    public function prepend(self $other): self
    {
        $otherValue = rtrim($other->value, '/');
        $current = '/'.ltrim($this->value, '/');

        return new self($otherValue.$current);
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
