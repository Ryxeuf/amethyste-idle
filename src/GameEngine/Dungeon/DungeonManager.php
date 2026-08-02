<?php

namespace App\GameEngine\Dungeon;

use App\Entity\App\Player;
use App\Entity\Game\Dungeon;

/**
 * Le verificateur de prerequis d'entree en donjon.
 *
 * DON-01b : l'ancien gestionnaire du chemin solo (entree par teleportation
 * sur une `Map`, `DungeonRun`, cooldown par difficulte) est supprime avec le
 * pivot PBBG — il ne reste que ce que le modele unique consomme : les deux
 * verifications que `GroupDungeonService` applique a chaque participant.
 */
class DungeonManager
{
    /**
     * Verifie si le joueur a assez d'experience pour le donjon : le seuil
     * (`Dungeon::getRequiredExperience()`) doit etre atteint dans au moins un
     * domaine.
     */
    public function meetsLevelRequirement(Player $player, Dungeon $dungeon): bool
    {
        $requiredXp = $dungeon->getRequiredExperience();

        foreach ($player->getDomainExperiences() as $domainExp) {
            if ($domainExp->getTotalExperience() >= $requiredXp) {
                return true;
            }
        }

        return $requiredXp <= 0;
    }

    /**
     * Retourne la liste des noms d'items manquants pour entrer dans le donjon.
     *
     * @return string[] noms d'items manquants (vide si tout est bon)
     */
    public function getMissingEntryItems(Player $player, Dungeon $dungeon): array
    {
        $requirements = $dungeon->getEntryRequirements();
        if ($requirements === null || !isset($requirements['items'])) {
            return [];
        }

        $ownedSlugs = [];
        foreach ($player->getInventories() as $inventory) {
            foreach ($inventory->getItems() as $playerItem) {
                $ownedSlugs[$playerItem->getGenericItem()->getSlug()] = true;
            }
        }

        $missing = [];
        foreach ($requirements['items'] as $entry) {
            $slug = $entry['slug'] ?? '';
            if (!isset($ownedSlugs[$slug])) {
                $missing[] = $entry['name'] ?? $slug;
            }
        }

        return $missing;
    }
}
