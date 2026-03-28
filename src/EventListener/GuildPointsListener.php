<?php

namespace App\EventListener;

use App\Entity\App\Player;
use App\Event\Fight\MobDeadEvent;
use App\Event\Game\QuestCompletedEvent;
use App\GameEngine\Guild\GuildManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Incremente les points globaux de la guilde du joueur
 * lors d'un kill de mob ou d'une quete completee.
 */
class GuildPointsListener implements EventSubscriberInterface
{
    public function __construct(
        private readonly GuildManager $guildManager,
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

        $fight = $mob->getFight();
        if ($fight === null) {
            return;
        }

        foreach ($fight->getPlayers() as $player) {
            if ($player->isDead()) {
                continue;
            }

            $points = 1 + (int) floor($mob->getLevel() / 5);
            $this->awardGuildPoints($player, $points);
        }

        $this->entityManager->flush();
    }

    public function onQuestCompleted(QuestCompletedEvent $event): void
    {
        $this->awardGuildPoints($event->getPlayer(), 5);
        $this->entityManager->flush();
    }

    private function awardGuildPoints(Player $player, int $points): void
    {
        $guild = $this->guildManager->getPlayerGuild($player);
        if ($guild === null) {
            return;
        }

        $guild->addPoints($points);
    }
}
