<?php

namespace App\GameEngine\Player;

use App\Event\Fight\PlayerDeadEvent;
use App\Event\Map\PlayerRespawnedEvent;
use App\Helper\MapHelper;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class PlayerRespawnHandler implements EventSubscriberInterface
{
    public function __construct(private readonly MapHelper $mapHelper, private readonly EntityManagerInterface $entityManager, private readonly EventDispatcherInterface $eventDispatcher)
    {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            PlayerDeadEvent::NAME => 'respawn'
        ];
    }

    public function respawn(PlayerDeadEvent $event)
    {
        $player = $event->getPlayer();
        $cell = $player->getLastCell();
        if ($respawnCell = $this->mapHelper->getRespawnCell($player->getCell()->getMap())) {
            $cell = $respawnCell;
        }

        $player->setLife(round($player->getMaxLife()/2));
        $player->setCell($cell);
        $this->entityManager->persist($player);
        $this->entityManager->flush();

        $this->eventDispatcher->dispatch(new PlayerRespawnedEvent($player), PlayerRespawnedEvent::NAME);
    }
}