<?php

namespace App\GameEngine\Zone;

use App\Enum\Purity;

/**
 * Vue d'une ressource recoltable d'une zone (ZON-10), destinee a l'affichage
 * de l'ecran de zone : etat du filon partage (stock/capacite, epuisement,
 * respawn restant) resolu au moment de la lecture, sans effet de bord.
 *
 * ECO-22 ajoute `purityCeiling` : la bande maximale que le filon peut rendre en
 * l'etat. C'est l'**information exclusive** du prospecteur
 * (GAME_ZONE_ACTIONS § 5.5) — elle ne donne ni energie, ni action, ni butin,
 * elle donne de la **decision**. Nulle pour qui n'a pas travaille son arbre de
 * recolte, et nulle aussi pour les matieres fongibles, qui n'ont pas de bande.
 *
 * ECO-24c ajoute `lockedBy` : le nom de la competence qui manque au joueur pour
 * exploiter le filon, ou `null` s'il peut l'exploiter. Le filon **reste
 * visible** — la zone montre ce que le personnage sait (GAME_ZONE_ACTIONS § 2),
 * et un filon qu'on voit sans pouvoir l'ouvrir est une raison de progresser ;
 * un filon cache n'est rien du tout.
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
        public ?Purity $purityCeiling = null,
        public ?string $lockedBy = null,
    ) {
    }

    public function isDepleted(): bool
    {
        return $this->stock <= 0;
    }

    public function isLocked(): bool
    {
        return null !== $this->lockedBy;
    }
}
