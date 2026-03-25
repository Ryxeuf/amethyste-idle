<?php

namespace App\EventListener;

use App\Entity\App\PlayerBestiary;
use App\Event\Fight\MobDeadEvent;
use App\Repository\PlayerBestiaryRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class BestiaryListener implements EventSubscriberInterface
{
    public function __construct(
        private readonly PlayerBestiaryRepository $bestiaryRepository,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            MobDeadEvent::NAME => 'onMobDead',
        ];
    }

    public function onMobDead(MobDeadEvent $event): void
    {
        $mob = $event->getMob();

        if ($mob->isSummoned()) {
            return;
        }

        $monster = $mob->getMonster();
        $fight = $mob->getFight();

        if ($fight === null) {
            return;
        }

        foreach ($fight->getPlayers() as $player) {
            if ($player->isDead()) {
                continue;
            }

            $entry = $this->bestiaryRepository->findOneByPlayerAndMonster($player, $monster);

            if ($entry === null) {
                $entry = new PlayerBestiary($player, $monster);
                $this->entityManager->persist($entry);
            } else {
                $entry->incrementKillCount();
            }
        }

        $this->entityManager->flush();
    }
}
