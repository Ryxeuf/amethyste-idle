<?php

namespace App\GameEngine\Progression;

use App\Entity\Game\Skill;
use App\Enum\CombatRegister;

/**
 * Ce que l'ecran des arbres dit d'un nœud passif (ARC-12b).
 *
 * Le lecteur (`SkillLeverReader`) dit ce qu'un nœud **est** ; ce presentateur
 * dit ce qu'un joueur en **lit**. Les separer evite le piege que le § 8 bis
 * nomme : un ecran qui affiche l'effet moyen ferait croire qu'un passif
 * conditionnel rend moins qu'il ne rend, et personne ne le prendrait.
 *
 * **Les libelles de famille viennent de l'echelle de port**, jamais d'une
 * table parallele : c'est elle qui nomme deja l'epee et la plaque, et une
 * famille renommee doit se renommer partout d'un coup.
 */
class SkillLeverPresenter
{
    /**
     * Ce qu'on ecrit pour les conditions qui ne nomment pas de famille.
     *
     * Les conditions de combat ne sont **pas** ici : l'ecran des arbres dit ce
     * qu'il faut **porter**, et « vous avez encaisse au tour precedent » ne se
     * porte pas. Elles se lisent en combat, ou elles ont un sens.
     */
    private const BUILD_LABELS = [
        SkillCondition::SHIELD => 'bouclier au bras',
        SkillCondition::OFFHAND_FREE => 'main gauche libre',
        SkillCondition::DUAL_WIELD => 'deux armes en main',
    ];

    public function __construct(
        private readonly SkillLeverReader $reader,
        private readonly CombatLeverScale $scale,
        private readonly EquipmentPortCatalog $portCatalog,
    ) {
    }

    /**
     * @return list<SkillLeverReadout>
     */
    public function readoutsOf(Skill $skill, ?CombatRegister $register = null): array
    {
        $readouts = [];

        foreach ($this->reader->grantsOf($skill) as $grant) {
            $readouts[] = new SkillLeverReadout(
                $grant->lever->label(),
                $this->reader->effectOf($grant, $register),
                $this->scale->unitOf($grant->lever, $register),
                $this->requirementOf($grant->condition),
                $this->pactCostOf($grant, $register),
            );
        }

        return $readouts;
    }

    /**
     * Le malus du pacte, dit **avant** d'apprendre (ARC-15, regle 6).
     *
     * *On assume un choix, on ne se fait pas pieger.* Le net, lui, se lit dans
     * l'effet du nœud : le joueur voit ce qu'il gagne et ce qu'il perd sur la
     * meme ligne, ce que le § 8 bis demande.
     */
    private function pactCostOf(LeverGrant $grant, ?CombatRegister $register): ?string
    {
        if ($grant->pact === null) {
            return null;
        }

        $loss = $this->scale->effectOf($grant->pact->lever, $grant->pact->budgetPoints, $register);

        return sprintf(
            '%s −%s %s',
            $grant->pact->lever->label(),
            rtrim(rtrim(number_format($loss, 1, ',', ''), '0'), ','),
            $this->scale->unitOf($grant->pact->lever, $register),
        );
    }

    /**
     * Ce qu'il faut porter, dit en clair.
     *
     * `null` a deux sens, et ils se confondent volontairement a l'ecran : le
     * nœud n'a pas de condition, ou sa condition n'est pas quelque chose qu'on
     * porte. Dans les deux cas il n'y a **rien a aller chercher dans son
     * inventaire**, et c'est la seule question a laquelle cet ecran repond.
     */
    private function requirementOf(?string $condition): ?string
    {
        if ($condition === null) {
            return null;
        }

        $parsed = SkillCondition::parse($condition);

        if ($parsed->subject !== null) {
            $family = $this->portCatalog->families()[$parsed->subject] ?? null;
            if ($family === null) {
                return null;
            }

            return $family['line'] === 'armor'
                ? sprintf('en %s', mb_strtolower($family['label']))
                : sprintf('a la %s', mb_strtolower($family['label']));
        }

        return self::BUILD_LABELS[$parsed->raw] ?? null;
    }
}
