<?php

namespace App\GameEngine\Progression;

use App\Entity\Game\Skill;
use App\Enum\CombatLever;
use App\Enum\CombatRegister;

/**
 * Ce qu'un nœud accorde, lu et verifie (ARC-03).
 *
 * `Skill::levers` est une colonne JSON : elle accepte n'importe quoi. Le lire
 * ici plutot que partout ou l'on en a besoin donne au vocabulaire ferme un
 * **point de passage unique** — la meme discipline que `EmailVerificationGate`
 * pour la verification ou `PurityPricer` pour la bande de purete.
 *
 * Trois refus, et chacun ferme une facon de contourner le budget :
 *
 * - un levier hors de `CombatLever` — le vocabulaire cesserait d'etre ferme ;
 * - un investissement nul ou negatif, ou au-dela du plafond du levier ;
 * - **deux entrees sur le meme levier** — sinon un arbre depasserait un plafond
 *   en le payant deux fois, et le total afficherait deux lignes la ou le canon
 *   en compte une.
 */
class SkillLeverReader
{
    public function __construct(
        private readonly CombatLeverScale $scale,
        private readonly EquipmentPortCatalog $portCatalog,
    ) {
    }

    /**
     * Ce qu'un nœud rapporte **quand sa condition est remplie** (ARC-12b).
     *
     * GAME_ARCHETYPES § 4.3 : *le budget compte l'effet **moyen**, pas l'effet
     * affiche*. Un passif conditionnel accorde donc plus que ce que ses points
     * paient — x1,4 quand la condition se remplit a l'inventaire, x2,0 quand
     * elle peut reellement manquer en combat —, et c'est **la seule chose** qui
     * fasse de l'equipement un build plutot qu'un total : sans elle, porter une
     * dague ou une hache ne change rien a ce qu'un arbre rend.
     *
     * Les plafonds ne bougent pas : ils restent exprimes en points de budget,
     * et c'est l'effet qui varie.
     */
    public function effectOf(LeverGrant $grant, ?CombatRegister $register = null): float
    {
        return $this->averageEffectOf($grant, $register) * SkillCondition::multiplierFor($grant->condition);
    }

    /**
     * Ce que le budget compte : l'effet **moyen**, condition ou pas.
     *
     * C'est la contrepartie de `effectOf()`, et les garder separes est ce qui
     * empeche la confusion que le § 4.3 previent — un arbre qui compterait
     * l'effet affiche dans son budget acheterait sa puissance deux fois.
     */
    public function averageEffectOf(LeverGrant $grant, ?CombatRegister $register = null): float
    {
        return $this->scale->effectOf($grant->lever, $grant->budgetPoints, $register);
    }

    /**
     * La condition designe-t-elle une famille que l'echelle de port connait ?
     *
     * **Le croisement OBJ.** Une condition de build nomme une famille d'arme ou
     * une ligne d'armure ; l'echelle de port (`EquipmentPortCatalog`) est deja
     * la table qui les enumere, et elle sait aussi les **separer** (`line`).
     * Relire celle-la plutot qu'en ecrire une seconde est ce qui garantit
     * qu'une famille renommee ne laisse pas derriere elle un passif mort.
     *
     * Les deux erreurs qu'il attrape sont exactement celles qu'on fait :
     * une famille qui n'existe pas (`weapon:katana`), et une famille qui existe
     * **du mauvais cote** (`weapon:plate`, `armor:sword`).
     */
    private function checkCondition(SkillCondition $condition, string $source): void
    {
        if ($condition->subject === null) {
            return;
        }

        $families = $this->portCatalog->families();
        $family = $families[$condition->subject] ?? null;

        if ($family === null) {
            throw new CombatLeverDefinitionException(sprintf('%s: "%s" names a family the port ladder does not know. Known families: %s.', $source, $condition->raw, implode(', ', array_keys($families))));
        }

        $expected = str_starts_with($condition->raw, SkillCondition::ARMOR_PREFIX . ':') ? 'armor' : 'weapon';
        if ($family['line'] !== $expected) {
            throw new CombatLeverDefinitionException(sprintf('%s: "%s" is a %s family, not a %s one. A condition names the family, and the ladder decides which side it sits on.', $source, $condition->raw, $family['line'], $expected));
        }
    }

    /**
     * @return list<LeverGrant>
     *
     * @throws CombatLeverDefinitionException si la colonne ment sur le vocabulaire ou sur le budget
     */
    public function grantsOf(Skill $skill): array
    {
        return $this->read($skill->getLevers(), sprintf('skill "%s"', $skill->getSlug()));
    }

    /**
     * Ce que le nœud coute au budget de son arbre.
     */
    public function budgetOf(Skill $skill): int
    {
        $total = 0;
        foreach ($this->grantsOf($skill) as $grant) {
            $total += $grant->budgetPoints;
        }

        return $total;
    }

    /**
     * @param array<array-key, mixed> $raw
     *
     * @return list<LeverGrant>
     */
    public function read(array $raw, string $source = '<array>'): array
    {
        $grants = [];
        $seen = [];

        foreach ($raw as $entry) {
            if (!\is_array($entry)) {
                throw new CombatLeverDefinitionException(sprintf('%s: each lever grant must be a mapping.', $source));
            }

            $name = $entry['lever'] ?? null;
            $lever = \is_string($name) ? CombatLever::tryFrom($name) : null;
            if ($lever === null) {
                throw new CombatLeverDefinitionException(sprintf('%s: "%s" is not a combat lever. The vocabulary is closed (GAME_ARCHETYPES §4).', $source, \is_string($name) ? $name : get_debug_type($name)));
            }

            if (isset($seen[$lever->value])) {
                throw new CombatLeverDefinitionException(sprintf('%s: "%s" is granted twice. Two entries on one lever would buy past its cap without ever exceeding it.', $source, $lever->value));
            }
            $seen[$lever->value] = true;

            $points = $entry['points'] ?? null;
            if (!\is_int($points) || $points <= 0) {
                throw new CombatLeverDefinitionException(sprintf('%s: "%s" must be bought with a positive number of budget points.', $source, $lever->value));
            }

            $cap = $this->scale->capOf($lever);
            if ($points > $cap) {
                throw new CombatLeverDefinitionException(sprintf('%s: "%s" is capped at %d budget points per tree, %d granted.', $source, $lever->value, $cap, $points));
            }

            $condition = $entry['condition'] ?? null;
            if ($condition !== null && (!\is_string($condition) || trim($condition) === '')) {
                throw new CombatLeverDefinitionException(sprintf('%s: "%s" declares an empty condition. A node either states its condition or is unconditional.', $source, $lever->value));
            }

            if ($condition !== null) {
                // ARC-12b — la condition passe enfin par sa grammaire. ARC-12a
                // avait pose le vocabulaire ferme en annoncant qu'une condition
                // inconnue serait **refusee a la lecture** ; le lecteur, lui, ne
                // verifiait que « non vide », si bien qu'une chaine mal
                // orthographiee entrait sans bruit et laissait son passif
                // **toujours inactif** — exactement le defaut que la grammaire
                // devait fermer.
                $this->checkCondition(SkillCondition::parse($condition), $source);
            }

            $grants[] = new LeverGrant($lever, $points, $condition);
        }

        return $grants;
    }
}
