<?php

namespace App\GameEngine\Zone;

/**
 * Vue d'une ressource recoltable d'une zone (ZON-10), destinee a l'affichage
 * de l'ecran de zone : etat du filon partage (stock/capacite, epuisement,
 * respawn restant) resolu au moment de la lecture, sans effet de bord.
 */
final readonly class GatherableResource
{
    public function __construct(
        public string $slug,
        public string $itemName,
        public string $itemSlug,
        public string $profession,
        public int $stock,
        public int $capacity,
        public int $respawnRemaining,
    ) {
    }

    public function isDepleted(): bool
    {
        return $this->stock <= 0;
    }
}
