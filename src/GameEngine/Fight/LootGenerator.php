<?php

namespace App\GameEngine\Fight;

use App\Entity\App\Mob;
use App\Entity\App\PlayerItem;
use App\Event\Fight\MobDeadEvent;
use App\GameEngine\Event\GameEventBonusProvider;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class LootGenerator implements EventSubscriberInterface
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly GameEventBonusProvider $gameEventBonusProvider,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            MobDeadEvent::NAME => 'mobDied',
        ];
    }

    public function mobDied(MobDeadEvent $event): void
    {
        // Les mobs invoqués en combat ne droppent pas de loot (anti-exploit)
        if ($event->getMob()->isSummoned()) {
            return;
        }

        $this->generateLoot($event->getMob());
    }

    protected function generateLoot(Mob $mob): void
    {
        // Les mobs invoqués en cours de combat ne droppent pas de loot
        if ($mob->isSummoned()) {
            return;
        }

        $dropMultiplier = $this->gameEventBonusProvider->getDropMultiplier($mob->getMap());

        // Dungeon difficulty drop bonus
        $fight = $mob->getFight();
        $dungeonDropMultiplier = $fight !== null
            ? (float) $fight->getMetadataValue('difficulty_drop_multiplier', 1.0)
            : 1.0;
        $dropMultiplier *= $dungeonDropMultiplier;

        $monsterDifficulty = $mob->getMonster()->getDifficulty();

        // Determine if this is a coop fight for round-robin loot distribution
        $coopPlayerIds = [];
        if ($fight !== null && $fight->isCoopFight()) {
            foreach ($fight->getPlayers() as $player) {
                if (!$player->isDead()) {
                    $coopPlayerIds[] = $player->getId();
                }
            }
        }
        $isCoopLoot = count($coopPlayerIds) > 1;
        $roundRobinIndex = 0;

        foreach ($mob->getMonster()->getMonsterItems() as $monsterItem) {
            if (null !== $monsterItem->getMinDifficulty() && $monsterDifficulty < $monsterItem->getMinDifficulty()) {
                continue;
            }

            if ($monsterItem->isGuaranteed()) {
                $item = new PlayerItem();
                $item->setMob($mob);
                $item->setGenericItem($monsterItem->getItem());
                if ($isCoopLoot) {
                    $item->setBoundToPlayerId($coopPlayerIds[$roundRobinIndex % count($coopPlayerIds)]);
                    ++$roundRobinIndex;
                }
                $mob->addItem($item);
                $this->entityManager->persist($item);

                continue;
            }

            $adjustedProbability = min(100, (int) round($monsterItem->getProbability() * $dropMultiplier));
            if (random_int(0, 99) < $adjustedProbability) {
                $item = new PlayerItem();
                $item->setMob($mob);
                $item->setGenericItem($monsterItem->getItem());
                if ($isCoopLoot) {
                    $item->setBoundToPlayerId($coopPlayerIds[$roundRobinIndex % count($coopPlayerIds)]);
                    ++$roundRobinIndex;
                }
                $mob->addItem($item);
                $this->entityManager->persist($item);
            }
        }

        $this->entityManager->flush();
    }
}
