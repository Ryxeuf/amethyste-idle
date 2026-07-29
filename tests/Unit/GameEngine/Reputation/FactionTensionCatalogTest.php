<?php

namespace App\Tests\Unit\GameEngine\Reputation;

use App\Enum\ReputationTier;
use App\GameEngine\Reputation\FactionTensionCatalog;
use App\GameEngine\Reputation\FactionTensionDefinitionException;
use PHPUnit\Framework\TestCase;

/**
 * L'axe doctrinal, et l'arithmetique de la decote (FAC-01).
 *
 * GAME_WORLD § 6.4 a : « progresser chez l'un fait decroitre chez son oppose
 * **au-dela du palier Ami** ». Tout le sujet tient dans « au-dela » : une decote
 * qui mordrait des le premier point ferait des cinq maisons cinq impasses, et
 * un joueur n'aurait jamais les moyens de decouvrir celle qui lui convient.
 */
class FactionTensionCatalogTest extends TestCase
{
    private function catalog(): FactionTensionCatalog
    {
        return new FactionTensionCatalog(\dirname(__DIR__, 4));
    }

    // =====================================================================
    // Le fichier livre
    // =====================================================================

    /**
     * Les deux paires du canon, et la Guilde des Marchands hors tension.
     */
    public function testTheShippedAxisMatchesTheCanon(): void
    {
        $catalog = $this->catalog();

        self::assertSame('mages', $catalog->opponentOf('fonderie'));
        self::assertSame('fonderie', $catalog->opponentOf('mages'));
        self::assertSame('ombres', $catalog->opponentOf('chevaliers'));
        self::assertSame('chevaliers', $catalog->opponentOf('ombres'));

        self::assertNull(
            $catalog->opponentOf('marchands'),
            'La Guilde des Marchands vend aux deux camps : lui donner un oppose lui retirerait son identite.',
        );
        self::assertContains('marchands', $catalog->neutralFactions());
    }

    /**
     * La paire de la Fonderie est declaree avant que la faction existe.
     *
     * Elle arrive avec FAC-04. Attendre pour declarer la tension obligerait a se
     * souvenir de revenir ici le jour venu — et l'oubli ne produirait aucune
     * erreur, seulement une faction qui monte sans jamais rien couter.
     */
    public function testAPairMayNameAFactionThatDoesNotExistYet(): void
    {
        self::assertSame('Extraire / Preserver', $this->catalog()->axisOf('fonderie'));
    }

    public function testTheOffsetFloorMirrorsTheTierWhereItBegins(): void
    {
        $catalog = $this->catalog();

        self::assertSame(
            -$catalog->beyondTier()->threshold(),
            $catalog->offsetFloor(),
            'On ne peut pas renoncer a plus que ce qu\'on aurait pu donner.',
        );
    }

    // =====================================================================
    // L'arithmetique de la decote
    // =====================================================================

    /**
     * En deca du palier, la tension ne mord pas.
     *
     * C'est la moitie de la regle, et celle qu'on casse sans s'en apercevoir :
     * un gain de 100 points a 200 de reputation ne coute rien nulle part.
     */
    public function testAGainBelowTheTierCostsNothing(): void
    {
        self::assertSame(0, $this->catalog()->offsetFor(200, 100));
    }

    /**
     * Un gain qui franchit le palier ne fait payer que la part au-dela.
     *
     * Le defaut naturel serait de facturer le gain entier des que l'arrivee
     * depasse le seuil : un joueur a 1 990 points perdrait pour un gain qui le
     * laisse Ami tout juste. Ici, sur un gain de 100 qui mene a 2 090, seuls 90
     * points comptent — et la moitie, soit 45, est retiree a l'oppose.
     */
    public function testOnlyThePartBeyondTheTierIsCharged(): void
    {
        $catalog = $this->catalog();

        self::assertSame(
            (int) floor(90 * $catalog->offsetPercent() / 100),
            $catalog->offsetFor(1990, 100),
        );
    }

