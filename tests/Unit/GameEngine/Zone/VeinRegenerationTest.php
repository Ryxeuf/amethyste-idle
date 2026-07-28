<?php

namespace App\Tests\Unit\GameEngine\Zone;

use App\GameEngine\Zone\GatherService;
use PHPUnit\Framework\TestCase;

/**
 * ZON-37 — la repousse d'un filon est un **debit**, pas une phase.
 *
 * > « La regeneration n'est pas une phase, c'est un debit permanent […] chaque
 * > filon rend `R = capacity x 3600 / respawn_seconds` unites par heure, **en
 * > continu**, et `capacity` n'est qu'un tampon. »
 * > — [GAME_WORLD.md](../../../../docs/GAME_WORLD.md) §3.5
 *
 * Le moteur faisait exactement l'inverse : un filon ne repoussait que s'il
 * tombait a zero, attendait le delai plein, puis revenait plein d'un bloc. Une
 * entame partielle n'etait **jamais** reconstituee. Tout le calibrage de
 * BALANCE §22 — « combien de recolteurs un filon soutient » — decrivait donc un
 * systeme qui n'existait pas, et le recalibrage a venir aurait transforme une
 * tension douce en mur.
 */
class VeinRegenerationTest extends TestCase
{
    private function at(string $time): \DateTimeImmutable
    {
        return new \DateTimeImmutable($time);
    }

    /**
     * Le cas qui n'existait pas : une entame partielle se reconstitue.
     */
    public function testAPartiallyDrainedVeinRefillsOverTime(): void
    {
        // 20 unites pour 900 s : une unite toutes les 45 s.
        $result = GatherService::regenerate(12, 20, 900, $this->at('12:00:00'), $this->at('12:03:00'));

        self::assertSame(16, $result['stock'], '180 s ecoulees = 4 unites.');
    }

    public function testRegenerationStopsAtCapacity(): void
    {
        $result = GatherService::regenerate(18, 20, 900, $this->at('12:00:00'), $this->at('20:00:00'));

        self::assertSame(20, $result['stock']);
    }

    /**
     * L'invariant de rythme (BALANCE § 22.4) : remplir un filon vide prend
     * exactement sa periode declaree — quelle que soit sa taille. C'est ce qui
     * permet au facteur de monde de grossir le tampon sans accelerer le monde.
     */
    public function testFullRefillAlwaysTakesTheDeclaredRespawnPeriod(): void
    {
        foreach ([20, 40, 72] as $capacity) {
            $result = GatherService::regenerate(0, $capacity, 900, $this->at('12:00:00'), $this->at('12:15:00'));

            self::assertSame(
                $capacity,
                $result['stock'],
                sprintf('Un filon de %d unites se remplit en 900 s, comme les autres.', $capacity),
            );
        }
    }

    /**
     * Le reliquat se reporte : sans cela, un filon souvent consulte perdrait sa
     * fraction de repousse a chaque lecture et ne remonterait jamais.
     */
    public function testTheLeftoverFractionCarriesOverBetweenReads(): void
    {
        // 45 s par unite. Trois lectures a 30 s d'intervalle = 90 s = 2 unites.
        $anchor = $this->at('12:00:00');
        $stock = 0;

        foreach (['12:00:30', '12:01:00', '12:01:30'] as $time) {
            $result = GatherService::regenerate($stock, 20, 900, $anchor, $this->at($time));
            $stock = $result['stock'];
            $anchor = $result['anchor'];
        }

        self::assertSame(2, $stock, 'Trois lectures rapprochees valent le meme total qu\'une seule.');
    }

    public function testNothingIsOwedBelowASingleUnit(): void
    {
        $result = GatherService::regenerate(5, 20, 900, $this->at('12:00:00'), $this->at('12:00:30'));

        self::assertSame(5, $result['stock'], '30 s pour une unite a 45 s : rien encore.');
        self::assertEquals($this->at('12:00:00'), $result['anchor'], 'L\'ancre ne bouge pas tant que rien n\'est du.');
    }

    /**
     * Un filon sans ancre est repute a jour : on ne lui doit rien.
     */
    public function testAVeinWithoutAnAnchorIsConsideredUpToDate(): void
    {
        $result = GatherService::regenerate(5, 20, 900, null, $this->at('20:00:00'));

        self::assertSame(5, $result['stock']);
    }

    public function testAFullVeinIsLeftAlone(): void
    {
        $result = GatherService::regenerate(20, 20, 900, $this->at('12:00:00'), $this->at('20:00:00'));

        self::assertSame(20, $result['stock']);
    }

    public function testStockIsNeverReportedAboveCapacity(): void
    {
        // Le facteur de monde peut retrecir un filon sous son stock courant.
        $result = GatherService::regenerate(40, 20, 900, $this->at('12:00:00'), $this->at('12:15:00'));

        self::assertSame(20, $result['stock']);
    }

    public function testAZeroRespawnNeverDividesByZero(): void
    {
        $result = GatherService::regenerate(5, 20, 0, $this->at('12:00:00'), $this->at('20:00:00'));

        self::assertSame(5, $result['stock']);
    }

    /**
     * Une horloge qui recule (reprise de sauvegarde, NTP) ne doit rien casser.
     */
    public function testATimeGoingBackwardsGrantsNothing(): void
    {
        $result = GatherService::regenerate(5, 20, 900, $this->at('12:00:00'), $this->at('11:00:00'));

        self::assertSame(5, $result['stock']);
    }
}
