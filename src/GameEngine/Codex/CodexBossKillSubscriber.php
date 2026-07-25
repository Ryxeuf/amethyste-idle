<?php

namespace App\GameEngine\Codex;

use App\Entity\Game\CodexEntry;
use App\Event\Fight\MobDeadEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Debloque les entrees de Codex `boss_kill` quand un monstre (boss) cible est
 * vaincu (NAR-05). Le deblocage vise chaque joueur present au combat.
 */
class CodexBossKillSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly CodexUnlockService $codexUnlockService,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            MobDeadEvent::NAME => 'onMobDead',
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

        $monsterSlug = $mob->getMonster()->getSlug();

        foreach ($fight->getPlayers() as $player) {
            $this->codexUnlockService->unlockByTrigger($player, CodexEntry::UNLOCK_BOSS_KILL, $monsterSlug);
        }
    }
}
