<?php

namespace App\GameEngine\Generator;

use App\Entity\App\PlayerItem;
use App\Entity\Game\Item;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityNotFoundException;

class PlayerItemGenerator
{
    /** @var EntityManagerInterface */
    protected $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function generateFromItemId(int $itemId): PlayerItem
    {
        if (null === $item = $this->entityManager->getRepository(Item::class)->find($itemId)) {
            throw new EntityNotFoundException("PlayerItem not generated : Item with #$itemId not found");
        }

        $playerItem = new PlayerItem();
        $playerItem->setGenericItem($item);
        $playerItem->setNbUsages($item->getNbUsages());

        return $playerItem;
    }
}