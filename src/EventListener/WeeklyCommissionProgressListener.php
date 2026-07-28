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
use App\GameEngine\Retention\WeeklyCommissionProgress;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * La commission avance en jouant (RET-02b).
 *
 * **Aucun evenement nouveau**, comme pour le sediment (FOY-02) : ce sont les six
 * boucles que le jeu emet deja. Un troisieme consommateur se branche a cote
 * d'`InfluenceListener` et de `SettlementSedimentListener`, sans toucher aux
 * emetteurs.
 *
 * La difference avec les deux autres est que l'avancement est **personnel** :
 * il ne mesure pas la frequentation d'une zone mais ce qu'un joueur a fait de sa
 * semaine. Un kill de groupe fait donc avancer la commission de **chaque**
 * participant vivant, et pas une seule fois.
 *
 * L'unite compte : la recolte avance de la **quantite recoltee**, pas de un.
 * « Prelevez 60 unites de ressource » se compterait sinon en 60 actions, soit
 * pres d'un jour entier d'energie — un rendez-vous hebdomadaire deviendrait une
 * corvee quotidienne.
 */
class WeeklyCommissionProgressListener implements EventSubscriberInterface
{
    public function __construct(
        private readonly WeeklyCommissionProgress $progress,
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
        $quantity = $event->getQuantity();
        if ($quantity <= 0) {
            return;
        }

        $this->progress->advance($event->getPlayer(), InfluenceActivityType::Harvest, $quantity);
    }

    public function onCraft(CraftEvent $event): void
    {
        $this->progress->advance($event->getPlayer(), InfluenceActivityType::Craft);
    }

    public function onFishing(FishingEvent $event): void
    {
        if (!$event->isSuccess()) {
            return;
        }

        $this->progress->advance($event->getPlayer(), InfluenceActivityType::Fishing);
    }

    public function onButchering(ButcheringEvent $event): void
    {
        if (\count($event->getHarvestedItems()) === 0) {
            return;
        }

        $this->progress->advance($event->getPlayer(), InfluenceActivityType::Butchering);
    }

    /**
     * Un kill avance la commission de **chaque** joueur vivant du combat.
     *
     * Une invocation ne compte pas : elle mesurerait la depense de mana d'un
     * joueur, pas sa chasse — meme regle qu'au depot de sediment.
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
            $this->progress->advance($player, InfluenceActivityType::MobKill);
        }
    }

    public function onQuestCompleted(QuestCompletedEvent $event): void
    {
        $this->progress->advance($event->getPlayer(), InfluenceActivityType::Quest);
    }
}
