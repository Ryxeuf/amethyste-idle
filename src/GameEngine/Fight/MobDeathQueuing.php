<?php

namespace App\GameEngine\Fight;

use App\Entity\App\Mob;
use App\Entity\App\PlayerItem;
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
            MobDeadEvent::NAME => "mobDied"
        ];
    }

    public function mobDied(MobDeadEvent $event)
    {
        $respawn = new QueueRespawnMob();
        $respawn->setDelay(10);
        $respawn->setLastCell($event->getMob()->getCell());
        $respawn->setMonster($event->getMob()->getMonster());

        $this->entityManager->persist($respawn);
        $this->entityManager->flush();
    }
}