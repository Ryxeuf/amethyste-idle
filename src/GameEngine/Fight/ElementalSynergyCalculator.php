<?php

namespace App\GameEngine\Fight;

use App\Entity\Game\Spell;

class ElementalSynergyCalculator
{
    /**
     * Synergy definitions: [element1 => [element2 => synergy data]]
     * Each synergy is bidirectional.
     */
    private const SYNERGIES = [
        Spell::ELEMENT_WATER => [
            Spell::ELEMENT_FIRE => [
                'name' => 'steam',
                'label' => 'Vapeur',
                'effect' => 'debuff_precision',
                'damageMultiplier' => 1.2,
                'statusEffect' => 'steam_blind',
                'description' => 'La combinaison eau + feu crée de la vapeur, réduisant la précision ennemie.',
            ],
        ],
        Spell::ELEMENT_EARTH => [
            Spell::ELEMENT_AIR => [
                'name' => 'sandstorm',
                'label' => 'Tempête de sable',
                'effect' => 'aoe_damage',
                'damageMultiplier' => 1.5,
                'statusEffect' => null,
                'description' => 'La combinaison terre + air crée une tempête de sable infligeant des dégâts de zone.',
            ],
        ],
        Spell::ELEMENT_LIGHT => [
            Spell::ELEMENT_DARK => [
                'name' => 'eclipse',
                'label' => 'Éclipse',
                'effect' => 'massive_damage_self_damage',
                'damageMultiplier' => 2.5,
                'selfDamagePercent' => 10,
                'statusEffect' => null,
                'description' => 'La combinaison lumière + ténèbres crée une éclipse : dégâts massifs mais aussi des dégâts sur le lanceur.',
            ],
        ],
        Spell::ELEMENT_FIRE => [
            Spell::ELEMENT_EARTH => [
                'name' => 'floral_explosion',
                'label' => 'Explosion florale',
                'effect' => 'poison_fire',
                'damageMultiplier' => 1.3,
                'statusEffect' => 'poison',
                'description' => 'La combinaison feu + terre crée une explosion florale : dégâts de feu et empoisonnement.',
            ],
        ],
    ];

    /**
     * Check if using a given element after the last element creates a synergy.
     *
     * @return array|null Synergy data if combo matches, null otherwise
     */
    public function checkSynergy(string $lastElement, string $currentElement): ?array
    {
        if ($lastElement === Spell::ELEMENT_NONE || $currentElement === Spell::ELEMENT_NONE) {
            return null;
        }

        if ($lastElement === $currentElement) {
            return null;
        }

        // Check direct order
        if (isset(self::SYNERGIES[$lastElement][$currentElement])) {
            return self::SYNERGIES[$lastElement][$currentElement];
        }

        // Check reverse order (synergies are bidirectional)
        if (isset(self::SYNERGIES[$currentElement][$lastElement])) {
            return self::SYNERGIES[$currentElement][$lastElement];
        }

        return null;
    }

    /**
     * Calculate synergy damage modifier based on the synergy data.
     */
    public function applySynergyDamage(int $baseDamage, array $synergyData): int
    {
        $multiplier = $synergyData['damageMultiplier'] ?? 1.0;

        return (int) round($baseDamage * $multiplier);
    }

    /**
     * Calculate self-damage from synergy (e.g., eclipse).
     */
    public function getSelfDamage(int $maxLife, array $synergyData): int
    {
        $percent = $synergyData['selfDamagePercent'] ?? 0;
        if ($percent <= 0) {
            return 0;
        }

        return (int) round($maxLife * $percent / 100);
    }

    /**
     * Get all available synergy combinations.
     *
     * @return array<string, array>
     */
    public function getAllSynergies(): array
    {
        $all = [];
        foreach (self::SYNERGIES as $element1 => $combos) {
            foreach ($combos as $element2 => $data) {
                $key = $element1 . '+' . $element2;
                $all[$key] = $data;
            }
        }

        return $all;
    }
}
