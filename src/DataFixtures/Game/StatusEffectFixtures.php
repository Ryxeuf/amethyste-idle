<?php

namespace App\DataFixtures\Game;

use App\Entity\Game\StatusEffect;
use App\Enum\Element;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class StatusEffectFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $effects = $this->getStatusEffectsData();

        foreach ($effects as $reference => $data) {
            $effect = new StatusEffect();
            $effect->setSlug($data['slug']);
            $effect->setName($data['name']);
            if (isset($data['name_translations']) && \is_array($data['name_translations'])) {
                $effect->setNameTranslations($data['name_translations']);
            }
            $effect->setType($data['type']);
            $effect->setDuration($data['duration']);
            $effect->setChance($data['chance'] ?? 100);
            $effect->setElement($data['element'] ?? Element::None);
            $effect->setIcon($data['icon'] ?? null);
            $effect->setCategory($data['category'] ?? null);
            $effect->setFrequency($data['frequency'] ?? null);
            $effect->setRealTimeDuration($data['realTimeDuration'] ?? null);

            if (isset($data['damagePerTurn'])) {
                $effect->setDamagePerTurn($data['damagePerTurn']);
            }
            if (isset($data['healPerTurn'])) {
                $effect->setHealPerTurn($data['healPerTurn']);
            }
            if (isset($data['statModifier'])) {
                $effect->setStatModifier($data['statModifier']);
            }

            $effect->setCreatedAt(new \DateTime());
            $effect->setUpdatedAt(new \DateTime());

            $manager->persist($effect);
            $this->addReference($reference, $effect);
        }

        $manager->flush();
    }

    private function getStatusEffectsData(): array
    {
        return [
            // === DoT ===

            // Poison : perd X PV par tour (3 tours)
            'status_poison' => [
                'slug' => 'poison',
                'name' => 'Poison',
                'name_translations' => ['en' => 'Poison'],
                'type' => StatusEffect::TYPE_POISON,
                'category' => StatusEffect::CATEGORY_DOT,
                'duration' => 3,
                'damagePerTurn' => 3,
                'chance' => 100,
                'element' => Element::Earth,
                'icon' => "\u{2620}",
            ],
            'status_poison_strong' => [
                'slug' => 'poison-strong',
                'name' => 'Poison virulent',
                'name_translations' => ['en' => 'Virulent Poison'],
                'type' => StatusEffect::TYPE_POISON,
                'category' => StatusEffect::CATEGORY_DOT,
                'duration' => 4,
                'damagePerTurn' => 5,
                'chance' => 80,
                'element' => Element::Earth,
                'icon' => "\u{2620}",
            ],

            // Brulure : degats reduits de 25% + degats de feu par tour
            'status_burn' => [
                'slug' => 'burn',
                'name' => 'Brulure',
                'name_translations' => ['en' => 'Burn'],
                'type' => StatusEffect::TYPE_BURN,
                'category' => StatusEffect::CATEGORY_DOT,
                'duration' => 3,
                'damagePerTurn' => 2,
                'statModifier' => ['damage' => -0.25],
                'chance' => 100,
                'element' => Element::Fire,
                'icon' => "\u{1F525}",
            ],

            // Poison lent : tick tous les 2 tours (frequency = 2)
            'status_poison_slow' => [
                'slug' => 'poison-slow',
                'name' => 'Poison insidieux',
                'name_translations' => ['en' => 'Insidious Poison'],
                'type' => StatusEffect::TYPE_POISON,
                'category' => StatusEffect::CATEGORY_DOT,
                'duration' => 6,
                'frequency' => 2,
                'damagePerTurn' => 8,
                'chance' => 100,
                'element' => Element::Earth,
                'icon' => "\u{2620}",
            ],

            // === Debuff ===

            // Paralysie : chance de ne pas agir (50%, 2 tours)
            'status_paralysis' => [
                'slug' => 'paralysis',
                'name' => 'Paralysie',
                'name_translations' => ['en' => 'Paralysis'],
                'type' => StatusEffect::TYPE_PARALYSIS,
                'category' => StatusEffect::CATEGORY_DEBUFF,
                'duration' => 2,
                'chance' => 50,
                'element' => Element::Light,
                'icon' => "\u{26A1}",
            ],

            // Gel : vitesse reduite de 50% (2 tours)
            'status_freeze' => [
                'slug' => 'freeze',
                'name' => 'Gel',
                'name_translations' => ['en' => 'Freeze'],
                'type' => StatusEffect::TYPE_FREEZE,
                'category' => StatusEffect::CATEGORY_DEBUFF,
                'duration' => 2,
                'statModifier' => ['speed' => -0.50],
                'chance' => 100,
                'element' => Element::Water,
                'icon' => "\u{2744}",
            ],

            // Silence : impossible d'utiliser des sorts (3 tours)
            'status_silence' => [
                'slug' => 'silence',
                'name' => 'Silence',
                'name_translations' => ['en' => 'Silence'],
                'type' => StatusEffect::TYPE_SILENCE,
                'category' => StatusEffect::CATEGORY_DEBUFF,
                'duration' => 3,
                'chance' => 100,
                'element' => Element::Dark,
                'icon' => "\u{1F910}",
            ],

            // === HoT ===

            // Regeneration : recupere X PV par tour (3 tours)
            'status_regeneration' => [
                'slug' => 'regeneration',
                'name' => 'Regeneration',
                'name_translations' => ['en' => 'Regeneration'],
                'type' => StatusEffect::TYPE_REGENERATION,
                'category' => StatusEffect::CATEGORY_HOT,
                'duration' => 3,
                'healPerTurn' => 4,
                'chance' => 100,
                'element' => Element::Light,
                'icon' => "\u{1F49A}",
            ],
            'status_regeneration_strong' => [
                'slug' => 'regeneration-strong',
                'name' => 'Regeneration puissante',
                'name_translations' => ['en' => 'Greater Regeneration'],
                'type' => StatusEffect::TYPE_REGENERATION,
                'category' => StatusEffect::CATEGORY_HOT,
                'duration' => 4,
                'healPerTurn' => 7,
                'chance' => 100,
                'element' => Element::Light,
                'icon' => "\u{1F49A}",
            ],

            // === Buff ===

            // Bouclier : absorbe les X prochains points de degats
            'status_shield' => [
                'slug' => 'shield',
                'name' => 'Bouclier',
                'name_translations' => ['en' => 'Shield'],
                'type' => StatusEffect::TYPE_SHIELD,
                'category' => StatusEffect::CATEGORY_BUFF,
                'duration' => 3,
                'statModifier' => ['shield_absorb' => 10],
                'chance' => 100,
                'element' => Element::Earth,
                'icon' => "\u{1F6E1}",
            ],
            'status_shield_strong' => [
                'slug' => 'shield-strong',
                'name' => 'Bouclier magique',
                'name_translations' => ['en' => 'Arcane Shield'],
                'type' => StatusEffect::TYPE_SHIELD,
                'category' => StatusEffect::CATEGORY_BUFF,
                'duration' => 4,
                'statModifier' => ['shield_absorb' => 20],
                'chance' => 100,
                'element' => Element::Light,
                'icon' => "\u{1F6E1}",
            ],

            // Berserk : +50% degats, -30% defense, ne peut pas fuir
            'status_berserk' => [
                'slug' => 'berserk',
                'name' => 'Berserk',
                'name_translations' => ['en' => 'Berserk'],
                'type' => StatusEffect::TYPE_BERSERK,
                'category' => StatusEffect::CATEGORY_BUFF,
                'duration' => 3,
                'statModifier' => ['damage' => 0.50, 'defense' => -0.30],
                'chance' => 100,
                'element' => Element::Fire,
                'icon' => "\u{1F4A2}",
            ],

            // === Effets persistants hors combat ===

            // Repas nourrissant : buff hors combat, +10% PV max pendant 5 min
            'status_food_buff' => [
                'slug' => 'food-buff',
                'name' => 'Repas nourrissant',
                'name_translations' => ['en' => 'Hearty Meal'],
                'type' => StatusEffect::TYPE_REGENERATION,
                'category' => StatusEffect::CATEGORY_BUFF,
                'duration' => 3,
                'realTimeDuration' => 300,
                'statModifier' => ['max_life' => 0.10],
                'chance' => 100,
                'element' => Element::None,
                'icon' => "\u{1F356}",
            ],

            // Elixir de force : buff hors combat, +15% attaque pendant 10 min
            'status_elixir_strength' => [
                'slug' => 'elixir-strength',
                'name' => 'Elixir de force',
                'name_translations' => ['en' => 'Strength Elixir'],
                'type' => StatusEffect::TYPE_BERSERK,
                'category' => StatusEffect::CATEGORY_BUFF,
                'duration' => 5,
                'realTimeDuration' => 600,
                'statModifier' => ['damage' => 0.15],
                'chance' => 100,
                'element' => Element::Fire,
                'icon' => "\u{1F4AA}",
            ],

            // Regeneration naturelle : HoT persistant, soin toutes les 2 tours en combat
            'status_natural_regen' => [
                'slug' => 'natural-regen',
                'name' => 'Regeneration naturelle',
                'name_translations' => ['en' => 'Natural Regeneration'],
                'type' => StatusEffect::TYPE_REGENERATION,
                'category' => StatusEffect::CATEGORY_HOT,
                'duration' => 6,
                'frequency' => 2,
                'realTimeDuration' => 180,
                'healPerTurn' => 5,
                'chance' => 100,
                'element' => Element::Light,
                'icon' => "\u{1F33F}",
            ],
        ];
    }
}
