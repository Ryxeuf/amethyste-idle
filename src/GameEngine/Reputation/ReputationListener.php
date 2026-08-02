<?php

namespace App\GameEngine\Reputation;

use App\Entity\Game\Faction;
use App\Event\Fight\MobDeadEvent;
use App\Event\Game\AuctionSaleEvent;
use App\Event\Game\QuestCompletedEvent;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Ce que les gestes rapportent aux maisons (FAC-02).
 *
 * GAME_WORLD § 6.4 b : « les quetes amorcent, les gestes font le regime de
 * croisiere. » Deux chemins, deux regimes : la quete passe par
 * `addReputation()` et n'est jamais plafonnee (on ne refait pas une quete) ;
 * le geste passe par le chemin plafonne (`grantCappedReputation` /
 * `grantGestureReputation`), borne par faction et par jour.
 */
class ReputationListener implements EventSubscriberInterface
{
    public function __construct(
        private readonly ReputationManager $reputationManager,
        private readonly EntityManagerInterface $entityManager,
        private readonly GestureReputationCatalog $gestureCatalog,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            MobDeadEvent::NAME => 'onMobDead',
            QuestCompletedEvent::NAME => 'onQuestCompleted',
            AuctionSaleEvent::NAME => 'onAuctionSale',
        ];
    }

    public function onMobDead(MobDeadEvent $event): void
    {
        $mob = $event->getMob();

        if ($mob->isSummoned()) {
            return;
        }

        $monster = $mob->getMonster();
        $faction = $monster->getFaction();
        $isUndead = null === $faction && $this->gestureCatalog->isUndead($monster->getSlug());

        if (null === $faction && !$isUndead) {
            return;
        }

        $fight = $mob->getFight();
        if (null === $fight) {
            return;
        }

        $players = $fight->getPlayers();
        if ($players->isEmpty()) {
            return;
        }

        $amount = $this->reputationManager->getReputationAmount($monster->getTier());

        foreach ($players as $player) {
            if ($player->isDead()) {
                continue;
            }
            if (null !== $faction) {
                $this->reputationManager->grantCappedReputation($player, $faction, $amount);
            } else {
                // Le mort-vivant n'a pas de faction propre : abattre ce que le
                // temps a laisse derriere lui est le geste de l'Ordre.
                $this->reputationManager->grantGestureReputation($player, 'undead_kill', $amount);
            }
        }
    }

    /**
     * Une vente conclue a l'hotel des ventes nourrit la Guilde des Marchands
     * — cote vendeur : c'est lui qui a servi le marche.
     */
    public function onAuctionSale(AuctionSaleEvent $event): void
    {
        $this->reputationManager->grantGestureReputation($event->getSeller(), 'auction_sale');
    }

    public function onQuestCompleted(QuestCompletedEvent $event): void
    {
        $player = $event->getPlayer();
        $quest = $event->getQuest();
        $rewards = $quest->getRewards();

        // 1. Reputation from base rewards.
        $reputationRewards = $rewards['reputation'] ?? [];

        // 2. Reputation from chosen moral-choice bonus rewards (if any).
        //    A quest with a choiceOutcome can grant/lose reputation on different
        //    factions depending on the choice made (e.g. siding with a faction
        //    earns reputation with them while losing some with the opposing one).
        $choiceMade = $event->getChoiceMade();
        $choiceOutcome = $quest->getChoiceOutcome();
        if (null !== $choiceMade && !empty($choiceOutcome)) {
            foreach ($choiceOutcome as $outcome) {
                if (($outcome['key'] ?? null) !== $choiceMade) {
                    continue;
                }
                $bonusRewards = $outcome['bonusRewards'] ?? [];
                $bonusReputation = $bonusRewards['reputation'] ?? [];
                if (!empty($bonusReputation)) {
                    $reputationRewards = array_merge($reputationRewards, $bonusReputation);
                }
                break;
            }
        }

        if (empty($reputationRewards)) {
            return;
        }

        $factionRepository = $this->entityManager->getRepository(Faction::class);

        foreach ($reputationRewards as $repReward) {
            $factionSlug = $repReward['faction_slug'] ?? null;
            $amount = (int) ($repReward['amount'] ?? 0);

            if (null === $factionSlug || 0 === $amount) {
                continue;
            }

            $faction = $factionRepository->findOneBy(['slug' => $factionSlug]);
            if (null === $faction) {
                continue;
            }

            $this->reputationManager->addReputation($player, $faction, $amount);
        }
    }
}
