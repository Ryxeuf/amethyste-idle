<?php

namespace App\Tests\Unit\GameEngine\Bestiary;

use App\Enum\MonsterRank;
use App\GameEngine\Bestiary\MonsterStatTemplate;
use PHPUnit\Framework\TestCase;

/**
 * BES-02 — les stats se derivent du gabarit tier × rang (GAME_BESTIARY §3).
 *
 * Le contrat qui compte : la faille du milieu venait d'un saut de vie de ×4
 * entre deux blocs — la grille interdit qu'il revienne.
 */
class MonsterStatTemplateTest extends TestCase
{
    /**
     * La grille de depart est celle du cadrage : T1 30/90/250 jusqu'a
     * T4 300/850/2400.
     */
    public function testLifeGridIsTheActedOne(): void
    {
        $this->assertSame(30, MonsterStatTemplate::lifeFor(1, MonsterRank::Common));
        $this->assertSame(90, MonsterStatTemplate::lifeFor(1, MonsterRank::Elite));
        $this->assertSame(250, MonsterStatTemplate::lifeFor(1, MonsterRank::Boss));
        $this->assertSame(300, MonsterStatTemplate::lifeFor(4, MonsterRank::Common));
        $this->assertSame(850, MonsterStatTemplate::lifeFor(4, MonsterRank::Elite));
        $this->assertSame(2400, MonsterStatTemplate::lifeFor(4, MonsterRank::Boss));
    }

    /**
     * Aucun saut de ×4 entre deux paliers consecutifs, pour aucun rang —
     * c'est l'invariant qui referme la faille du milieu.
     */
    public function testNoTierJumpReachesFourfold(): void
    {
        foreach (MonsterRank::cases() as $rank) {
            for ($tier = 1; $tier < 4; ++$tier) {
                $ratio = MonsterStatTemplate::lifeFor($tier + 1, $rank) / MonsterStatTemplate::lifeFor($tier, $rank);

                $this->assertLessThan(
                    4,
                    $ratio,
                    sprintf('Saut de vie ×%.1f entre T%d et T%d (%s) : la faille du milieu revient.', $ratio, $tier, $tier + 1, $rank->value),
                );
            }
        }
    }

    /**
     * La progression par rang reste elle aussi sous le ×4 : l'elite fait
     * hesiter, elle ne change pas de monde.
     */
    public function testNoRankJumpReachesFourfold(): void
    {
        for ($tier = 1; $tier <= 4; ++$tier) {
            $eliteRatio = MonsterStatTemplate::lifeFor($tier, MonsterRank::Elite) / MonsterStatTemplate::lifeFor($tier, MonsterRank::Common);
            $bossRatio = MonsterStatTemplate::lifeFor($tier, MonsterRank::Boss) / MonsterStatTemplate::lifeFor($tier, MonsterRank::Elite);

            $this->assertLessThan(4, $eliteRatio, sprintf('T%d : saut commun → elite trop grand.', $tier));
            $this->assertLessThan(4, $bossRatio, sprintf('T%d : saut elite → boss trop grand.', $tier));
        }
    }

    /**
     * Precision : 70 + 5 par palier, +5 pour l'elite et le boss.
     */
    public function testHitFormula(): void
    {
        $this->assertSame(75, MonsterStatTemplate::hitFor(1, MonsterRank::Common));
        $this->assertSame(90, MonsterStatTemplate::hitFor(4, MonsterRank::Common));
        $this->assertSame(80, MonsterStatTemplate::hitFor(1, MonsterRank::Elite));
        $this->assertSame(95, MonsterStatTemplate::hitFor(4, MonsterRank::Boss));
    }

    /**
     * Le gabarit borne ses entrees : un palier hors monde est ramene dans la
     * grille plutot que de lever au milieu d'un chargement de fixtures.
     */
    public function testTierIsClamped(): void
    {
        $this->assertSame(MonsterStatTemplate::lifeFor(4, MonsterRank::Common), MonsterStatTemplate::lifeFor(9, MonsterRank::Common));
        $this->assertSame(MonsterStatTemplate::lifeFor(0, MonsterRank::Common), MonsterStatTemplate::lifeFor(-1, MonsterRank::Common));
    }
}
