<?php

namespace App\Tests\Unit\GameEngine\Player;

use App\GameEngine\Player\HubWeek;
use App\GameEngine\Player\HubWeekRow;
use PHPUnit\Framework\TestCase;

/**
 * Le bloc « La semaine » tient ses regles par sa forme (RET-08).
 *
 * GAME_DASHBOARD § 3 pose deux regles que rien, dans un projet, ne tient
 * longtemps par convention : **cinq lignes au maximum**, et **le hub lit, il ne
 * fait pas**. Les deux sont ici des proprietes du type plutot que des consignes
 * dans un document :
 *
 * - le plafond est applique a la construction, pas verifie a la relecture ;
 * - `HubWeekRow` **n'a pas** de quoi poster : ni action, ni methode HTTP, ni
 *   jeton, ni charge utile. Le dernier test le verifie par reflexion, ce qui
 *   est la seule facon de tenir une regle sur ce qu'un type *ne porte pas*.
 */
class HubWeekTest extends TestCase
{
    private function row(string $key = 'commission'): HubWeekRow
    {
        return new HubWeekRow($key, 'app_game_zone');
    }

    /**
     * Le plafond de cinq lignes est applique, pas espere.
     */
    public function testTheBlockNeverExceedsFiveRows(): void
    {
        $week = new HubWeek(array_fill(0, 9, $this->row()));

        self::assertCount(HubWeek::MAX_ROWS, $week->rows);
        self::assertSame(5, HubWeek::MAX_ROWS);
    }

    /**
     * Tronquer garde l'ordre canonique : c'est la derniere ligne qui tombe.
     *
     * L'ordre du § 3 va du plus personnel au plus collectif. Si une ligne doit
     * disparaitre, ce doit etre la moins urgente pour ce joueur-la, pas une
     * ligne tiree au hasard par un tri instable.
     */
    public function testTruncationDropsTheLastRowNotAnyRow(): void
    {
        $keys = ['commission', 'guild_challenges', 'guild_order', 'settlement_work', 'attendance', 'de_trop'];
        $week = new HubWeek(array_map(fn (string $k): HubWeekRow => $this->row($k), $keys));

        self::assertSame(\array_slice($keys, 0, 5), array_column($week->rows, 'key'));
    }

    /**
     * Un bloc sans ligne ne se rend pas du tout.
     *
     * Un joueur sans guilde, sans commission et sans foyer ne doit pas lire
     * cinq lignes barrees : la ligne absente suffit a dire qu'il n'y a rien la.
     */
    public function testAnEmptyBlockKnowsItIsEmpty(): void
    {
        self::assertTrue((new HubWeek([]))->isEmpty());
        self::assertFalse((new HubWeek([$this->row()]))->isEmpty());
    }

    /**
     * Une jauge n'existe que si elle est lisible.
     */
    public function testAGaugeNeedsBothEndsAndAPositiveTarget(): void
    {
        self::assertFalse($this->row()->hasGauge());
        self::assertFalse((new HubWeekRow('k', 'r', current: 3))->hasGauge());
        self::assertFalse((new HubWeekRow('k', 'r', current: 3, target: 0))->hasGauge());
        self::assertTrue((new HubWeekRow('k', 'r', current: 3, target: 10))->hasGauge());
    }

    /**
     * Le pourcentage est borne a 100.
     *
     * Une commission livrable peut depasser sa cible — douze truites pour dix
     * demandees — et une barre a 120 % se lit comme un bug, pas comme une
     * reussite.
     */
    public function testThePercentageIsCappedAtOneHundred(): void
    {
        self::assertSame(0, $this->row()->percent());
        self::assertSame(30, (new HubWeekRow('k', 'r', current: 3, target: 10))->percent());
        self::assertSame(100, (new HubWeekRow('k', 'r', current: 12, target: 10))->percent());
    }

    /**
     * Le hub ne dit jamais l'Affleurement de la semaine.
     *
     * GAME_DASHBOARD § 6, qui reaffirme la decision de RET-06 : l'Affleurement
     * n'est annonce nulle part. C'est **l'information des prospecteurs**, et
     * elle se monnaye entre joueurs — l'afficher gratuitement au tableau de
     * bord effacerait d'un trait le seul savoir qui ait une valeur marchande
     * dans le jeu.
     *
     * L'interdit se verifie sur le gabarit et sur le digest : la tentation de
     * « juste une ligne de plus » viendra de l'un ou de l'autre.
     */
    public function testTheHubNeverNamesTheWeeklyOutcrop(): void
    {
        $root = \dirname(__DIR__, 4);

        foreach (['templates/game/index.html.twig', 'src/GameEngine/Player/PlayerHubDigest.php'] as $file) {
            $source = file_get_contents($root . '/' . $file);
            self::assertIsString($source, sprintf('%s est illisible.', $file));

            foreach (['outcrop', 'Outcrop', 'affleurement', 'Affleurement'] as $needle) {
                self::assertStringNotContainsString(
                    $needle,
                    $source,
                    sprintf('%s mentionne l\'Affleurement. C\'est l\'information des prospecteurs : elle se monnaye, elle ne s\'affiche pas.', $file),
                );
            }
        }
    }

    /**
     * Une ligne du hub ne peut **pas** devenir un bouton.
     *
     * La regle « le hub lit, il ne fait pas » se verifie sur ce que le type
     * *n'a pas* : aucune propriete par laquelle une action pourrait passer. Un
     * futur jalon qui voudrait poster depuis le hub devra ajouter la propriete —
     * et ce test tombera, ce qui est exactement la conversation voulue.
     */
    public function testARowCarriesNothingToActWith(): void
    {
        $forbidden = ['action', 'method', 'csrf', 'token', 'payload', 'form', 'submit'];

        $properties = array_map(
            static fn (\ReflectionProperty $p): string => strtolower($p->getName()),
            (new \ReflectionClass(HubWeekRow::class))->getProperties(),
        );

        self::assertNotEmpty($properties, 'Aucune propriete lue : le test ne verifie rien.');

        foreach ($forbidden as $name) {
            self::assertNotContains($name, $properties, sprintf('`HubWeekRow` porte « %s » : le hub cesserait de lire pour agir.', $name));
        }
    }
}
