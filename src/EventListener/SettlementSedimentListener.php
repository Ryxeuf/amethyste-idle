<?php

namespace App\EventListener;

use App\Event\CraftEvent;
use App\Event\Fight\MobDeadEvent;
use App\Event\Game\QuestCompletedEvent;
use App\Event\Map\ButcheringEvent;
use App\Event\Map\FishingEvent;
use App\Event\Zone\PlayerTraveledEvent;
use App\Event\Zone\ZoneGatherEvent;
use App\GameEngine\Settlement\SettlementDepositService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * L'activite des joueurs devient la matiere du monde (FOY-02).
 *
 * **Aucun evenement domaine nouveau** : ce sont ceux que le jeu emet deja. On
 * branche un second consommateur a cote d'`InfluenceListener`, sans toucher aux
 * emetteurs.
 *
 * La zone visee est toujours `player.currentZone` (regle 7 — jamais des
 * coordonnees), sauf a l'arrivee d'un voyage, ou l'evenement porte lui-meme la
 * zone d'arrivee : la lire sur le joueur marcherait aujourd'hui, mais dependrait
 * de l'ordre dans lequel `ZoneTravelService` ecrit et emet.
 *
 * Le flush est laisse a l'appelant partout ou l'action en fait deja un — le
 * depot de sediment ne doit jamais etre la raison d'un aller-retour en base
 * supplementaire sur une action de jeu.
 */
class SettlementSedimentListener implements EventSubscriberInterface
{
    public function __construct(
        private readonly SettlementDepositService $depositService,
        private readonly EntityManagerInterface $entityManager,
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
            PlayerTraveledEvent::NAME => 'onPlayerTraveled',
        ];
    }

    public function onZoneGather(ZoneGatherEvent $event): void
    {
        $this->depositService->deposit($event->getPlayer(), 'harvest', $event->getZone());
        $this->entityManager->flush();
    }

    public function onCraft(CraftEvent $event): void
    {
        $this->depositService->deposit($event->getPlayer(), 'craft');
        $this->entityManager->flush();
    }

    public function onFishing(FishingEvent $event): void
    {
        if (!$event->isSuccess()) {
            return;
        }

        $this->depositService->deposit($event->getPlayer(), 'fishing');
        $this->entityManager->flush();
    }

    public function onButchering(ButcheringEvent $event): void
    {
        if (\count($event->getHarvestedItems()) === 0) {
            return;
        }

        $this->depositService->deposit($event->getPlayer(), 'butchering');
        $this->entityManager->flush();
    }

    /**
     * Un kill depose pour **chaque** joueur vivant du combat.
     *
     * Une invocation ne depose rien : elle mesurerait la depense de mana d'un
     * joueur, pas la frequentation d'une zone.
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

        $deposited = false;
        foreach ($fight->getPlayers() as $player) {
            if ($player->isDead()) {
                continue;
            }
            $this->depositService->deposit($player, 'mob_kill');
            $deposited = true;
        }

        if ($deposited) {
            $this->entityManager->flush();
        }
    }

    public function onQuestCompleted(QuestCompletedEvent $event): void
    {
        $this->depositService->deposit($event->getPlayer(), 'quest');
        $this->entityManager->flush();
    }

    /**
     * Traverser une zone y laisse une trace faible mais reelle.
     *
     * C'est le levier 4 de GAME_WORLD § 5.5, chiffre : vingt traversees par jour
     * tiennent un Campement. Une zone de passage vit donc sans qu'on y farme —
     * mais elle ne gagne jamais d'identite, le depot etant reparti sur les
     * quatre indices.
     */
    public function onPlayerTraveled(PlayerTraveledEvent $event): void
    {
        $this->depositService->deposit($event->getPlayer(), 'travel', $event->getZone());
        $this->entityManager->flush();
    }
}
