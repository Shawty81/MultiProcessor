<?php

namespace MultiProcessor\Tests\ChildrenPool;

use MultiProcessor\ChildrenPool\Child;
use MultiProcessor\ChildrenPool\ChildrenPool;
use PHPUnit\Framework\TestCase;

class ChildrenPoolTest extends TestCase
{
    /**
     * @test
     */
    public function itAddsRemovesAndCountsChildren(): void
    {
        $pool = new ChildrenPool();

        $pool->addChild(new Child(1, ['1']));
        $pool->addChild(new Child(2, ['2']));

        $this->assertSame(2, $pool->numberOfChildren());

        $pool->removeChild(1);

        $this->assertSame(1, $pool->numberOfChildren());

        $pool->removeChild(2);

        $this->assertSame(0, $pool->numberOfChildren());
    }
}
