<?php

namespace App\EventListener;

use App\Entity\App\Player;
use App\Entity\App\Region;
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

class InfluenceListener implements EventSubscriberInterface
{
    public function __construct(
        private readonly InfluenceManager $influenceManager,
        private readonly PlayerHelper $playerHelper,
        private readonly EntityManagerInterface $entityManager,
        private readonly InfluenceMercurePublisher $mercurePublisher,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            MobDeadEvent::NAME => 'onMobDead',
            CraftEvent::NAME => 'onCraft',
            SpotHarvestEvent::NAME => 'onSpotHarvest',
            FishingEvent::NAME => 'onFishing',
            ButcheringEvent::NAME => 'onButchering',
            QuestCompletedEvent::NAME => 'onQuestCompleted',
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

        $region = $mob->getMap()?->getRegion();
        if ($region === null) {
            return;
        }

        foreach ($fight->getPlayers() as $player) {
            if ($player->isDead()) {
                continue;
            }

            $this->awardForPlayer(
                $player,
                InfluenceActivityType::MobKill,
                ['mob_level' => $mob->getLevel()],
                $region,
                ['monster' => $mob->getMonster()->getSlug(), 'level' => $mob->getLevel()],
            );
        }

        $this->entityManager->flush();
    }

    public function onCraft(CraftEvent $event): void
    {
        $player = $event->getPlayer();
        $recipe = $event->getRecipe();

        $this->awardForPlayer(
            $player,
            InfluenceActivityType::Craft,
            ['recipe_level' => $recipe->getRequiredLevel()],
            null,
            ['recipe' => $recipe->getName(), 'item' => $event->getResultItem()->getSlug(), 'qty' => $event->getQuantity()],
        );

        $this->entityManager->flush();
    }

    public function onSpotHarvest(SpotHarvestEvent $event): void
    {
        $player = $this->playerHelper->getPlayer();
        if ($player === null) {
            return;
        }

        $region = $event->getObjectLayer()->getMap()?->getRegion();
        $itemCount = \count($event->getHarvestedItems());

        if ($itemCount === 0) {
            return;
        }

        $this->awardForPlayer(
            $player,
            InfluenceActivityType::Harvest,
            ['item_count' => $itemCount],
            $region,
            ['spot' => $event->getObjectLayer()->getSlug(), 'items' => $itemCount],
        );

        $this->entityManager->flush();
    }

    public function onFishing(FishingEvent $event): void
    {
        if (!$event->isSuccess()) {
            return;
        }

        $player = $event->getPlayer();
        $region = $event->getObjectLayer()->getMap()?->getRegion();

        $this->awardForPlayer(
            $player,
            InfluenceActivityType::Fishing,
            [],
            $region,
            ['spot' => $event->getObjectLayer()->getSlug()],
        );

        $this->entityManager->flush();
    }

    public function onButchering(ButcheringEvent $event): void
    {
        $player = $event->getPlayer();
        $itemCount = \count($event->getHarvestedItems());

        if ($itemCount === 0) {
            return;
        }

        $region = $event->getMob()->getMap()?->getRegion();

        $this->awardForPlayer(
            $player,
            InfluenceActivityType::Butchering,
            ['item_count' => $itemCount],
            $region,
            ['mob' => $event->getMob()->getMonster()->getSlug(), 'items' => $itemCount],
        );

        $this->entityManager->flush();
    }

    public function onQuestCompleted(QuestCompletedEvent $event): void
    {
        $player = $event->getPlayer();

        $this->awardForPlayer(
            $player,
            InfluenceActivityType::Quest,
            ['quest_tier' => 1],
            null,
            ['quest' => $event->getQuest()->getName()],
        );

        $this->entityManager->flush();
    }

    /**
     * @param array<string, mixed>      $context
     * @param array<string, mixed>|null $details
     */
    private function awardForPlayer(
        Player $player,
        InfluenceActivityType $activityType,
        array $context = [],
        ?Region $region = null,
        ?array $details = null,
    ): void {
        $result = $this->influenceManager->awardInfluence($player, $activityType, $context, $region, $details);

        if ($result['awarded']) {
            $this->mercurePublisher->onInfluenceAwarded(
                $result['guild'],
                $result['region'],
                $result['season'],
                $player,
                $activityType,
                $result['points'],
            );
        }
    }
}
