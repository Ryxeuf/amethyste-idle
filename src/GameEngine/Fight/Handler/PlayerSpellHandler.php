<?php

namespace App\GameEngine\Fight\Handler;

use App\Entity\App\Fight;
use App\Entity\App\Player;
use App\Entity\App\PlayerItem;
use App\Entity\CharacterInterface;
use App\Enum\QuestGesture;
use App\Event\Fight\PlayerSpellHitEvent;
use App\Event\Fight\PlayerSpellMissEvent;
use App\Event\Game\PlayerGestureEvent;
use App\GameEngine\Item\ItemUsageResolver;
use App\Helper\FightHelper;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class PlayerSpellHandler extends AbstractPayerItemHandler
{
    public function __construct(EntityManagerInterface $entityManager, FightHelper $fightHelper, private readonly EventDispatcherInterface $eventDispatcher, ItemUsageResolver $itemUsageResolver)
    {
        parent::__construct($entityManager, $fightHelper, $itemUsageResolver);
    }

    public function supports(Fight $fight, string $context): bool
    {
        return PlayerActionHandlerInterface::ACTION_SPELL === $context;
    }

    public function resolveItem(PlayerItem $item, Player $player, CharacterInterface $target): bool
    {
        // ONB-12a : le geste est le **lancer**, annonce avant de savoir s'il
        // touche. Rater n'est pas ne pas avoir appris, et une chaine
        // d'introduction qui bloque sur un jet de des enseigne le contraire de
        // ce qu'elle veut montrer.
        $this->eventDispatcher->dispatch(
            new PlayerGestureEvent(QuestGesture::CastSpell, [$item->getGenericItem()->getSlug()]),
            PlayerGestureEvent::NAME,
        );

        if ($hit = parent::resolveItem($item, $player, $target)) {
            $this->eventDispatcher->dispatch(new PlayerSpellHitEvent($item->getGenericItem()->getName()), PlayerSpellHitEvent::NAME);
        } else {
            $this->eventDispatcher->dispatch(new PlayerSpellMissEvent($item->getGenericItem()->getName()), PlayerSpellMissEvent::NAME);
        }

        return $hit;
    }
}
