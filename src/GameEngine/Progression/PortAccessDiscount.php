<?php

namespace App\GameEngine\Progression;

use App\Entity\App\Player;
use App\Entity\Game\Skill;

/**
 * Ce qu'un echelon de port coute **a ce joueur** (ARC-16b).
 *
 * Le lecteur de la forme `access_discount` du canon (§ 9.7) : *« l'echelon 3 de
 * port de l'arc coute un palier de moins »*. Trois decisions, et chacune ferme
 * une porte :
 *
 * - **La remise est fixe par la regle** — un barreau sur `SkillCostScale`,
 *   jamais un nombre en donnees. Une accointance qui porterait « −37 » serait un
 *   bonus deguise ; celle-ci ne porte qu'une famille.
 * - **Seul l'echelon 3 est remisable.** L'entree est gratuite par regle
 *   (`rung1.free`), et remiser l'echelon 2 ferait de l'accointance un raccourci
 *   de milieu d'echelle — le canon nomme le dernier barreau, celui qui coute.
 * - **Un seul chiffre, trois moments.** Le refus (`PlayerSkillHelper`), la
 *   depense (`CrossDomainSkillResolver`) et l'affichage lisent tous ce service :
 *   un cout verifie a 25 et debite a 50 serait le pire des mensonges, celui que
 *   le joueur ne decouvre qu'a son solde. Le respec, lui, n'a rien a lire — il
 *   remet la depense a zero en bloc, pas nœud par nœud.
 *
 * L'activation est **monotone** : elle se joue sur l'XP totale des deux ecoles,
 * qui ne redescend jamais (le respec rend l'XP utilisee, pas la totale). Une
 * remise acquise ne se perd donc pas entre le refus et la depense.
 */
class PortAccessDiscount
{
    public function __construct(
        private readonly EquipmentPortCatalog $portCatalog,
        private readonly SynergyCalculator $synergyCalculator,
    ) {
    }

    /**
     * Le cout reel du nœud pour ce joueur — remise d'accointance comprise.
     */
    public function effectiveRequiredPointsOf(Player $player, Skill $skill): int
    {
        $cost = $skill->getRequiredPoints();
        if ($cost === 0) {
            return 0;
        }

        $family = $this->portCatalog->familyOfRungThree($skill->getSlug());
        if ($family === null) {
            return $cost;
        }

        if (!\in_array($family, $this->synergyCalculator->accessDiscountFamilies($player), true)) {
            return $cost;
        }

        return SkillCostScale::rungBelow($cost);
    }
}
