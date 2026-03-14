<?php

namespace App\DataFixtures\Game;

use App\Entity\Game\Spell;
use App\Entity\Game\StatusEffect;
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
            $effect->setType($data['type']);
            $effect->setDuration($data['duration']);
            $effect->setChance($data['chance'] ?? 100);
            $effect->setElement($data['element'] ?? Spell::ELEMENT_NONE);
            $effect->setIcon($data['icon'] ?? null);

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
            // Poison : perd X% PV par tour (3 tours)
            'status_poison' => [
                'slug' => 'poison',
                'name' => 'Poison',
                'type' => StatusEffect::TYPE_POISON,
                'duration' => 3,
                'damagePerTurn' => 3,
                'chance' => 100,
                'element' => Spell::ELEMENT_EARTH,
                'icon' => "\u{2620}",
            ],
            'status_poison_strong' => [
                'slug' => 'poison-strong',
                'name' => 'Poison virulent',
                'type' => StatusEffect::TYPE_POISON,
                'duration' => 4,
                'damagePerTurn' => 5,
                'chance' => 80,
                'element' => Spell::ELEMENT_EARTH,
                'icon' => "\u{2620}",
            ],

            // Paralysie : chance de ne pas agir (50%, 2 tours)
            'status_paralysis' => [
                'slug' => 'paralysis',
                'name' => 'Paralysie',
                'type' => StatusEffect::TYPE_PARALYSIS,
                'duration' => 2,
                'chance' => 50,
                'element' => Spell::ELEMENT_LIGHT,
                'icon' => "\u{26A1}",
            ],

            // Brulure : degats reduits de 25% + degats de feu par tour
            'status_burn' => [
                'slug' => 'burn',
                'name' => 'Brulure',
                'type' => StatusEffect::TYPE_BURN,
                'duration' => 3,
                'damagePerTurn' => 2,
                'statModifier' => ['damage' => -0.25],
                'chance' => 100,
                'element' => Spell::ELEMENT_FIRE,
                'icon' => "\u{1F525}",
            ],

            // Gel : vitesse reduite de 50% (2 tours)
            'status_freeze' => [
                'slug' => 'freeze',
                'name' => 'Gel',
                'type' => StatusEffect::TYPE_FREEZE,
                'duration' => 2,
                'statModifier' => ['speed' => -0.50],
                'chance' => 100,
                'element' => Spell::ELEMENT_WATER,
                'icon' => "\u{2744}",
            ],

            // Silence : impossible d'utiliser des sorts (3 tours)
            'status_silence' => [
                'slug' => 'silence',
                'name' => 'Silence',
                'type' => StatusEffect::TYPE_SILENCE,
                'duration' => 3,
                'chance' => 100,
                'element' => Spell::ELEMENT_DARK,
                'icon' => "\u{1F910}",
            ],

            // Regeneration : recupere X PV par tour (3 tours)
            'status_regeneration' => [
                'slug' => 'regeneration',
                'name' => 'Regeneration',
                'type' => StatusEffect::TYPE_REGENERATION,
                'duration' => 3,
                'healPerTurn' => 4,
                'chance' => 100,
                'element' => Spell::ELEMENT_LIGHT,
                'icon' => "\u{1F49A}",
            ],
            'status_regeneration_strong' => [
                'slug' => 'regeneration-strong',
                'name' => 'Regeneration puissante',
                'type' => StatusEffect::TYPE_REGENERATION,
                'duration' => 4,
                'healPerTurn' => 7,
                'chance' => 100,
                'element' => Spell::ELEMENT_LIGHT,
                'icon' => "\u{1F49A}",
            ],

            // Bouclier : absorbe les X prochains points de degats
            'status_shield' => [
                'slug' => 'shield',
                'name' => 'Bouclier',
                'type' => StatusEffect::TYPE_SHIELD,
                'duration' => 3,
                'statModifier' => ['shield_absorb' => 10],
                'chance' => 100,
                'element' => Spell::ELEMENT_EARTH,
                'icon' => "\u{1F6E1}",
            ],
            'status_shield_strong' => [
                'slug' => 'shield-strong',
                'name' => 'Bouclier magique',
                'type' => StatusEffect::TYPE_SHIELD,
                'duration' => 4,
                'statModifier' => ['shield_absorb' => 20],
                'chance' => 100,
                'element' => Spell::ELEMENT_LIGHT,
                'icon' => "\u{1F6E1}",
            ],

            // Berserk : +50% degats, -30% defense, ne peut pas fuir
            'status_berserk' => [
                'slug' => 'berserk',
                'name' => 'Berserk',
                'type' => StatusEffect::TYPE_BERSERK,
                'duration' => 3,
                'statModifier' => ['damage' => 0.50, 'defense' => -0.30],
                'chance' => 100,
                'element' => Spell::ELEMENT_FIRE,
                'icon' => "\u{1F4A2}",
            ],
        ];
    }
}
