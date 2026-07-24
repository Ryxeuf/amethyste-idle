<?php

namespace App\GameEngine\Zone;

/**
 * Bilan d'un import declaratif de zones (ZON-11).
 */
class ZoneImportReport
{
    public int $zonesCreated = 0;
    public int $zonesUpdated = 0;
    public int $connectionsCreated = 0;
    public int $connectionsUpdated = 0;

    /** @var list<string> */
    public array $warnings = [];

    public function addWarning(string $message): void
    {
        $this->warnings[] = $message;
    }

    public function zonesTouched(): int
    {
        return $this->zonesCreated + $this->zonesUpdated;
    }

    public function connectionsTouched(): int
    {
        return $this->connectionsCreated + $this->connectionsUpdated;
    }
}
