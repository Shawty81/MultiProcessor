<?php

namespace MultiProcessor\ChildrenPool;

final class ChildrenPool
{
    /**
     * @var array<int, Child>
     */
    private array $children = [];

    public function addChild(Child $child): void
    {
        $this->children[$child->pid] = $child;
    }

    /**
     * Returns null when the pid is not in the pool, which happens whenever something
     * other than one of our own children is reaped. Callers have to handle that.
     */
    public function removeChild(int $pid): ?Child
    {
        $child = $this->children[$pid] ?? null;

        unset($this->children[$pid]);

        return $child;
    }

    public function numberOfChildren(): int
    {
        return count($this->children);
    }

    /**
     * @return Child[]
     */
    public function getChildren(): array
    {
        return array_values($this->children);
    }
}
