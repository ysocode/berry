<?php

declare(strict_types=1);

namespace YSOCode\Berry\Domain\Entities;

use Closure;
use UnitEnum;

/**
 * @phpstan-template TSelf of object
 * @phpstan-template TEvent of UnitEnum
 */
trait EventTrait
{
    /** @var array<string, array<Closure(TSelf, array<string, mixed>): void>> */
    private array $listeners = [];

    /**
     * @param  TEvent  $event
     * @param  Closure(TSelf, array<string, mixed>): void  $listener
     */
    public function on(UnitEnum $event, Closure $listener): static
    {
        $this->listeners[$event->name][] = $listener;

        return $this;
    }

    /**
     * @param  TEvent  $event
     * @param  array<string, mixed>  $data
     */
    private function emit(UnitEnum $event, array $data = []): void
    {
        foreach ($this->listeners[$event->name] ?? [] as $listener) {
            /** @var Closure(TSelf, array<string, mixed>): void $listener */
            $listener($this, $data);
        }
    }
}
