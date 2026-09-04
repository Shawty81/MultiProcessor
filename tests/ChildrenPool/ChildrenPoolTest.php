<?php

declare(strict_types=1);

namespace MultiProcessor\Tests\ChildrenPool;

use MultiProcessor\ChildrenPool\Child;
use MultiProcessor\ChildrenPool\ChildrenPool;
use MultiProcessor\Queue\Chunk;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class ChildrenPoolTest extends TestCase
{
    #[Test]
    public function itAddsRemovesAndCountsChildren(): void
    {
        $pool = new ChildrenPool();

        $pool->addChild(new Child(1, new Chunk(['1'])));
        $pool->addChild(new Child(2, new Chunk(['2'])));

        $this->assertSame(2, $pool->numberOfChildren());

        $pool->removeChild(1);

        $this->assertSame(1, $pool->numberOfChildren());

        $pool->removeChild(2);

        $this->assertSame(0, $pool->numberOfChildren());
    }

    #[Test]
    public function itGetsAllChildren(): void
    {
        $pool = new ChildrenPool();

        $expected = [
            new Child(1, new Chunk(['1'])),
            new Child(2, new Chunk(['2'])),
        ];

        $children = $pool->getChildren();
        $this->assertSame([], $children);

        $pool->addChild($expected[0]);
        $pool->addChild($expected[1]);

        $children = $pool->getChildren();
        $this->assertSame($expected, $children);

        $pool->removeChild(2);
        unset($expected[1]);

        $children = $pool->getChildren();
        $this->assertSame($expected, $children);

        $pool->removeChild(1);

        $children = $pool->getChildren();
        $this->assertSame([], $children);
    }

    #[Test]
    public function itReturnsNullWhenTheChildIsNotInThePool(): void
    {
        $pool = new ChildrenPool();

        $pool->addChild(new Child(1, new Chunk(['1'])));

        $this->assertNull($pool->removeChild(-1));
        $this->assertSame(1, $pool->numberOfChildren());
    }
}
