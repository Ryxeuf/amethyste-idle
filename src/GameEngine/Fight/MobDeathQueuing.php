<?php

namespace App\GameEngine\Fight;

use App\Entity\App\QueueRespawnMob;
use App\Event\Fight\MobDeadEvent;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class MobDeathQueuing implements EventSubscriberInterface
{
    public function __construct(private readonly EntityManagerInterface $entityManager)
    {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            MobDeadEvent::NAME => 'mobDied',
        ];
    }

    public function mobDied(MobDeadEvent $event): void
    {
        $mob = $event->getMob();
        $monster = $mob->getMonster();

        // Boss mobs have a longer respawn delay (1 hour)
        $delay = $monster->isBoss() ? 3600 : 10;

        $respawn = new QueueRespawnMob();
        $respawn->setDelay($delay);
        $respawn->setCoordinates($mob->getCoordinates());
        $respawn->setMap($mob->getMap());
        $respawn->setMonster($monster);
        $respawn->setNocturnal($mob->isNocturnal());
        $respawn->setSpawnWeather($mob->getSpawnWeather());

        $this->entityManager->persist($respawn);
        $this->entityManager->flush();
    }
}
