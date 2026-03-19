<?php

namespace App\DataFixtures;

use App\Entity\App\Slot;
use App\Enum\Element;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class SlotFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        // Création du slot de test
        $slot = new Slot();
        $slot->setElement(Element::Fire);
        $slot->setCreatedAt(new \DateTime());
        $slot->setUpdatedAt(new \DateTime());

        $manager->persist($slot);
        $this->addReference('slot_1', $slot);

        $manager->flush();
    }
}