    /**
     * Au-dela du palier, tout le gain compte.
     */
    public function testBeyondTheTierTheWholeGainIsCharged(): void
    {
        $catalog = $this->catalog();

        self::assertSame(
            (int) floor(200 * $catalog->offsetPercent() / 100),
            $catalog->offsetFor(6000, 200),
        );
    }

    /**
     * Un gain nul ou negatif ne retire rien.
     *
     * « La reputation ne descend que par le geste oppose » : un non-geste n'est
     * pas un geste, et une decote declenchee par un zero ferait descendre les
     * deux factions a chaque appel neutre du moteur.
     */
    public function testANonGainCostsNothing(): void
    {
        self::assertSame(0, $this->catalog()->offsetFor(9000, 0));
        self::assertSame(0, $this->catalog()->offsetFor(9000, -500));
    }

    /**
     * Rien, dans le catalogue, ne porte de duree.
     *
     * Principe du plan Retention : l'absence n'est jamais punie. Le jour ou une
     * decroissance par inactivite apparaitrait, elle passerait forcement par un
     * champ de temps ici — et ce test le verrait avant les joueurs.
     */
    public function testNothingInTheCatalogueDecaysWithTime(): void
    {
        $yaml = (string) file_get_contents(\dirname(__DIR__, 4) . '/config/game/factions.yaml');

        foreach (['decay', 'per_day', 'per_week', 'inactivity', 'expires'] as $forbidden) {
            self::assertStringNotContainsString(
                $forbidden . ':',
                $yaml,
                sprintf('Le catalogue declare "%s" : la reputation ne descend que par le geste oppose.', $forbidden),
            );
        }
    }

    // =====================================================================
    // Ce que le loader refuse
    // =====================================================================

    public function testAFactionCannotSitInTwoPairs(): void
    {
        $this->expectException(FactionTensionDefinitionException::class);

        $this->catalog()->normalize([
            'tension_pairs' => [
                ['left' => 'a', 'right' => 'b', 'axis' => 'X'],
                ['left' => 'b', 'right' => 'c', 'axis' => 'Y'],
            ],
            'tension' => ['beyond_tier' => 'ami', 'percent' => 50],
            'patronage' => ['required_tier' => 'ami'],
        ]);
    }

    public function testAFactionCannotBeBothNeutralAndOpposed(): void
    {
        $this->expectException(FactionTensionDefinitionException::class);

        $this->catalog()->normalize([
            'tension_pairs' => [['left' => 'a', 'right' => 'b', 'axis' => 'X']],
            'neutral' => ['a'],
            'tension' => ['beyond_tier' => 'ami', 'percent' => 50],
            'patronage' => ['required_tier' => 'ami'],
        ]);
    }

    public function testAnUnknownTierIsRefused(): void
    {
        $this->expectException(FactionTensionDefinitionException::class);

        $this->catalog()->normalize([
            'tension_pairs' => [],
            'tension' => ['beyond_tier' => 'camarade', 'percent' => 50],
            'patronage' => ['required_tier' => 'ami'],
        ]);
    }

    public function testAPercentOutsideTheScaleIsRefused(): void
    {
        $this->expectException(FactionTensionDefinitionException::class);

        $this->catalog()->normalize([
            'tension_pairs' => [],
            'tension' => ['beyond_tier' => 'ami', 'percent' => 140],
            'patronage' => ['required_tier' => 'ami'],
        ]);
    }

    public function testAFactionCannotOpposeItself(): void
    {
        $this->expectException(FactionTensionDefinitionException::class);

        $this->catalog()->normalize([
            'tension_pairs' => [['left' => 'a', 'right' => 'a', 'axis' => 'X']],
            'tension' => ['beyond_tier' => 'ami', 'percent' => 50],
            'patronage' => ['required_tier' => 'ami'],
        ]);
    }

    public function testTheShippedTiersAreRealTiers(): void
    {
        $catalog = $this->catalog();

        self::assertContains($catalog->beyondTier(), ReputationTier::cases());
        self::assertContains($catalog->patronageTier(), ReputationTier::cases());
    }
}
