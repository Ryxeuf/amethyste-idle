<?php

namespace App\GameEngine\Fight\Handler;

use App\ApiResource\FightResource;
use App\Entity\App\Player;
use App\Entity\App\PlayerItem;
use App\Entity\CharacterInterface;
use App\Event\Fight\PlayerSpellHitEvent;
use App\Event\Fight\PlayerSpellMissEvent;
use App\GameEngine\Item\ItemUsageResolver;
use App\Helper\FightHelper;
use App\Helper\GearHelper;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class PlayerSpellHandler extends AbstractPayerItemHandler
{
    public function __construct(GearHelper $gearHelper, EntityManagerInterface $entityManager, FightHelper $fightHelper, private readonly EventDispatcherInterface $eventDispatcher, ItemUsageResolver $itemUsageResolver)
    {
        parent::__construct($entityManager, $fightHelper, $itemUsageResolver);
    }

    public function supports(FightResource $fight, string $context)
    {
        return FightResource::ACTION_SPELL === $context;
    }

    public function resolveItem(PlayerItem $item, Player $player, CharacterInterface $target): bool
    {
        if ($hit = parent::resolveItem($item, $player, $target)) {
            $this->eventDispatcher->dispatch(new PlayerSpellHitEvent($item->getGenericItem()->getName()), PlayerSpellHitEvent::NAME);
        } else {
            $this->eventDispatcher->dispatch(new PlayerSpellMissEvent($item->getGenericItem()->getName()), PlayerSpellMissEvent::NAME);
        }

        return $hit;
    }
}
