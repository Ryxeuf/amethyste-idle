<?php

namespace App\DataFixtures;

use App\Entity\App\PlayerItem;
use App\Entity\App\Slot;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class SlotFixtures extends Fixture implements DependentFixtureInterface
{
    /**
     * Equipment references whose 2+ slots should be linked together.
     */
    private const LINKED_SLOT_GEAR = [
        'player_long_sword_2',
        'player_leather_armor',
        'remy_long_sword',
        'remy_leather_armor',
    ];

    public function load(ObjectManager $manager): void
    {
        $gearReferences = [
            'player_short_sword',
            'player_long_sword_2',
            'player_leather_boots',
            'player_leather_armor',
            'player_leather_hat',
            'player_iron_shield',
            'player_leather_gloves',
            'player_bronze_ring',
            'player_silver_amulet',
            'player_leather_shoulders',
            'player_leather_pants',
            'remy_short_sword',
            'remy_long_sword',
            'remy_leather_boots',
            'remy_leather_armor',
            'remy_leather_hat',
            'remy_iron_shield',
            'remy_leather_gloves',
            'remy_leather_belt',
            'remy_bronze_ring',
            'remy_silver_amulet',
            'remy_leather_shoulders',
            'remy_leather_pants',
        ];

        $slotIndex = 1;
        foreach ($gearReferences as $gearRef) {
            if (!$this->hasReference($gearRef, PlayerItem::class)) {
                continue;
            }

            $playerItem = $this->getReference($gearRef, PlayerItem::class);
            $nbSlots = $playerItem->getGenericItem()->getMateriaSlots();
            $shouldLink = in_array($gearRef, self::LINKED_SLOT_GEAR, true) && $nbSlots >= 2;

            $gearSlots = [];
            for ($i = 0; $i < $nbSlots; ++$i) {
                $slot = new Slot();
                $slot->setItem($playerItem);
                $slot->setCreatedAt(new \DateTime());
                $slot->setUpdatedAt(new \DateTime());

                $manager->persist($slot);
                $this->addReference('slot_' . $slotIndex, $slot);
                $gearSlots[] = $slot;
                ++$slotIndex;
            }

            // Link first two slots together on eligible equipment
            if ($shouldLink && count($gearSlots) >= 2) {
                $gearSlots[0]->setLinkedSlot($gearSlots[1]);
                $gearSlots[1]->setLinkedSlot($gearSlots[0]);
            }
        }

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [
            PlayerItemFixtures::class,
        ];
    }
}
