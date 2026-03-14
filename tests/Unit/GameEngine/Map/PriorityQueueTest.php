<?php

namespace App\Tests\Unit\GameEngine\Map;

use App\GameEngine\Map\EmptyQueueException;
use App\GameEngine\Map\PriorityQueue;
use PHPUnit\Framework\TestCase;

class PriorityQueueTest extends TestCase
{
    public function testNewQueueIsEmpty(): void
    {
        $queue = new PriorityQueue();

        $this->assertTrue($queue->isEmpty());
        $this->assertSame(0, $queue->count());
    }

    public function testPushAndPop(): void
    {
        $queue = new PriorityQueue();
        $queue->push('A', 10);
        $queue->push('B', 5);
        $queue->push('C', 15);

        $this->assertFalse($queue->isEmpty());
        $this->assertSame(3, $queue->count());

        // Le plus petit priority sort en premier (min-heap)
        $this->assertSame('B', $queue->pop());
        $this->assertSame('A', $queue->pop());
        $this->assertSame('C', $queue->pop());
        $this->assertTrue($queue->isEmpty());
    }

    public function testContains(): void
    {
        $queue = new PriorityQueue();
        $queue->push('node_1', 10);

        $this->assertTrue($queue->contains('node_1'));
        $this->assertFalse($queue->contains('node_2'));
    }

    public function testContainsAfterPop(): void
    {
        $queue = new PriorityQueue();
        $queue->push('A', 1);
        $queue->pop();

        $this->assertFalse($queue->contains('A'));
    }

    public function testPopOnEmptyQueueThrows(): void
    {
        $queue = new PriorityQueue();

        $this->expectException(EmptyQueueException::class);
        $queue->pop();
    }

    public function testChangePriority(): void
    {
        $queue = new PriorityQueue();
        $queue->push('A', 10);
        $queue->push('B', 20);
        $queue->push('C', 30);

        // Réduire la priorité de C pour qu'il sorte en premier
        $queue->change_priority('C', 1);

        $this->assertSame('C', $queue->pop());
    }

    public function testPurge(): void
    {
        $queue = new PriorityQueue();
        $queue->push('A', 1);
        $queue->push('B', 2);

        $queue->purge();

        $this->assertTrue($queue->isEmpty());
        $this->assertSame(0, $queue->count());
    }

    public function testSingleElement(): void
    {
        $queue = new PriorityQueue();
        $queue->push('only', 42);

        $this->assertSame(1, $queue->count());
        $this->assertSame('only', $queue->pop());
        $this->assertTrue($queue->isEmpty());
    }

    public function testSamePriority(): void
    {
        $queue = new PriorityQueue();
        $queue->push('A', 5);
        $queue->push('B', 5);
        $queue->push('C', 5);

        $this->assertSame(3, $queue->count());

        // Les 3 éléments doivent sortir, l'ordre entre eux n'est pas garanti
        $results = [$queue->pop(), $queue->pop(), $queue->pop()];
        sort($results);
        $this->assertSame(['A', 'B', 'C'], $results);
    }
}
