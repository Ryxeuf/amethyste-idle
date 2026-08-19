<?php

namespace App\GameEngine\Fight;

use App\Entity\App\Mob;
use App\Entity\App\PlayerItem;
use App\Event\Fight\MobDeadEvent;
use App\GameEngine\Event\GameEventBonusProvider;
use App\GameEngine\Materia\MateriaLootTable;
use App\GameEngine\Reputation\CounterfeitService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class LootGenerator implements EventSubscriberInterface
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly GameEventBonusProvider $gameEventBonusProvider,
        private readonly MateriaLootTable $materiaLootTable,
        private readonly CounterfeitService $counterfeitService,
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
        $dungeonDropMultiplier = (float) ($mob->getFight()?->getMetadataValue('difficulty_drop_multiplier', 1.0) ?? 1.0);
        $dropMultiplier *= $dungeonDropMultiplier;

        $monsterRank = $mob->getMonster()->getRank();

        // Determine if this is a coop fight for round-robin loot distribution
        $fight = $mob->getFight();
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
            if (null !== $monsterItem->getMinRank() && !$monsterRank->atLeast($monsterItem->getMinRank())) {
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

        // MAT-05 : le butin de materia se derive de l'element et du palier du
        // monstre — la table n'est plus ecrite a la main, et la fourchette
        // canonique (4-10 %) profite du meme multiplicateur que le reste.
        $materia = $this->materiaLootTable->roll($mob->getMonster(), $dropMultiplier);
        if (null !== $materia) {
            $item = new PlayerItem();
            $item->setMob($mob);
            $item->setGenericItem($materia);
            // REP-01 : la provenance, la ou elle est **vraie**. Une materia
            // tombee d'un monstre sait de quelle zone elle vient, et c'est le
            // seul chemin du jeu ou le monde la donne — l'echoppe, l'etabli et
            // la quete n'en savent rien, et elles la laissent inconnue.
            $item->setOriginZoneId($mob->getZone()?->getId());
            // FAC-07 : une part du butin sort contrefaite, non identifiee —
            // l'unique source involontaire du monde, et la raison d'etre de
            // l'œil du faussaire. Indiscernable jusqu'a la trahison.
            $this->counterfeitService->maybeMarkLoot($item);
            if ($isCoopLoot) {
                $item->setBoundToPlayerId($coopPlayerIds[$roundRobinIndex % count($coopPlayerIds)]);
                ++$roundRobinIndex;
            }
            $mob->addItem($item);
            $this->entityManager->persist($item);
        }

        $this->entityManager->flush();
        $this->entityManager->refresh($mob);
    }
}
