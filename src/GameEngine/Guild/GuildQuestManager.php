<?php

namespace App\GameEngine\Guild;

use App\Entity\App\Guild;
use App\Entity\App\GuildQuest;
use App\Entity\App\Player;
use App\Entity\Game\Monster;
use App\Entity\Game\Recipe;
use App\Enum\GuildQuestType;
use Doctrine\ORM\EntityManagerInterface;

class GuildQuestManager
{
    public const QUESTS_PER_WEEK = 3;
    public const QUEST_DURATION_DAYS = 7;

    private const KILL_GOALS = [20, 30, 50, 75, 100];
    private const COLLECT_GOALS = [15, 25, 40, 60];
    private const CRAFT_GOALS = [5, 10, 15, 20];

    private const BASE_GILS_REWARD = 500;
    private const BASE_POINTS_REWARD = 10;

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly GuildManager $guildManager,
    ) {
    }

    /**
     * @return GuildQuest[]
     */
    public function getActiveQuests(Guild $guild): array
    {
        return $this->entityManager->getRepository(GuildQuest::class)
            ->createQueryBuilder('gq')
            ->where('gq.guild = :guild')
            ->andWhere('gq.expiresAt > :now')
            ->andWhere('gq.completedAt IS NULL')
            ->setParameter('guild', $guild)
            ->setParameter('now', new \DateTime())
            ->orderBy('gq.expiresAt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return GuildQuest[]
     */
    public function getCompletedQuests(Guild $guild, int $limit = 10): array
    {
        return $this->entityManager->getRepository(GuildQuest::class)
            ->createQueryBuilder('gq')
            ->where('gq.guild = :guild')
            ->andWhere('gq.completedAt IS NOT NULL')
            ->setParameter('guild', $guild)
            ->orderBy('gq.completedAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Generate weekly quests for a guild if needed.
     *
     * @return GuildQuest[]
     */
    public function generateWeeklyQuests(Guild $guild): array
    {
        $activeQuests = $this->getActiveQuests($guild);
        if (\count($activeQuests) >= self::QUESTS_PER_WEEK) {
            return $activeQuests;
        }

        $needed = self::QUESTS_PER_WEEK - \count($activeQuests);
        $expiresAt = new \DateTime(sprintf('+%d days', self::QUEST_DURATION_DAYS));
        $newQuests = [];

        $types = GuildQuestType::cases();
        $usedTypes = array_map(fn (GuildQuest $q) => $q->getType(), $activeQuests);

        // Try to pick different types from what's already active
        $availableTypes = array_values(array_filter(
            $types,
            fn (GuildQuestType $t) => !\in_array($t, $usedTypes, true)
        ));

        for ($i = 0; $i < $needed; ++$i) {
            if (\count($availableTypes) > 0) {
                $type = $availableTypes[array_rand($availableTypes)];
                $availableTypes = array_values(array_filter(
                    $availableTypes,
                    fn (GuildQuestType $t) => $t !== $type
                ));
            } else {
                $type = $types[array_rand($types)];
            }

            $quest = $this->buildQuest($guild, $type, $expiresAt);
            if ($quest !== null) {
                $this->entityManager->persist($quest);
                $newQuests[] = $quest;
            }
        }

        if (\count($newQuests) > 0) {
            $this->entityManager->flush();
        }

        return array_merge($activeQuests, $newQuests);
    }

    public function trackProgress(Guild $guild, GuildQuestType $type, string $targetSlug, int $amount = 1): void
    {
        $quests = $this->entityManager->getRepository(GuildQuest::class)
            ->createQueryBuilder('gq')
            ->where('gq.guild = :guild')
            ->andWhere('gq.type = :type')
            ->andWhere('gq.target = :target')
            ->andWhere('gq.completedAt IS NULL')
            ->andWhere('gq.expiresAt > :now')
            ->setParameter('guild', $guild)
            ->setParameter('type', $type)
            ->setParameter('target', $targetSlug)
            ->setParameter('now', new \DateTime())
            ->getQuery()
            ->getResult();

        foreach ($quests as $quest) {
            $quest->addProgress($amount);

            if ($quest->getProgress() >= $quest->getGoal()) {
                $this->completeQuest($quest);
            }
        }

        if (\count($quests) > 0) {
            $this->entityManager->flush();
        }
    }

    public function getPlayerGuild(Player $player): ?Guild
    {
        return $this->guildManager->getPlayerGuild($player);
    }

    private function completeQuest(GuildQuest $quest): void
    {
        $quest->setCompletedAt(new \DateTime());

        $guild = $quest->getGuild();
        $guild->addPoints($quest->getPointsReward());

        // Distribute gils to all members
        $gilsPerMember = $this->calculateGilsPerMember($quest);
        foreach ($guild->getMembers() as $member) {
            $member->getPlayer()->addGils($gilsPerMember);
        }
    }

    private function calculateGilsPerMember(GuildQuest $quest): int
    {
        $memberCount = $quest->getGuild()->getMemberCount();
        if ($memberCount <= 0) {
            return 0;
        }

        return max(1, (int) floor($quest->getGilsReward() / $memberCount));
    }

    private function buildQuest(Guild $guild, GuildQuestType $type, \DateTimeInterface $expiresAt): ?GuildQuest
    {
        $definition = match ($type) {
            GuildQuestType::Kill => $this->buildKillQuest(),
            GuildQuestType::Collect => $this->buildCollectQuest(),
            GuildQuestType::Craft => $this->buildCraftQuest(),
        };

        if ($definition === null) {
            return null;
        }

        $quest = new GuildQuest();
        $quest->setGuild($guild);
        $quest->setType($type);
        $quest->setTarget($definition['target']);
        $quest->setTargetLabel($definition['targetLabel']);
        $quest->setGoal($definition['goal']);
        $quest->setGilsReward($definition['gilsReward']);
        $quest->setPointsReward($definition['pointsReward']);
        $quest->setExpiresAt($expiresAt);

        return $quest;
    }

    /**
     * @return array{target: string, targetLabel: string, goal: int, gilsReward: int, pointsReward: int}|null
     */
    private function buildKillQuest(): ?array
    {
        $monsters = $this->entityManager->getRepository(Monster::class)->findBy(
            ['isBoss' => false],
        );

        if (\count($monsters) === 0) {
            return null;
        }

        $monster = $monsters[array_rand($monsters)];
        $goal = self::KILL_GOALS[array_rand(self::KILL_GOALS)];
        $multiplier = 1 + ($monster->getLevel() / 10);

        return [
            'target' => $monster->getSlug(),
            'targetLabel' => $monster->getName(),
            'goal' => $goal,
            'gilsReward' => (int) round(self::BASE_GILS_REWARD * $multiplier * ($goal / 20)),
            'pointsReward' => (int) round(self::BASE_POINTS_REWARD * $multiplier * ($goal / 20)),
        ];
    }

    /**
     * @return array{target: string, targetLabel: string, goal: int, gilsReward: int, pointsReward: int}|null
     */
    private function buildCollectQuest(): ?array
    {
        $recipes = $this->entityManager->getRepository(Recipe::class)->findAll();

        if (\count($recipes) === 0) {
            return null;
        }

        $recipe = $recipes[array_rand($recipes)];
        $goal = self::COLLECT_GOALS[array_rand(self::COLLECT_GOALS)];
        $multiplier = 1 + ($recipe->getRequiredLevel() / 10);

        return [
            'target' => $recipe->getResult()->getSlug(),
            'targetLabel' => $recipe->getResult()->getName(),
            'goal' => $goal,
            'gilsReward' => (int) round(self::BASE_GILS_REWARD * $multiplier * ($goal / 15)),
            'pointsReward' => (int) round(self::BASE_POINTS_REWARD * $multiplier * ($goal / 15)),
        ];
    }

    /**
     * @return array{target: string, targetLabel: string, goal: int, gilsReward: int, pointsReward: int}|null
     */
    private function buildCraftQuest(): ?array
    {
        $recipes = $this->entityManager->getRepository(Recipe::class)->findAll();

        if (\count($recipes) === 0) {
            return null;
        }

        $recipe = $recipes[array_rand($recipes)];
        $goal = self::CRAFT_GOALS[array_rand(self::CRAFT_GOALS)];
        $multiplier = 1 + ($recipe->getRequiredLevel() / 10);

        return [
            'target' => $recipe->getSlug(),
            'targetLabel' => $recipe->getName(),
            'goal' => $goal,
            'gilsReward' => (int) round(self::BASE_GILS_REWARD * $multiplier * ($goal / 5)),
            'pointsReward' => (int) round(self::BASE_POINTS_REWARD * $multiplier * ($goal / 5)),
        ];
    }
}
