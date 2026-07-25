<?php

declare(strict_types=1);

namespace App\Tests\Unit\Entity\App;

use App\Entity\App\GameEvent;
use App\Entity\App\InfluenceSeason;
use App\Entity\App\Mob;
use PHPUnit\Framework\TestCase;

/**
 * Couvre {@see Mob::isSeasonBoss()} (NAR-10) : un world boss rattache au beat
 * de climax d'un arc de saison.
 */
final class MobSeasonBossTest extends TestCase
{
    private function seasonBeatEvent(): GameEvent
    {
        $event = new GameEvent();
        $event->setSeason(new InfluenceSeason());
        $event->setBeat(GameEvent::BEAT_CLIMAX);

        return $event;
    }

    public function testPlainMobIsNotSeasonBoss(): void
    {
        self::assertFalse((new Mob())->isSeasonBoss());
    }

    public function testWorldBossWithoutSeasonEventIsNotSeasonBoss(): void
    {
        $mob = new Mob();
        $mob->setIsWorldBoss(true);
        // Pas de GameEvent -> pas un boss de saison.
        self::assertFalse($mob->isSeasonBoss());

        // GameEvent sans saison -> pas un boss de saison.
        $mob->setGameEvent(new GameEvent());
        self::assertFalse($mob->isSeasonBoss());
    }

    public function testWorldBossOnSeasonBeatIsSeasonBoss(): void
    {
        $mob = new Mob();
        $mob->setIsWorldBoss(true);
        $mob->setGameEvent($this->seasonBeatEvent());

        self::assertTrue($mob->isSeasonBoss());
    }

    public function testSeasonEventButNotWorldBossIsNotSeasonBoss(): void
    {
        $mob = new Mob();
        // Rattache a un beat de saison mais pas marque world boss.
        $mob->setGameEvent($this->seasonBeatEvent());

        self::assertFalse($mob->isSeasonBoss());
    }
}
