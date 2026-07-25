<?php

namespace App\GameEngine\Fight;

use App\Entity\App\Fight;
use App\Event\Fight\FightLootedEvent;
use App\GameEngine\Zone\LifeRegenManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class FightCleaner implements EventSubscriberInterface
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly LifeRegenManager $lifeRegenManager,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            FightLootedEvent::NAME => 'removeFight',
        ];
    }

    public function removeFight(FightLootedEvent $event)
    {
        $fight = $this->entityManager->getRepository(Fight::class)->find($event->getFightId());
        if ($fight instanceof Fight) {
            foreach ($fight->getMobs() as $mob) {
                foreach ($mob->getItems() as $item) {
                    $this->entityManager->remove($item);
                }
                $this->entityManager->remove($mob);
            }
            foreach ($fight->getPlayers() as $player) {
                $player->setFight(null);
                // Ancre la regen des PV a la sortie de combat (ZON-12).
                $this->lifeRegenManager->anchor($player);
            }
            $this->entityManager->remove($fight);

            $this->entityManager->flush();
        }
    }
}
