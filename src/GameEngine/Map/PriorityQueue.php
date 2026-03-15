<?php

namespace App\GameEngine\Map;

/**
 * This priority queue is based on the 'heap' concept (a 'heap' is an ordered stack) and is
 * implemented using a binary tree.
 * It's first element should be 1.
 *
 * @author Oscar Pascual <oscar.pascual@gmail.com>
 */
class PriorityQueue implements PriorityQueueInterface
{
    // The queue itself.  It is a bi-dimensional array.
    //    private array $queue;

    // The number of elements inside the queue.
    //    private int $numElements;

    // A hashmap to track the positions of all the elements inside the queue (performance trick).
    //    private $hashmap;

    // The hash algorithm.  By default, 'crc32b'.
    //    private string $algorithm;

    /**
     * Create a new queue (an array) without elements in it.
     * Create the hashmap.
     */
    public function __construct(private array $queue = [], private int $numElements = 0, private array $hashmap = [], private readonly string $algorithm = 'crc32b')
    {
    }

    /**
     * Returns a boolean indicating wether the queue is empty or not.
     * (true = empty).
     */
    public function isEmpty(): bool
    {
        return $this->numElements == 0;
    }

    /**
     * This method returns a boolean indicating wether an element is present or not in the priority queue.
     * For speed, this is done using a hashmap.
     */
    public function contains($element): bool
    {
        return isset($this->hashmap[hash($this->algorithm, $element)]);
    }

    /**
     * Push an element into the priority queue.  The process is:
     *   1. Get next free position (last)
     *   2. Get father's position
     *   3. Insert or swith positions.
     *
     * @param int $priority
     */
    public function push($element, $priority): bool
    {
        // Why is the first element treated differently?  Because otherwise the first element
        // in the queue would have index 0 (zero), and that is not desired.  ;-)
        if ($this->isEmpty()) {
            $this->numElements = 1;
            $this->queue[1]['element'] = $element;
            $this->queue[1]['priority'] = $priority;
            $this->queue[1]['timestamp'] = time();
            $this->hashmap[hash($this->algorithm, $element)] = 1;

            return true;
        }

        ++$this->numElements;

        // New elements are placed in last position, and then "up-heaped".
        $this->up_heap($element, $this->numElements, $priority);

        return true;
    }

    /**
     * Get the first element of the priority queue.
     * In order to have the correct priority queue, we get the last element and insert it in
     * the first position.  After that we "down-heap" it to it's correct position.
     */
    public function pop()
    {
        // What if we perform a pop on an empty queue?  Throw exception.
        if ($this->isEmpty()) {
            throw new EmptyQueueException('Queue is empty');
        }

        $first_element = $this->queue[1];
        $last_element = $this->queue[$this->numElements];

        --$this->numElements;
        array_pop($this->queue);

        // It's also necessary to unset the element's position inside the hashmap.
        unset($this->hashmap[hash($this->algorithm, $first_element['element'])]);

        // The last element is placed on top of the heap, and then "down-heaped".
        if ($this->numElements > 0) {
            $this->down_heap($last_element['element'], 1, $last_element['priority']);
        }

        return $first_element['element'];
    }

    /**
     * Change one element's priority.
     * This is done by searching the element in the queue, change it's priority, and move it
     * up or down to it's correct place.
     *
     * Possible cases:
     *    a) it's the top element of the priority queue and new priority is higher: do nothing!
     *    b) it's the top element of the priority queue and new priority is lower: perform down-heap.
     *    c) it's the last element of the priority queue and new priority is higher: perform up-heap.
     *    d) it's the last element of the priority queue and new priority is lower: do nothing!
     *    e) it's an intermediate element: check what operation to perform.
     *
     * Important: cost for this operation is O(log n) in worst case, thanks to the hashmap.
     */
    public function change_priority($element, $new_priority): bool
    {
        // Get the position of the element in the queue.  Must be present, or return false.
        $pos = $this->hashmap[hash($this->algorithm, $element)];

        if ($pos) {
            // It's the first element, and the new priority is lower than current
            // Element must be down-heaped
            // Otherwise... do nothing.
            if (($pos == 1) && ($this->queue[$pos]['priority'] < $new_priority)) {
                $this->down_heap($element, $pos, $new_priority);

                return true;
            } elseif (($pos == 1) && ($this->queue[$pos]['priority'] > $new_priority)) {
                return true;

            // It's the last element, and the new priority is higher than current
            // Element must be up-heaped
            // Otherwise... do nothing.
            } elseif (($pos == $this->numElements) && ($this->queue[$pos]['priority'] > $new_priority)) {
                $this->up_heap($element, $pos, $new_priority);

                return true;
            } elseif (($pos == $this->numElements) && ($this->queue[$pos]['priority'] < $new_priority)) {
                return true;

            }
            // Element is somewhere in the queue... let's check father's priority and decide.
            $fathers_position = intdiv($pos, 2);

            // And now, if father's priority is higher, we need to up-heap the element.  If not,
            // we perform a down-heap without the need to check child's priority.
            if ($new_priority < $this->queue[$fathers_position]['priority']) {
                $this->up_heap($element, $pos, $new_priority);
            } else {
                $this->down_heap($element, $pos, $new_priority);
            }

            return true;

        }

        return false;

    }

