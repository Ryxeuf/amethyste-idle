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
    public int $mobsCreated = 0;
    public int $pnjsCreated = 0;
    public int $pnjsUpdated = 0;

    /**
     * Entites de monde (joueur, mob, PNJ, calque) rattachees a leur zone au
     * passage — celles que leur carte rendait rattachables sans qu'aucune
     * commande ne l'ait jamais fait.
     */
    public int $entitiesReattached = 0;

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

    public function mobsTouched(): int
    {
        return $this->mobsCreated;
    }

    public function pnjsTouched(): int
    {
        return $this->pnjsCreated + $this->pnjsUpdated;
    }

    public function connectionsTouched(): int
    {
        return $this->connectionsCreated + $this->connectionsUpdated;
    }
}
