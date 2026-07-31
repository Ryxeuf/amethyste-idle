<?php

namespace App\Tests\Unit\GameEngine\Player;

use App\Entity\Game\CodexEntry;
use App\GameEngine\Player\HubWeekRecap;
use App\GameEngine\Player\HubWeekRecapLine;
use PHPUnit\Framework\TestCase;

/**
 * Le recap du lundi tient ses regles par sa forme (RET-09).
 *
 * GAME_DASHBOARD § 4 en pose deux qu'un document ne tient jamais longtemps :
 * le recap **constate** (il ne renvoie nulle part, la semaine close n'ayant pas
 * d'ecran) et il **ne fait pas reproche** (« sur le ton du constat, jamais du
 * reproche »). Les deux sont ici des proprietes des types :
 *
 * - `HubWeekRecapLine` n'a pas de route — un `HubWeekRow` en a une, et c'est
 *   toute la difference entre ce qui s'ouvre et ce qui s'est ferme ;
 * - le vocabulaire de ton se limite a *neutre* et *gain*. Il n'y a pas de
 *   constante pour le manque, donc pas moyen d'en teinter une ligne sans
 *   ajouter le mot au type.
 */
class HubWeekRecapTest extends TestCase
{
    private function line(string $key = 'attendance'): HubWeekRecapLine
    {
        return new HubWeekRecapLine($key);
    }

    /**
     * Le plafond de quatre depots est applique, pas espere.
     */
    public function testTheRecapNeverExceedsFourLines(): void
    {
        $recap = new HubWeekRecap(array_fill(0, 9, $this->line()));

        self::assertCount(HubWeekRecap::MAX_LINES, $recap->lines);
        self::assertSame(4, HubWeekRecap::MAX_LINES);
    }

    /**
     * Tronquer garde l'ordre canonique : c'est la derniere ligne qui tombe.
     */
    public function testTruncationDropsTheLastLineNotAnyLine(): void
    {
        $keys = ['attendance', 'commission_delivered', 'guild_challenges', 'settlement_work', 'de_trop'];
        $recap = new HubWeekRecap(array_map(fn (string $k): HubWeekRecapLine => $this->line($k), $keys));

        self::assertSame(\array_slice($keys, 0, 4), array_column($recap->lines, 'key'));
    }

    /**
     * Une semaine muette ne s'annonce pas.
     *
     * Un encart « rien cette semaine » serait exactement le reproche que le
     * cadrage refuse : le silence se lit tres bien comme un silence.
     */
    public function testASilentWeekHasNothingToOpen(): void
    {
        self::assertTrue((new HubWeekRecap([]))->isEmpty());
    }

    /**
     * La chronique suffit a ouvrir le recap.
     *
     * Une semaine ou le joueur n'a rien depose mais ou sa ville a change reste
     * une semaine qui merite d'etre racontee — c'est meme le cas ou la
     * chronique compte le plus.
     */
    public function testTheChronicleAloneIsWorthOpeningTheRecap(): void
    {
        $recap = new HubWeekRecap([], '2026-W30', new CodexEntry());

        self::assertFalse($recap->isEmpty());
    }

    /**
     * Le recap ne renvoie nulle part : la ligne n'a pas de destination.
     *
     * C'est verifie par reflexion parce que c'est la seule facon de tenir une
     * regle sur ce qu'un type **ne porte pas**.
     */
    public function testARecapLineCarriesNoDestination(): void
    {
        $properties = array_map(
            static fn (\ReflectionProperty $p): string => strtolower($p->getName()),
            (new \ReflectionClass(HubWeekRecapLine::class))->getProperties(),
        );

        self::assertNotEmpty($properties, 'Aucune propriete lue : le test ne verifie rien.');

        foreach (['route', 'routeparams', 'url', 'path', 'action', 'method'] as $forbidden) {
            self::assertNotContains(
                $forbidden,
                $properties,
                'Le recap constate ; la semaine close n\'a pas d\'ecran ou aller.',
            );
        }
    }

    /**
     * Le type n'a pas de mot pour le reproche.
     *
     * L'invariant remonte a RET-04 : on recompense la presence, on ne
     * sanctionne jamais l'absence. Une constante `TONE_MISSED` suffirait a
     * rouvrir la porte, et ce test la referme au niveau ou elle s'ouvrirait.
     */
    public function testTheRecapHasNoVocabularyForBlame(): void
    {
        $tones = (new \ReflectionClass(HubWeekRecapLine::class))->getConstants();

        self::assertSame(
            ['TONE_NEUTRAL' => 'neutral', 'TONE_GAIN' => 'gain'],
            $tones,
            'Un ton de plus dans ce type est un jugement de plus sur la semaine du joueur.',
        );
    }
}
