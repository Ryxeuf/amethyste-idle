<?php

namespace App\Helper;

use App\Entity\App\Inventory;
use App\Entity\App\PlayerItem;
use App\GameEngine\Generator\PlayerItemGenerator;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityNotFoundException;

class InventoryHelper
{
    public function __construct(private readonly EntityManagerInterface $entityManager, private readonly PlayerHelper $playerHelper, private readonly PlayerItemGenerator $playerItemGenerator)
    {
    }

    public function addItemId(int $id, bool $flush = true): void
    {
        try {
            $item = $this->playerItemGenerator->generateFromItemId($id);
            $this->addItem($item, $flush);
        } catch (EntityNotFoundException) {
        }
    }

    public function addItem(PlayerItem $item, bool $flush = true): void
    {
        $this->playerHelper->getBagInventory()->addItem($item);
        $item->setInventory($this->playerHelper->getBagInventory());
        $this->entityManager->persist($item);

        if ($flush) {
            $this->entityManager->flush();
        }
    }

    public function addGold(int $gold): void
    {
        $this->playerHelper->getBagInventory()->addGold($gold);
    }

    public function hasItem(PlayerItem $item): bool
    {
        if ($this->hasItemInBagInventory($item)) {
            return true;
        }
        if ($this->hasItemInBankInventory($item)) {
            return true;
        }
        if ($this->hasItemInMateriaInventory($item)) {
            return true;
        }

        return false;
    }

    public function hasItemInBagInventory(PlayerItem $item): bool
    {
        if ($this->hasItemInInventory($this->playerHelper->getBagInventory(), $item)) {
            return true;
        }

        return false;
    }

    public function hasItemInBankInventory(PlayerItem $item): bool
    {
        if ($this->hasItemInInventory($this->playerHelper->getBankInventory(), $item)) {
            return true;
        }

        return false;
    }

    public function hasItemInMateriaInventory(PlayerItem $item): bool
    {
        if ($this->hasItemInInventory($this->playerHelper->getMateriaInventory(), $item)) {
            return true;
        }

        return false;
    }

    private function hasItemInInventory(Inventory $inventory, PlayerItem $playerItem): bool
    {
        foreach ($inventory->getItems() as $inventoryItem) {
            if ($playerItem === $inventoryItem) {
                return true;
            }
        }

        return false;
    }
}