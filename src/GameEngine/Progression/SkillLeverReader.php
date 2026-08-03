<?php

namespace App\GameEngine\Progression;

use App\Entity\Game\Skill;
use App\Enum\CombatLever;

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
    ) {
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

            $grants[] = new LeverGrant($lever, $points, $condition);
        }

        return $grants;
    }
}
