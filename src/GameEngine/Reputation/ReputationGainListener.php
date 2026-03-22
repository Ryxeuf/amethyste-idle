<?php

namespace App\GameEngine\Reputation;

use App\Entity\Game\Faction;
use App\Event\Fight\MobDeadEvent;
use App\Event\Game\QuestCompletedEvent;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class ReputationGainListener implements EventSubscriberInterface
{
    private const MOB_KILL_BASE_REPUTATION = 5;

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
        $monster = $mob->getMonster();
        $faction = $monster->getFaction();

        if (null === $faction) {
            return;
        }

        $fight = $mob->getFight();
        if (null === $fight) {
            return;
        }

        $reputationReward = $monster->getFactionReputationReward() ?? self::MOB_KILL_BASE_REPUTATION;

        foreach ($fight->getPlayers() as $player) {
            if ($player->isDead()) {
                continue;
            }

            $this->reputationManager->addReputation($player, $faction, $reputationReward);
        }
    }

    public function onQuestCompleted(QuestCompletedEvent $event): void
    {
        $quest = $event->getQuest();
        $player = $event->getPlayer();
        $rewards = $quest->getRewards();

        $factionReputation = $rewards['faction_reputation'] ?? [];
        if (empty($factionReputation)) {
            return;
        }

        $factionRepository = $this->entityManager->getRepository(Faction::class);

        foreach ($factionReputation as $factionSlug => $amount) {
            $faction = $factionRepository->findOneBy(['slug' => $factionSlug]);
            if (null === $faction) {
                continue;
            }

            $this->reputationManager->addReputation($player, $faction, (int) $amount);
        }
    }
}
