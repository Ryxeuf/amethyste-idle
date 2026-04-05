<?php

namespace App\GameEngine\Map;

interface PriorityQueueInterface
{
    public function isEmpty(): bool;

    public function push($element, $priority): bool;

    public function pop();

    public function purge(): void;

    public function count(): int;

    public function contains($element): bool;

    public function change_priority($element, $new_priority): bool;
}
