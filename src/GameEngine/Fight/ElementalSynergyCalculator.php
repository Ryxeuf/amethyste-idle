<?php

namespace App\GameEngine\Fight;

use App\Enum\Element;

class ElementalSynergyCalculator
{
    /**
     * Synergy definitions: [element1 => [element2 => synergy data]]
     * Each synergy is bidirectional.
     */
    private const SYNERGIES = [
        'water' => [
            'fire' => [
                'name' => 'steam',
                'label' => 'Vapeur',
                'effect' => 'debuff_precision',
                'damageMultiplier' => 1.2,
                'statusEffect' => 'steam_blind',
                'description' => 'La combinaison eau + feu crée de la vapeur, réduisant la précision ennemie.',
            ],
        ],
        'earth' => [
            'air' => [
                'name' => 'sandstorm',
                'label' => 'Tempête de sable',
                'effect' => 'aoe_damage',
                'damageMultiplier' => 1.5,
                'statusEffect' => null,
                'description' => 'La combinaison terre + air crée une tempête de sable infligeant des dégâts de zone.',
            ],
        ],
        'light' => [
            'dark' => [
                'name' => 'eclipse',
                'label' => 'Éclipse',
                'effect' => 'massive_damage_self_damage',
                'damageMultiplier' => 2.5,
                'selfDamagePercent' => 10,
                'statusEffect' => null,
                'description' => 'La combinaison lumière + ténèbres crée une éclipse : dégâts massifs mais aussi des dégâts sur le lanceur.',
            ],
        ],
        'fire' => [
            'earth' => [
                'name' => 'floral_explosion',
                'label' => 'Explosion florale',
                'effect' => 'poison_fire',
                'damageMultiplier' => 1.3,
                'statusEffect' => 'poison',
                'description' => 'La combinaison feu + terre crée une explosion florale : dégâts de feu et empoisonnement.',
            ],
        ],
        'metal' => [
            'fire' => [
                'name' => 'forge',
                'label' => 'Forge ardente',
                'effect' => 'buff_damage',
                'damageMultiplier' => 1.4,
                'statusEffect' => 'burn',
                'description' => 'La combinaison métal + feu crée une forge ardente : métal chauffé à blanc, dégâts amplifiés et brûlure.',
            ],
            'light' => [
                'name' => 'holy_blade',
                'label' => 'Lame sacrée',
                'effect' => 'burst_damage',
                'damageMultiplier' => 1.6,
                'statusEffect' => null,
                'description' => 'La combinaison métal + lumière forge une lame sacrée : dégâts purs amplifiés.',
            ],
        ],
        'beast' => [
            'earth' => [
                'name' => 'primal_fury',
                'label' => 'Furie primale',
                'effect' => 'buff_berserk',
                'damageMultiplier' => 1.4,
                'statusEffect' => 'berserk',
                'description' => 'La combinaison bête + terre déchaîne une furie primale : rage sauvage et puissance brute.',
            ],
            'dark' => [
                'name' => 'venomous_shadow',
                'label' => 'Ombre venimeuse',
                'effect' => 'poison_dark',
                'damageMultiplier' => 1.5,
                'statusEffect' => 'poison',
                'description' => 'La combinaison bête + ténèbres crée une ombre venimeuse : poison virulent amplifié par les ténèbres.',
            ],
        ],
    ];

    /**
     * Check if using a given element after the last element creates a synergy.
     *
     * @return array|null Synergy data if combo matches, null otherwise
     */
    public function checkSynergy(Element $lastElement, Element $currentElement): ?array
    {
        if ($lastElement === Element::None || $currentElement === Element::None) {
            return null;
        }

        if ($lastElement === $currentElement) {
            return null;
        }

        $last = $lastElement->value;
        $current = $currentElement->value;

        // Check direct order
        if (isset(self::SYNERGIES[$last][$current])) {
            return self::SYNERGIES[$last][$current];
        }

        // Check reverse order (synergies are bidirectional)
        if (isset(self::SYNERGIES[$current][$last])) {
            return self::SYNERGIES[$current][$last];
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
