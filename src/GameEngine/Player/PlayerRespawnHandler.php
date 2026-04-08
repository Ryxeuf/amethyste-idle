<?php

namespace App\GameEngine\Player;

use App\Event\Fight\PlayerDeadEvent;
use App\Event\Map\PlayerRespawnedEvent;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class PlayerRespawnHandler implements EventSubscriberInterface
{
    public function __construct(private readonly EntityManagerInterface $entityManager, private readonly EventDispatcherInterface $eventDispatcher)
    {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            PlayerDeadEvent::NAME => 'respawn',
        ];
    }

    public function respawn(PlayerDeadEvent $event): void
    {
        $player = $event->getPlayer();
        $coordinates = $player->getLastCoordinates();

        $player->setLife((int) round($player->getMaxLife() / 2));
        $player->setDiedAt(null);
        $player->setCoordinates($coordinates);
        $this->entityManager->persist($player);
        $this->entityManager->flush();

        $this->eventDispatcher->dispatch(new PlayerRespawnedEvent($player), PlayerRespawnedEvent::NAME);
    }
}
