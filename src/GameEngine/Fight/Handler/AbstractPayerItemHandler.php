<?php

namespace App\GameEngine\Fight\Handler;

use App\ApiResource\FightResource;
use App\Entity\App\Player;
use App\Entity\App\PlayerItem;
use App\Entity\CharacterInterface;
use App\GameEngine\Item\ItemUsageResolver;
use App\Helper\FightHelper;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityNotFoundException;

abstract class AbstractPayerItemHandler implements PlayerActionHandlerInterface
{
    /**
     * @var EntityManagerInterface
     */
    protected $entityManager;
    /**
     * @var FightHelper
     */
    protected $fightHelper;

    /**
     * AbstractPayerItemHandler constructor.
     */
    public function __construct(EntityManagerInterface $entityManager, FightHelper $fightHelper, private readonly ItemUsageResolver $itemUsageResolver)
    {
        $this->entityManager = $entityManager;
        $this->fightHelper = $fightHelper;
    }

    public function applyAction(FightResource $fight, Player $player): bool
    {
        $item = $this->getItem($fight);

        // Récupère la cible de l'action
        if(!$target = $this->fightHelper->getTarget($fight)) {
            return false;
        }
        $this->resolveItem($item, $player, $target);

        return true;
    }

    protected function getItem(FightResource $fight): PlayerItem
    {
        /** @var PlayerItem $item */
        if (!$item = $this->entityManager->getRepository(PlayerItem::class)->find($fight->item)) {
            throw new EntityNotFoundException("Objet inconnu");
        }

        return $item;
    }

    public function resolveItem(PlayerItem $item, Player $player, CharacterInterface $target): bool
    {
        return $this->itemUsageResolver->resolve($item, $player, $target);
    }
}