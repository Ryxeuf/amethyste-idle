<?php

namespace App\GameEngine\Reputation;

use App\Entity\Game\Faction;
use App\Event\Fight\MobDeadEvent;
use App\Event\Game\QuestCompletedEvent;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class ReputationListener implements EventSubscriberInterface
{
    public function __construct(
        private readonly ReputationManager $reputationManager,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            MobDeadEvent::NAME => 'onMobDead',
            QuestCompletedEvent::NAME => 'onQuestCompleted',
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

        if (null === $faction) {
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

        $amount = $this->reputationManager->getReputationAmount($monster->getLevel());

        foreach ($players as $player) {
            if ($player->isDead()) {
                continue;
            }
            $this->reputationManager->addReputation($player, $faction, $amount);
        }
    }

    public function onQuestCompleted(QuestCompletedEvent $event): void
    {
        $player = $event->getPlayer();
        $quest = $event->getQuest();
        $rewards = $quest->getRewards();

        $reputationRewards = $rewards['reputation'] ?? [];
        if (empty($reputationRewards)) {
            return;
        }

        $factionRepository = $this->entityManager->getRepository(Faction::class);

        foreach ($reputationRewards as $repReward) {
            $factionSlug = $repReward['faction_slug'] ?? null;
            $amount = (int) ($repReward['amount'] ?? 0);

            if (null === $factionSlug || $amount <= 0) {
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
