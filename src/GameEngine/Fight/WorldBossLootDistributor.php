<?php

namespace App\GameEngine\Fight;

use App\Entity\App\Mob;
use App\Entity\App\Player;
use App\Entity\App\PlayerItem;
use App\Event\Fight\MobDeadEvent;
use App\GameEngine\Event\GameEventBonusProvider;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Mercure\Update;

/**
 * Distribue le loot d'un world boss en fonction de la contribution de chaque joueur.
 *
 * - Top 3 contributeurs : loot garanti (tous les drops garantis + probabilité boostée)
 * - Autres contributeurs : loot probabiliste standard
 */
class WorldBossLootDistributor implements EventSubscriberInterface
{
    private const TOP_CONTRIBUTOR_COUNT = 3;
    private const TOP_CONTRIBUTOR_PROBABILITY_BONUS = 1.5;
    private const LOOT_SCALE_PER_PLAYER = 0.1;
    private const MAX_PARTICIPANT_LOOT_MULTIPLIER = 2.0;

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly GameEventBonusProvider $gameEventBonusProvider,
        private readonly HubInterface $hub,
        private readonly LoggerInterface $logger,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            MobDeadEvent::NAME => ['onMobDead', 10],
        ];
    }

    public function onMobDead(MobDeadEvent $event): void
    {
        $mob = $event->getMob();

        if (!$mob->isWorldBoss()) {
            return;
        }

        $fight = $mob->getFight();
        if ($fight === null) {
            return;
        }

        $this->distributeLoot($mob);
        $this->publishWorldBossDefeated($mob);

        // Empêcher le LootGenerator standard de traiter ce mob
        $event->stopPropagation();
    }

    private function distributeLoot(Mob $mob): void
    {
        $fight = $mob->getFight();
        if ($fight === null) {
            return;
        }

        $rankedContributors = $fight->getRankedContributors();
        $dropMultiplier = $this->gameEventBonusProvider->getDropMultiplier($mob->getMap());

        // Bonus de loot proportionnel au nombre de participants
        $participantCount = \count($rankedContributors);
        $participantBonus = min(
            self::MAX_PARTICIPANT_LOOT_MULTIPLIER,
            1.0 + self::LOOT_SCALE_PER_PLAYER * max(0, $participantCount - 1)
        );
        $dropMultiplier *= $participantBonus;

        $playerMap = [];
        foreach ($fight->getPlayers() as $player) {
            $playerMap[$player->getId()] = $player;
        }

        foreach ($rankedContributors as $contributor) {
            $playerId = $contributor['playerId'];
            $rank = $contributor['rank'];

            /** @var Player|null $player */
            $player = $playerMap[$playerId] ?? null;
            if ($player === null) {
                continue;
            }

            $isTopContributor = $rank <= self::TOP_CONTRIBUTOR_COUNT;
            $this->generatePlayerLoot($mob, $player, $isTopContributor, $dropMultiplier);
        }

        $this->entityManager->flush();
    }

    private function generatePlayerLoot(Mob $mob, Player $player, bool $isTopContributor, float $dropMultiplier): void
    {
        foreach ($mob->getMonster()->getMonsterItems() as $monsterItem) {
            $monsterDifficulty = $mob->getMonster()->getDifficulty();

            if (null !== $monsterItem->getMinDifficulty() && $monsterDifficulty < $monsterItem->getMinDifficulty()) {
                continue;
            }

            if ($monsterItem->isGuaranteed()) {
                // Seuls les top contributeurs reçoivent les drops garantis
                if ($isTopContributor) {
                    $item = new PlayerItem();
                    $item->setMob($mob);
                    $item->setGenericItem($monsterItem->getItem());
                    $item->setBoundToPlayerId($player->getId());
                    $mob->addItem($item);
                    $this->entityManager->persist($item);
                }

                continue;
            }

            $effectiveMultiplier = $isTopContributor
                ? $dropMultiplier * self::TOP_CONTRIBUTOR_PROBABILITY_BONUS
                : $dropMultiplier;

            $adjustedProbability = min(100, (int) round($monsterItem->getProbability() * $effectiveMultiplier));
            if (random_int(0, 99) < $adjustedProbability) {
                $item = new PlayerItem();
                $item->setMob($mob);
                $item->setGenericItem($monsterItem->getItem());
                $item->setBoundToPlayerId($player->getId());
                $mob->addItem($item);
                $this->entityManager->persist($item);
            }
        }

        $this->logger->info('[WorldBossLootDistributor] Loot generated for player {player} (top: {top})', [
            'player' => $player->getId(),
            'top' => $isTopContributor ? 'yes' : 'no',
        ]);
    }

    private function publishWorldBossDefeated(Mob $mob): void
    {
        $update = new Update(
            'map/respawn',
            json_encode([
                'topic' => 'map/respawn',
                'type' => 'world_boss_defeated',
                'object' => [
                    'id' => $mob->getId(),
                    'name' => $mob->getName(),
                ],
                'coordinates' => $mob->getCoordinates(),
                'mapId' => $mob->getMap()?->getId(),
            ], JSON_THROW_ON_ERROR)
        );

        $this->hub->publish($update);
    }
}
