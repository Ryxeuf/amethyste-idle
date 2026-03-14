<?php

namespace App\Dto\CellAction;

class HarvestOutput
{
    public bool $success = false;

    /** @var array<array{name: string, slug: string}> */
    public array $items = [];

    public string $message = '';

    public ?int $respawnDelay = null;

    public bool $toolBroken = false;
}
