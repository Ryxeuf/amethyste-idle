<?php

namespace App\DataFixtures\Game;

use App\Entity\Game\EquipmentSet;
use App\Entity\Game\EquipmentSetBonus;
use App\Entity\Game\Item;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\Yaml\Yaml;

class EquipmentSetFixtures extends Fixture implements DependentFixtureInterface
{
    private const FIXTURES_FILE = __DIR__ . '/../../../fixtures/game/equipment_set.yaml';

    public function load(ObjectManager $manager): void
    {
        $content = Yaml::parseFile(self::FIXTURES_FILE);

        if (!isset($content['App\Entity\Game\EquipmentSet'])) {
            return;
        }

        $sets = $content['App\Entity\Game\EquipmentSet'];

        foreach ($sets as $reference => $data) {
            $set = new EquipmentSet();
            $set->setSlug((string) $data['slug']);
            $set->setName((string) $data['name']);
            $set->setDescription((string) $data['description']);

            // Create bonuses
            if (isset($data['bonuses']) && \is_array($data['bonuses'])) {
                foreach ($data['bonuses'] as $bonusData) {
                    $bonus = new EquipmentSetBonus();
                    $bonus->setRequiredPieces((int) $bonusData['required_pieces']);
                    $bonus->setBonusType((string) $bonusData['bonus_type']);
                    $bonus->setBonusValue((int) $bonusData['bonus_value']);
                    $set->addBonus($bonus);
                }
            }

            $manager->persist($set);
            $this->addReference($reference, $set);

            // Link items to the set
            if (isset($data['items']) && \is_array($data['items'])) {
                foreach ($data['items'] as $itemRef) {
                    $itemReference = substr($itemRef, 1); // Remove the @ symbol
                    $item = $this->getReference($itemReference, Item::class);
                    $item->setEquipmentSet($set);
                }
            }
        }

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [
            ItemFixtures::class,
        ];
    }
}
