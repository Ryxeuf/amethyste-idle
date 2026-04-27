<?php

namespace App\DataFixtures\Game;

use App\Entity\Game\EnchantmentDefinition;
use App\Enum\Element;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class EnchantmentDefinitionFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $definitions = $this->getDefinitionsData();

        foreach ($definitions as $reference => $data) {
            $definition = new EnchantmentDefinition();
            $definition->setSlug($data['slug']);
            $definition->setName($data['name']);
            $definition->setNameTranslations($data['name_translations'] ?? null);
            $definition->setDescription($data['description'] ?? null);
            $definition->setDescriptionTranslations($data['description_translations'] ?? null);
            $definition->setElement($data['element'] ?? Element::None);
            $definition->setStatBonuses($data['statBonuses']);
            $definition->setDuration($data['duration']);
            $definition->setIngredients($data['ingredients']);
            $definition->setRequiredLevel($data['requiredLevel'] ?? 1);
            $definition->setCost($data['cost'] ?? 0);
            $definition->setIcon($data['icon'] ?? null);
            $definition->setCreatedAt(new \DateTime());
            $definition->setUpdatedAt(new \DateTime());

            $manager->persist($definition);
            $this->addReference($reference, $definition);
        }

        $manager->flush();
    }

    private function getDefinitionsData(): array
    {
        return [
            'enchant_fire_blade' => [
                'slug' => 'fire-blade',
                'name' => 'Tranchant de feu',
                'name_translations' => ['en' => 'Flame Edge'],
                'description' => 'Imprègne l\'arme d\'une flamme ardente, augmentant les dégâts infligés.',
                'description_translations' => ['en' => 'Imbues the weapon with searing flame, increasing damage dealt.'],
                'element' => Element::Fire,
                'statBonuses' => ['damage' => 5],
                'duration' => 3600, // 1h
                'ingredients' => [
                    ['slug' => 'plant-sage', 'quantity' => 2],
                    ['slug' => 'magic-crystal', 'quantity' => 1],
                ],
                'requiredLevel' => 1,
                'cost' => 50,
                'icon' => "\u{1F525}",
            ],
            'enchant_ice_protection' => [
                'slug' => 'ice-protection',
                'name' => 'Protection de glace',
                'name_translations' => ['en' => 'Ice Ward'],
                'description' => 'Enveloppe l\'armure d\'un voile de givre protecteur.',
                'description_translations' => ['en' => 'Wraps the armor in a protective veil of frost.'],
                'element' => Element::Water,
                'statBonuses' => ['defense' => 3],
                'duration' => 1800, // 30min
                'ingredients' => [
                    ['slug' => 'plant-mint', 'quantity' => 2],
                    ['slug' => 'magic-crystal', 'quantity' => 1],
                ],
                'requiredLevel' => 1,
                'cost' => 40,
                'icon' => "\u{2744}",
            ],
            'enchant_earth_fortitude' => [
                'slug' => 'earth-fortitude',
                'name' => 'Robustesse tellurique',
                'name_translations' => ['en' => 'Telluric Fortitude'],
                'description' => 'Canalise la force de la terre pour augmenter les points de vie.',
                'description_translations' => ['en' => 'Channels the strength of the earth to increase maximum life.'],
                'element' => Element::Earth,
                'statBonuses' => ['max_life' => 10],
                'duration' => 3600, // 1h
                'ingredients' => [
                    ['slug' => 'plant-nettle', 'quantity' => 2],
                    ['slug' => 'plant-valerian', 'quantity' => 1],
                ],
                'requiredLevel' => 2,
                'cost' => 60,
                'icon' => "\u{1F30D}",
            ],
            'enchant_light_precision' => [
                'slug' => 'light-precision',
                'name' => 'Precision lumineuse',
                'name_translations' => ['en' => 'Radiant Precision'],
                'description' => 'Guide les coups avec la lumière, augmentant la précision.',
                'description_translations' => ['en' => 'Guides each strike with light, increasing accuracy.'],
                'element' => Element::Light,
                'statBonuses' => ['hit' => 8],
                'duration' => 2700, // 45min
                'ingredients' => [
                    ['slug' => 'plant-chamomile', 'quantity' => 2],
                    ['slug' => 'plant-rosemary', 'quantity' => 1],
                ],
                'requiredLevel' => 2,
                'cost' => 55,
                'icon' => "\u{2728}",
            ],
        ];
    }
}