    /**
     * Eliminate all the queue elements, set num_elements to zero and empty the hashmap.
     */
    public function purge(): void
    {
        array_splice($this->queue, 0);
        $this->numElements = 0;

        array_splice($this->hashmap, 0);
    }

    /**
     * Returns the number of elements in the queue.
     */
    public function count(): int
    {
        return $this->numElements;
    }

    /**
     * This method pushes down one particular element in the heap from its current position.
     * It also updates its hashmap position.
     *
     * @param mixed $element  the element to move down in the heap
     * @param int   $pos      position where the element is now
     * @param int   $priority The new priority for the element
     */
    private function down_heap($element, $pos, $priority): void
    {
        // Point to the first child
        $top = $pos;
        $tops_son = $top * 2;

        // If first son exists and sibling's priority is higher, point to the sibling
        if (($tops_son < $this->numElements) && ($this->queue[$tops_son + 1]['priority'] < $this->queue[$tops_son]['priority'])) {
            ++$tops_son;
        }

        // While son exists and priority is higher than last element, change!
        while (($tops_son <= $this->numElements) && ($this->queue[$tops_son]['priority'] < $priority)) {
            $this->queue[$top] = $this->queue[$tops_son];
            $this->hashmap[hash($this->algorithm, $this->queue[$top]['element'])] = $top;

            $top = $tops_son;
            $tops_son = $top * 2;

            // Again, if first son exists and sibling's priority is higher, point to the sibling
            if (($tops_son < $this->numElements) && ($this->queue[$tops_son + 1]['priority'] < $this->queue[$tops_son]['priority'])) {
                ++$tops_son;
            }
        }

        // Final destination for the element is found.
        $this->queue[$top]['element'] = $element;
        $this->queue[$top]['priority'] = $priority;
        $this->queue[$top]['timestamp'] = time();
        $this->hashmap[hash($this->algorithm, $element)] = $top;

        $this->hashmap[hash($this->algorithm, $this->queue[$pos]['element'])] = $pos;
    }

    /**
     * This method lifts up one particular element in the heap from its current position.
     * It also updates its hashmap position.
     *
     * @param mixed $element  the element to raise up in the heap
     * @param int   $pos      position where the element is now
     * @param int   $priority The new priority for the element
     *
     * @return void
     */
    private function up_heap($element, $pos, $priority)
    {
        $next_position = $pos;        // Pointer to the next free position in the queue.
        $fathers_position = intdiv($next_position, 2);  // Obtain father's position of the new element.

        while (($fathers_position > 0)
               && ($this->queue[$fathers_position]['priority'] > $priority)) {
            $this->queue[$next_position] = $this->queue[$fathers_position];
            $this->hashmap[hash($this->algorithm, $this->queue[$next_position]['element'])] = $next_position;

            $next_position = $fathers_position;
            $fathers_position = intdiv($next_position, 2);
        }

        // Final destination for the element is found.
        $this->queue[$next_position]['element'] = $element;
        $this->queue[$next_position]['priority'] = $priority;
        $this->queue[$next_position]['timestamp'] = time();
        $this->hashmap[hash($this->algorithm, $element)] = $next_position;

        // Reset previous element position in the hashmap.
        $this->hashmap[hash($this->algorithm, $this->queue[$pos]['element'])] = $pos;
    }

    /**
     * Get the position of one element in cost O(1).  :-).
     *
     * @return int|bool
     */
    private function getPosition($element)
    {
        $position = hash($this->algorithm, $element);
        if (isset($this->hashmap[$position])) {
            return $this->hashmap[$position];
        }

        return false;

    }

    /**
     * Just a help function to print out the queue.
     */
    public function print(): void
    {
        for ($i = 1; $i <= $this->numElements; ++$i) {
            echo $i . ' - ' . $this->queue[$i]['priority'] . ' - ' . $this->queue[$i]['element'] . "\n";
        }
    }
}
