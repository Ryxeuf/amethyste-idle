<?php

namespace App\EventListener;

use App\Entity\App\Player;
use App\Enum\InfluenceActivityType;
use App\Event\CraftEvent;
use App\Event\Fight\MobDeadEvent;
use App\Event\Game\QuestCompletedEvent;
use App\Event\Map\ButcheringEvent;
use App\Event\Map\FishingEvent;
use App\Event\Zone\ZoneGatherEvent;
use App\GameEngine\Settlement\SettlementWeeklyWorkProgress;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Le chantier de la semaine se remplit en jouant (RET-05).
 *
 * **Aucun evenement nouveau** : les memes six boucles que le sediment (FOY-02)
 * et la commission (RET-02b). Un quatrieme consommateur se branche a cote des
 * trois autres, sans toucher aux emetteurs.
 *
 * La difference est le **lieu** : le chantier est celui de la zone ou l'action a
 * eu lieu, pas celui du joueur. La zone de la recolte est portee par
 * l'evenement ; pour les autres, c'est la zone courante — meme regle qu'au
 * depot de sediment.
 */
class SettlementWeeklyWorkListener implements EventSubscriberInterface
{
    public function __construct(
        private readonly SettlementWeeklyWorkProgress $progress,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            ZoneGatherEvent::NAME => 'onZoneGather',
            CraftEvent::NAME => 'onCraft',
            FishingEvent::NAME => 'onFishing',
            ButcheringEvent::NAME => 'onButchering',
            MobDeadEvent::NAME => 'onMobDead',
            QuestCompletedEvent::NAME => 'onQuestCompleted',
        ];
    }

    public function onZoneGather(ZoneGatherEvent $event): void
    {
        $this->progress->contribute(
            $event->getPlayer(),
            InfluenceActivityType::Harvest,
            $event->getQuantity(),
            $event->getZone(),
        );
    }

    public function onCraft(CraftEvent $event): void
    {
        $this->progress->contribute($event->getPlayer(), InfluenceActivityType::Craft);
    }

    public function onFishing(FishingEvent $event): void
    {
        if (!$event->isSuccess()) {
            return;
        }

        $this->progress->contribute($event->getPlayer(), InfluenceActivityType::Fishing);
    }

    public function onButchering(ButcheringEvent $event): void
    {
        if (\count($event->getHarvestedItems()) === 0) {
            return;
        }

        $this->progress->contribute($event->getPlayer(), InfluenceActivityType::Butchering);
    }

    /**
     * Un kill compte pour **chaque** joueur vivant du combat, comme au depot de
     * sediment : le chantier mesure l'aide apportee, et ils ont tous aide.
     */
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

        /** @var Player $player */
        foreach ($fight->getPlayers() as $player) {
            if ($player->isDead()) {
                continue;
            }
            $this->progress->contribute($player, InfluenceActivityType::MobKill);
        }
    }

    public function onQuestCompleted(QuestCompletedEvent $event): void
    {
        $this->progress->contribute($event->getPlayer(), InfluenceActivityType::Quest);
    }
}
