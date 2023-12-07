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

    public function removeChild(int $pid): void
    {
        unset($this->children[$pid]);
    }

    public function numberOfChildren(): int
    {
        return count($this->children);
    }
}
