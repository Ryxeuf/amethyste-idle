<?php

namespace App\GameEngine\Fight\Handler;

use App\Entity\App\Fight;
use App\Entity\App\Player;
use App\Entity\App\PlayerItem;
use App\Entity\CharacterInterface;
use App\Event\Fight\PlayerAttackHitEvent;
use App\Event\Fight\PlayerAttackMissEvent;
use App\GameEngine\Item\ItemUsageResolver;
use App\Helper\FightHelper;
use App\Helper\GearHelper;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityNotFoundException;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class PlayerAttackHandler extends AbstractPayerItemHandler
{
    public function __construct(private readonly GearHelper $gearHelper, EntityManagerInterface $entityManager, FightHelper $fightHelper, private readonly EventDispatcherInterface $eventDispatcher, ItemUsageResolver $itemUsageResolver)
    {
        parent::__construct($entityManager, $fightHelper, $itemUsageResolver);
    }

    public function supports(Fight $fight, string $context): bool
    {
        return PlayerActionHandlerInterface::ACTION_ATTACK === $context;
    }

    protected function getItem(Fight $fight): PlayerItem
    {
        $weapon = $this->gearHelper->getWeaponGear();
        if (!$weapon || !$weapon->getGenericItem()->getSpell()) {
            throw new EntityNotFoundException('Player attack impossible');
        }

        return $weapon;
    }

    public function resolveItem(PlayerItem $item, Player $player, CharacterInterface $target): bool
    {
        if ($hit = parent::resolveItem($item, $player, $target)) {
            $this->eventDispatcher->dispatch(new PlayerAttackHitEvent($item->getGenericItem()->getName()), PlayerAttackHitEvent::NAME);
        } else {
            $this->eventDispatcher->dispatch(new PlayerAttackMissEvent($item->getGenericItem()->getName()), PlayerAttackMissEvent::NAME);
        }

        return $hit;
    }
}
