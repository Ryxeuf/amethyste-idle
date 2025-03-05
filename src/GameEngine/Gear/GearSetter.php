<?php

namespace App\GameEngine\Gear;

use App\Entity\App\PlayerItem;
use App\Exception\GearLocationException;
use App\Exception\ItemNotEquippedException;
use App\Exception\ItemNotGearException;
use App\Helper\GearHelper;
use Doctrine\ORM\EntityManagerInterface;

class GearSetter
{
    public function __construct(private readonly GearHelper $gearHelper, private readonly EntityManagerInterface $entityManager)
    {
    }

    /**
     *
     * @throws ItemNotGearException
     * @throws ItemNotEquippedException
     */
    public function setGear(PlayerItem $gear): void
    {
        if (!$gear->isGear()) {
            throw new ItemNotGearException();
        }
        $location = $gear->getGenericItem()->getGearLocation();
        if ($equipped = $this->gearHelper->getEquippedGearByLocation($location)) {
            if ($equipped->getId() === $gear->getId()) {
                return;
            }
            $this->unsetGear($equipped, false);
        }
        $gear->setGear($this->gearHelper->getPlayerItemGearByLocation($location));

        $this->entityManager->flush();
    }

    /**
     *
     * @throws ItemNotEquippedException
     */
    public function unsetGear(PlayerItem $gear, bool $flush = true): void
    {
        if (!$this->gearHelper->isEquipped($gear)) {
            throw new ItemNotEquippedException();
        }

        $gear->removeGear();
        $this->entityManager->persist($gear);

        if ($flush) {
            $this->entityManager->flush();
        }
    }
}
