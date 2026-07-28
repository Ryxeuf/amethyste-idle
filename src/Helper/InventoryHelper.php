<?php

namespace App\Helper;

use App\Entity\App\Inventory;
use App\Entity\App\PlayerItem;
use App\GameEngine\Generator\PlayerItemGenerator;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityNotFoundException;
use Psr\Log\LoggerInterface;

class InventoryHelper
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly PlayerHelper $playerHelper,
        private readonly PlayerItemGenerator $playerItemGenerator,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function addItemId(int $id, bool $flush = true): void
    {
        try {
            $item = $this->playerItemGenerator->generateFromItemId($id);
            $this->addItem($item, $flush);
        } catch (EntityNotFoundException $e) {
            $this->logger->warning('Failed to add item to inventory: item ID {id} not found.', [
                'id' => $id,
                'exception' => $e,
            ]);
        }
    }

    public function addItem(PlayerItem $item, bool $flush = true): void
    {
        $this->playerHelper->getBagInventory()->addItem($item);
        $item->setInventory($this->playerHelper->getBagInventory());

        // Auto-bind soulbound items to the player
        if ($item->getGenericItem()->isBoundOnPickup() && !$item->isBound()) {
            $item->setBoundToPlayerId($this->playerHelper->getPlayer()->getId());
        }

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

    /**
     * Remove up to $quantity items matching the given slug from the bag inventory.
     *
     * @return int The number of items actually removed
     */
    public function removeItemBySlug(string $slug, int $quantity = 1): int
    {
        $bag = $this->playerHelper->getBagInventory();
        $removed = 0;

        foreach ($this->consumptionOrder($bag->getItems()->toArray(), $slug) as $item) {
            if ($removed >= $quantity) {
                break;
            }

            $bag->removeItem($item);
            $this->entityManager->remove($item);
            ++$removed;
        }

        if ($removed > 0) {
            $this->entityManager->flush();
        }

        return $removed;
    }

    /**
     * Lots de ce slug, **du moins pur au plus pur** (ECO-21).
     *
     * C'est la regle de pile de ce jalon, appliquee la ou elle se joue
     * reellement. Les objets ne s'empilent pas en base — chaque lot est une
     * ligne — mais une recette qui demande « 3 minerais de cuivre » les prenait
     * dans l'ordre du sac : un joueur qui gardait un lot **parfait** pour eveiller
     * une materia le voyait fondre dans la premiere epee venue, sans avertissement
     * et sans recours.
     *
     * Le tri est stable pour les lots de meme bande, et les lots sans bande
     * (tout ce qui est hors perimetre) gardent leur ordre d'origine : pour eux,
     * la fonction ne change rien.
     *
     * @param list<PlayerItem> $items
     *
     * @return list<PlayerItem>
     */
    private function consumptionOrder(array $items, string $slug): array
    {
        $matching = array_values(array_filter(
            $items,
            static fn (PlayerItem $item): bool => $item->getGenericItem()->getSlug() === $slug,
        ));

        usort($matching, static fn (PlayerItem $a, PlayerItem $b): int => ($a->getPurity()?->level() ?? -1) <=> ($b->getPurity()?->level() ?? -1));

        return $matching;
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
