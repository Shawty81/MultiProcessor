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

    public function removeChild(int $pid): Child
    {
        $child = $this->children[$pid];

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
