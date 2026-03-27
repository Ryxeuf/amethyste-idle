<?php

namespace App\GameEngine\Achievement;

use App\Entity\App\Player;
use App\Entity\App\PlayerAchievement;
use App\Entity\Game\Achievement;
use App\Enum\DungeonDifficulty;
use App\Event\CraftEvent;
use App\Event\Fight\CombatFleeEvent;
use App\Event\Fight\MobDeadEvent;
use App\Event\Fight\PlayerDeadEvent;
use App\Event\Game\DungeonCompletedEvent;
use App\Event\Game\QuestCompletedEvent;
use App\Event\GatheringEvent;
use App\Repository\PlayerAchievementRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class AchievementTracker implements EventSubscriberInterface
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly PlayerAchievementRepository $playerAchievementRepository,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            MobDeadEvent::NAME => 'onMobDead',
            QuestCompletedEvent::NAME => 'onQuestCompleted',
            DungeonCompletedEvent::NAME => 'onDungeonCompleted',
            GatheringEvent::NAME => 'onGathering',
            CraftEvent::NAME => 'onCraft',
            PlayerDeadEvent::NAME => 'onPlayerDead',
            CombatFleeEvent::NAME => 'onCombatFlee',
        ];
    }

    public function onMobDead(MobDeadEvent $event): void
    {
        $mob = $event->getMob();

        if ($mob->isSummoned()) {
            return;
        }

        $fight = $mob->getFight();
        if (!$fight) {
            return;
        }

        $players = $fight->getPlayers();
        if ($players->isEmpty()) {
            return;
        }

        $player = $players->first();
        $monsterSlug = $mob->getMonster()->getSlug();

        // Update mob_kill achievements for this monster type
        $this->progressAchievements($player, 'mob_kill', $monsterSlug);

        // Update monster_discovery achievements
        $this->checkDiscoveryAchievements($player);
    }

    public function onQuestCompleted(QuestCompletedEvent $event): void
    {
        $this->progressAchievements($event->getPlayer(), 'quest_complete');
    }

    public function onDungeonCompleted(DungeonCompletedEvent $event): void
    {
        $player = $event->getPlayer();

        $this->progressAchievements($player, 'dungeon_clear');

        if ($event->getDungeonRun()->getDifficulty() === DungeonDifficulty::Mythic) {
            $this->progressAchievements($player, 'dungeon_clear_mythic');
        }
    }

    public function onGathering(GatheringEvent $event): void
    {
        $this->progressAchievements($event->getPlayer(), 'gathering', null, $event->getQuantity());
    }

    public function onCraft(CraftEvent $event): void
    {
        $this->progressAchievements($event->getPlayer(), 'craft', null, $event->getQuantity());
    }

    public function onPlayerDead(PlayerDeadEvent $event): void
    {
        $this->progressAchievements($event->getPlayer(), 'player_death');
    }

    public function onCombatFlee(CombatFleeEvent $event): void
    {
        $this->progressAchievements($event->getPlayer(), 'combat_flee');
    }

    private function progressAchievements(Player $player, string $criteriaType, ?string $monsterSlug = null, int $increment = 1): void
    {
        $achievements = $this->entityManager->getRepository(Achievement::class)->findBy([]);

        foreach ($achievements as $achievement) {
            if ($achievement->getCriteriaType() !== $criteriaType) {
                continue;
            }

            // For mob_kill, match monster slug
            if ('mob_kill' === $criteriaType && $achievement->getCriteriaMonsterSlug() !== $monsterSlug) {
                continue;
            }

            $playerAchievement = $this->playerAchievementRepository->findOneByPlayerAndAchievement($player, $achievement);

            if (null === $playerAchievement) {
                $playerAchievement = new PlayerAchievement();
                $playerAchievement->setPlayer($player);
                $playerAchievement->setAchievement($achievement);
                $this->entityManager->persist($playerAchievement);
            }

            if ($playerAchievement->isCompleted()) {
                continue;
            }

            $newProgress = $playerAchievement->getProgress() + $increment;
            $playerAchievement->setProgress($newProgress);

            if ($newProgress >= $achievement->getCriteriaCount()) {
                $playerAchievement->setCompletedAt(new \DateTime());
                $this->applyReward($player, $achievement);
            }
        }

        $this->entityManager->flush();
    }

    private function checkDiscoveryAchievements(Player $player): void
    {
        $achievements = $this->entityManager->getRepository(Achievement::class)->findBy([]);
        $discoveryAchievements = array_filter($achievements, fn (Achievement $a) => 'monster_discovery' === $a->getCriteriaType());

        if (empty($discoveryAchievements)) {
            return;
        }

        // Count distinct monster types killed: count completed mob_kill achievements with count=10 (first tier)
        // This is a simple heuristic: each monster type for which you've killed at least 10 counts as "discovered"
        // Alternative: count any mob_kill PlayerAchievement with progress >= 1
        $distinctCount = $this->countDistinctMonstersEngaged($player);

        foreach ($discoveryAchievements as $achievement) {
            $playerAchievement = $this->playerAchievementRepository->findOneByPlayerAndAchievement($player, $achievement);

            if (null === $playerAchievement) {
                $playerAchievement = new PlayerAchievement();
                $playerAchievement->setPlayer($player);
                $playerAchievement->setAchievement($achievement);
                $this->entityManager->persist($playerAchievement);
            }

            if ($playerAchievement->isCompleted()) {
                continue;
            }

            $playerAchievement->setProgress($distinctCount);

            if ($distinctCount >= $achievement->getCriteriaCount()) {
                $playerAchievement->setCompletedAt(new \DateTime());
                $this->applyReward($player, $achievement);
            }
        }

        $this->entityManager->flush();
    }

    private function countDistinctMonstersEngaged(Player $player): int
    {
        // Count distinct monster types by looking at mob_kill PlayerAchievements with progress >= 1
        $qb = $this->entityManager->createQueryBuilder();
        $qb->select('COUNT(pa.id)')
            ->from(PlayerAchievement::class, 'pa')
            ->join('pa.achievement', 'a')
            ->where('pa.player = :player')
            ->andWhere('pa.progress >= 1')
            ->andWhere('a.category = :category')
            ->setParameter('player', $player)
            ->setParameter('category', 'combat');

        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    private function applyReward(Player $player, Achievement $achievement): void
    {
        $reward = $achievement->getReward();
        if (!$reward) {
            return;
        }

        if (isset($reward['gils'])) {
            $player->addGils((int) $reward['gils']);
        }
    }
}
