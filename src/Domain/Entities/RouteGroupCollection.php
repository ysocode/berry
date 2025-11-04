<?php

declare(strict_types=1);

namespace YSOCode\Berry\Domain\Entities;

final class RouteGroupCollection
{
    /**
     * @var array<RouteGroup>
     */
    public private(set) array $groups = [];

    public function addGroup(RouteGroup $group): void
    {
        $this->groups[] = $group;
    }

    public function propagateAll(): void
    {
        foreach ($this->groups as $group) {
            $group->propagate();
        }
    }
}
