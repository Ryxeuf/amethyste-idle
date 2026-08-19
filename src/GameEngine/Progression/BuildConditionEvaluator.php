<?php

namespace App\GameEngine\Progression;

use App\Entity\App\Player;
use App\Entity\App\PlayerItem;
use App\GameEngine\Gear\WornPieceReader;

/**
 * Une condition de build, confrontee a l'equipement reel (ARC-16b).
 *
 * ARC-12a a donne aux conditions une grammaire fermee, ARC-12b un ecran — mais
 * personne ne repondait a la question qu'elles posent : *« ce joueur porte-t-il
 * une dague ? »*. C'etait le blocage nomme d'ARC-16a, et il fermait deux choses
 * a la fois : les passifs conditionnels restaient actifs quelle que soit la
 * tenue (un « +9 % a la dague » parlait a mains nues), et la forme
 * `condition_widening` des accointances n'avait rien a elargir.
 *
 * ## D'ou vient la reponse
 *
 * **De l'echelle de port, jamais d'une table parallele** — la meme discipline
 * que le croisement OBJ d'ARC-12b : porter une epee passe par les echelons de
 * port et par eux seuls, donc une piece **est** de la famille dont elle exige un
 * echelon. C'est `EquipmentPortCatalog::familyOfPortSkill()` qui le sait deja ;
 * ecrire une seconde table aurait rejoue le defaut nomme par ARC-08a — *une
 * regle recopiee derive de son original en silence*.
 *
 * ## Ce que l'accointance elargit
 *
 * La forme `condition_widening` (§ 9.7) s'evalue **ici et nulle part ailleurs** :
 * quand la condition n'est pas portee, ses elargissements actifs le sont
 * peut-etre — *les passifs conditionnes « en cuir » sont aussi satisfaits par la
 * plaque*. Un seul niveau, jamais de chaine : un elargissement qui en appellerait
 * un autre transformerait une souplesse en graphe, et un graphe en surprise.
 *
 * ## Ce que ce service refuse
 *
 * Les conditions de **combat** : elles ne se remplissent pas a l'inventaire, et
 * les evaluer ici avec un « toujours vrai » silencieux serait exactement le
 * mensonge que la grammaire d'ARC-12a existe pour fermer. Le refus est une
 * exception, pas un `false` — un appelant qui pose la mauvaise question doit
 * l'entendre.
 */
class BuildConditionEvaluator
{
    public function __construct(
        private readonly EquipmentPortCatalog $portCatalog,
        private readonly SynergyCalculator $synergyCalculator,
        private readonly WornPieceReader $wornPieces,
    ) {
    }

    /**
     * La condition est-elle satisfaite par ce que le joueur porte ?
     *
     * Elargissements d'accointance compris — c'est le lecteur de la forme
     * `condition_widening`.
     *
     * @throws CombatLeverDefinitionException si la condition n'est pas une condition de build
     */
    public function isSatisfied(Player $player, SkillCondition $condition): bool
    {
        if (!$condition->isBuild()) {
            throw new CombatLeverDefinitionException(sprintf('"%s" is a combat condition: it is not answered by the inventory, and pretending otherwise would leave it silently always-true.', $condition->raw));
        }

        if ($this->isWorn($player, $condition)) {
            return true;
        }

        // ARC-16b — l'elargissement, un seul niveau. La table ne contient que
        // les accointances **actives** : l'activation s'est decidee dans
        // `SynergyCalculator`, pas ici.
        foreach ($this->synergyCalculator->conditionWidenings($player)[$condition->raw] ?? [] as $widenedBy) {
            if ($this->isWorn($player, SkillCondition::parse($widenedBy))) {
                return true;
            }
        }

        return false;
    }

    /**
     * La condition, lue sur l'equipement seul — sans accointance.
     */
    private function isWorn(Player $player, SkillCondition $condition): bool
    {
        return match ($condition->raw) {
            SkillCondition::SHIELD => $this->wearsFamily($player, 'shield'),
            SkillCondition::OFFHAND_FREE => !$this->holdsAt($player, PlayerItem::GEAR_SIDE_WEAPON),
            SkillCondition::DUAL_WIELD => $this->wieldsWeaponAt($player, PlayerItem::GEAR_MAIN_WEAPON)
                && $this->wieldsWeaponAt($player, PlayerItem::GEAR_SIDE_WEAPON),
            default => $condition->subject !== null && $this->wearsFamily($player, $condition->subject),
        };
    }

    private function wearsFamily(Player $player, string $family): bool
    {
        foreach ($this->equippedItems($player) as $item) {
            if ($this->familyOf($item) === $family) {
                return true;
            }
        }

        return false;
    }

    private function holdsAt(Player $player, int $gearBit): bool
    {
        foreach ($this->equippedItems($player) as $item) {
            if (($item->getGear() & $gearBit) !== 0) {
                return true;
            }
        }

        return false;
    }

    /**
     * Une **arme** a cet emplacement — un bouclier au bras gauche n'est pas une
     * seconde lame, et c'est l'echelle qui les separe (`line`).
     */
    private function wieldsWeaponAt(Player $player, int $gearBit): bool
    {
        $families = $this->portCatalog->families();

        foreach ($this->equippedItems($player) as $item) {
            if (($item->getGear() & $gearBit) === 0) {
                continue;
            }

            $family = $this->familyOf($item);
            if ($family !== null && ($families[$family]['line'] ?? null) === 'weapon') {
                return true;
            }
        }

        return false;
    }

    /**
     * La famille d'une piece, lue par le lecteur unique (ARC-19).
     *
     * La regle vivait ici, en prive : *une piece est de la famille dont elle
     * exige un echelon*. La mitigation d'armure pose la meme question, et
     * l'ecrire deux fois l'aurait laissee deriver — d'ou `WornPieceReader`.
     */
    private function familyOf(PlayerItem $item): ?string
    {
        return $this->wornPieces->familyOf($item);
    }

    /**
     * @return iterable<PlayerItem>
     */
    private function equippedItems(Player $player): iterable
    {
        return $this->wornPieces->equippedItems($player);
    }
}
