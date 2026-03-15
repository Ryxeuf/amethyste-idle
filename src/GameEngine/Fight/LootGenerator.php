<?php

namespace App\GameEngine\Fight;

use App\Entity\App\Mob;
use App\Entity\App\PlayerItem;
use App\Event\Fight\MobDeadEvent;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class LootGenerator implements EventSubscriberInterface
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

    public function mobDied(MobDeadEvent $event)
    {
        $this->generateLoot($event->getMob());
    }

    protected function generateLoot(Mob $mob)
    {
        $items = [];
        foreach ($mob->getMonster()->getMonsterItems() as $monsterItem) {
            if (random_int(0, 99) < $monsterItem->getProbability()) {
                $item = new PlayerItem();
                $item->setMob($mob);
                $item->setGenericItem($monsterItem->getItem());

                $mob->addItem($item);

                $this->entityManager->persist($item);
            }
        }
        $mob->setItems(new \Doctrine\Common\Collections\ArrayCollection($items));

        $this->entityManager->flush();
        $this->entityManager->refresh($mob);
    }
}
