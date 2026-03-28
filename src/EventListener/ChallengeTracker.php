<?php

namespace App\EventListener;

use App\Entity\App\Guild;
use App\Entity\App\GuildChallengeProgress;
use App\Entity\App\Player;
use App\Entity\App\WeeklyChallenge;
use App\Enum\InfluenceActivityType;
use App\Event\CraftEvent;
use App\Event\Fight\MobDeadEvent;
use App\Event\Game\QuestCompletedEvent;
use App\Event\Map\ButcheringEvent;
use App\Event\Map\FishingEvent;
use App\Event\Map\SpotHarvestEvent;
use App\GameEngine\Guild\InfluenceManager;
use App\GameEngine\Realtime\Guild\InfluenceMercurePublisher;
use App\Helper\PlayerHelper;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class ChallengeTracker implements EventSubscriberInterface
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly InfluenceManager $influenceManager,
        private readonly PlayerHelper $playerHelper,
        private readonly InfluenceMercurePublisher $mercurePublisher,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            MobDeadEvent::NAME => ['onMobDead', -10],
            CraftEvent::NAME => ['onCraft', -10],
            SpotHarvestEvent::NAME => ['onSpotHarvest', -10],
            FishingEvent::NAME => ['onFishing', -10],
            ButcheringEvent::NAME => ['onButchering', -10],
            QuestCompletedEvent::NAME => ['onQuestCompleted', -10],
        ];
    }

    public function onMobDead(MobDeadEvent $event): void
    {
        $mob = $event->getMob();

        if ($mob->isSummoned()) {
            return;
        }

        $fight = $mob->getFight();
        if ($fight === null) {
            return;
        }

        foreach ($fight->getPlayers() as $player) {
            if ($player->isDead()) {
                continue;
            }

            $this->trackActivity($player, InfluenceActivityType::MobKill);
        }
    }

    public function onCraft(CraftEvent $event): void
    {
        $this->trackActivity($event->getPlayer(), InfluenceActivityType::Craft, $event->getQuantity());
    }

    public function onSpotHarvest(SpotHarvestEvent $event): void
    {
        $player = $this->playerHelper->getPlayer();
        if ($player === null) {
            return;
        }

        $itemCount = \count($event->getHarvestedItems());
        if ($itemCount === 0) {
            return;
        }

        $this->trackActivity($player, InfluenceActivityType::Harvest, $itemCount);
    }

    public function onFishing(FishingEvent $event): void
    {
        if (!$event->isSuccess()) {
            return;
        }

        $this->trackActivity($event->getPlayer(), InfluenceActivityType::Fishing);
    }

    public function onButchering(ButcheringEvent $event): void
    {
        $itemCount = \count($event->getHarvestedItems());
        if ($itemCount === 0) {
            return;
        }

        $this->trackActivity($event->getPlayer(), InfluenceActivityType::Butchering, $itemCount);
    }

    public function onQuestCompleted(QuestCompletedEvent $event): void
    {
        $this->trackActivity($event->getPlayer(), InfluenceActivityType::Quest);
    }

    private function trackActivity(Player $player, InfluenceActivityType $activityType, int $amount = 1): void
    {
        $guild = $this->influenceManager->getPlayerGuild($player);
        if ($guild === null) {
            return;
        }

        $challenges = $this->getActiveChallengesForType($activityType);
        if (\count($challenges) === 0) {
            return;
        }

        foreach ($challenges as $challenge) {
            $this->incrementProgress($guild, $challenge, $player, $amount);
        }
    }

    /**
     * @return list<WeeklyChallenge>
     */
    private function getActiveChallengesForType(InfluenceActivityType $activityType): array
    {
        $now = new \DateTime();

        return $this->entityManager->getRepository(WeeklyChallenge::class)
            ->createQueryBuilder('wc')
            ->where('wc.activityType = :type')
            ->andWhere('wc.startsAt <= :now')
            ->andWhere('wc.endsAt >= :now')
            ->setParameter('type', $activityType)
            ->setParameter('now', $now)
            ->getQuery()
            ->getResult();
    }

    private function incrementProgress(Guild $guild, WeeklyChallenge $challenge, Player $player, int $amount): void
    {
        $progress = $this->entityManager->getRepository(GuildChallengeProgress::class)->findOneBy([
            'guild' => $guild,
            'challenge' => $challenge,
        ]);

        if ($progress !== null && $progress->isCompleted()) {
            return;
        }

        if ($progress === null) {
            $progress = new GuildChallengeProgress();
            $progress->setGuild($guild);
            $progress->setChallenge($challenge);
            $progress->setCreatedAt(new \DateTime());
            $progress->setUpdatedAt(new \DateTime());
            $this->entityManager->persist($progress);
        }

        $progress->incrementProgress($amount);
        $progress->setUpdatedAt(new \DateTime());

        if ($progress->getProgress() >= $challenge->getTarget() && !$progress->isCompleted()) {
            $progress->setCompletedAt(new \DateTime());
            $this->awardBonusPoints($guild, $challenge, $player);
            $this->mercurePublisher->publishChallengeCompleted($guild, $challenge, $player);
        }

        $this->entityManager->flush();
    }

    private function awardBonusPoints(Guild $guild, WeeklyChallenge $challenge, Player $player): void
    {
        $season = $challenge->getSeason();
        $region = $this->influenceManager->getPlayerRegion($player);

        if ($region === null) {
            return;
        }

        $this->influenceManager->addPoints(
            $guild,
            $region,
            $season,
            $challenge->getBonusPoints(),
            $player,
            InfluenceActivityType::Challenge,
            ['challenge' => $challenge->getTitle(), 'bonus_points' => $challenge->getBonusPoints()],
        );
    }
}
