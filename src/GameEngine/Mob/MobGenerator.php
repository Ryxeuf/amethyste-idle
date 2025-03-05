<?php

namespace App\GameEngine\Mob;

use App\Entity\App\Mob;
use App\Entity\Game\Monster;
use App\Event\Map\MobRespawnedEvent;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class MobGenerator
{
    /** @var EntityManagerInterface */
    protected $entityManager;
    /** @var EventDispatcherInterface */
    protected $eventDispatcher;

    public function __construct(EntityManagerInterface $entityManager, EventDispatcherInterface $eventDispatcher)
    {
        $this->entityManager = $entityManager;
        $this->eventDispatcher = $eventDispatcher;
    }

    public function generate(Monster $monster, Cell $cell)
    {
        $mob = new Mob();
        $mob->setMonster($monster);
        $mob->setLife($monster->getLife());
        $mob->setCell($cell);
        $mob->setLevel($monster->getLevel());

        $this->entityManager->persist($mob);
        $this->entityManager->flush();

        $this->eventDispatcher->dispatch(new MobRespawnedEvent($mob), MobRespawnedEvent::NAME);
    }
}