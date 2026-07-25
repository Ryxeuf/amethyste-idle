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
        $this->bindOnEquip($gear);

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

    /**
     * Materialise la liaison a l'equipement (ECO-01).
     *
     * Un objet `bind_on_equip` circule librement tant qu'il n'a pas ete porte ;
     * il s'immobilise sur le porteur au premier equipement. La liaison est
     * definitive : `unsetGear` ne la leve pas.
     */
    private function bindOnEquip(PlayerItem $gear): void
    {
        if ($gear->isBound() || !$gear->getGenericItem()->isBoundOnEquip()) {
            return;
        }

        $owner = $gear->getInventory()?->getPlayer();
        if ($owner instanceof Player) {
            $gear->setBoundToPlayerId($owner->getId());
        }
    }
}
