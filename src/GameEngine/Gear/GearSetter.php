<?php

namespace App\GameEngine\Gear;

use App\Entity\App\Player;
use App\Entity\App\PlayerItem;
use App\Exception\ItemNotEquippedException;
use App\Exception\ItemNotGearException;
use App\Helper\GearHelper;
use App\Service\Avatar\AvatarHashRecalculator;
use Doctrine\ORM\EntityManagerInterface;

class GearSetter
{
    public function __construct(
        private readonly GearHelper $gearHelper,
        private readonly EntityManagerInterface $entityManager,
        private readonly AvatarHashRecalculator $avatarHashRecalculator,
    ) {
    }

    /**
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

        $this->recalculateAvatarHashFor($gear);
    }

    /**
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
            $this->recalculateAvatarHashFor($gear);
        }
    }

    private function recalculateAvatarHashFor(PlayerItem $gear): void
    {
        $inventory = $gear->getInventory();
        $player = $inventory?->getPlayer();

        if ($player instanceof Player) {
            $this->avatarHashRecalculator->recalculate($player);
        }
    }
}
