<?php

namespace App\GameEngine\Fight;

use App\Entity\App\Fight;
use App\Event\Fight\FightLootedEvent;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class FightCleaner implements EventSubscriberInterface
{
    public function __construct(private readonly EntityManagerInterface $entityManager)
    {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            FightLootedEvent::NAME => 'removeFight',
        ];
    }

    public function removeFight(FightLootedEvent $event)
    {
        /** @var Fight $fight */
        if ($fight = $this->entityManager->getRepository(Fight::class)->find($event->getFightId())) {
            foreach ($fight->getMobs() as $mob) {
                foreach ($mob->getItems() as $item) {
                    $this->entityManager->remove($item);
                }
                $this->entityManager->remove($mob);
            }
            foreach ($fight->getPlayers() as $player) {
                $player->setFight(null);
            }
            $this->entityManager->remove($fight);

            $this->entityManager->flush();
        }
    }
}
