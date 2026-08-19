<?php

namespace App\GameEngine\Fight;

use App\Entity\App\Player;
use App\Entity\CharacterInterface;
use App\GameEngine\Gear\WornPieceReader;

/**
 * La part que l'armure d'un personnage retire aux coups recus (ARC-19).
 *
 * Le lecteur de `ArmorMitigationLaw` cote jeu : il lit **ce qui est porte**, la
 * loi dit ce que cela vaut. Les separer suit la discipline du chantier — une
 * loi se teste sans base de donnees, un lecteur se branche partout ou le degat
 * se calcule.
 *
 * **Seul un joueur mitige.** Un monstre porte sa defense dans ses statistiques
 * derivees (`MonsterStatTemplate`) ; lui appliquer en plus une ligne d'armure
 * ferait payer deux fois la meme chose, et la ligne n'aurait de toute facon
 * aucun sens — un loup ne porte pas de plaque.
 */
class ArmorMitigationResolver
{
    public function __construct(
        private readonly WornPieceReader $wornPieces,
    ) {
    }

    /**
     * La part mitigee par ce que ce personnage porte, entre 0 et la borne.
     */
    public function shareFor(CharacterInterface $character): float
    {
        if (!$character instanceof Player) {
            return 0.0;
        }

        return ArmorMitigationLaw::shareFor(
            $this->wornPieces->armorLinesWornBy($character, ArmorMitigationLaw::ARMOR_SLOTS),
        );
    }

    /**
     * Ce qui reste d'un coup une fois l'armure de la cible servie.
     */
    public function mitigate(int $damage, CharacterInterface $target): int
    {
        return ArmorMitigationLaw::mitigated($damage, $this->shareFor($target));
    }
}
