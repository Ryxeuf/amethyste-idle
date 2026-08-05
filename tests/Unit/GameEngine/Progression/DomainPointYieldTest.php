<?php

namespace App\Tests\Unit\GameEngine\Progression;

use App\GameEngine\Balance\DailyAnchor;
use App\GameEngine\Progression\DomainPointYield;
use App\GameEngine\Progression\SkillCostScale;
use App\GameEngine\Zone\ActionEnergyManager;
use App\GameEngine\Zone\HuntService;
use PHPUnit\Framework\TestCase;

/**
 * Le gain de points, et le calendrier qu'il doit tenir (ARC-06a).
 *
 * GAME_ARCHETYPES § 6.2 : *le taux de gain se derive du calendrier, pas
 * l'inverse*. Le canon annonce ~7 semaines de pratique soutenue sur de la faune
 * de palier 2 pour un arbre complet — ce test le **verifie** sur les curseurs
 * reellement livres, au lieu de le croire.
 */
class DomainPointYieldTest extends TestCase
{
    /**
     * Le nombre de rencontres qu'une journee autorise, sur les vrais curseurs.
     */
    private function encountersPerDay(): int
    {
        return DailyAnchor::encountersPerDay(
            DailyAnchor::dailyEnergyBudget(ActionEnergyManager::DEFAULT_REGEN_SECONDS),
            HuntService::DEFAULT_COST,
        );
    }

    /**
     * La table du canon, au quart de point pres.
     *
     * 0,25 · 0,5 · 1 · 2 — et le pas de deux entre deux paliers est ce qui
     * porte toute la regle : *on ne monte pas un arbre en tapant des rats*.
     */
    public function testTheTableFollowsTheCanonAndDoublesEachTier(): void
    {
        self::assertSame(1, DomainPointYield::quartersFor(1));
        self::assertSame(2, DomainPointYield::quartersFor(2));
        self::assertSame(4, DomainPointYield::quartersFor(3));
        self::assertSame(8, DomainPointYield::quartersFor(4));

        foreach ([2, 3, 4] as $tier) {
            self::assertSame(
                2 * DomainPointYield::quartersFor($tier - 1),
                DomainPointYield::quartersFor($tier),
                sprintf('Le palier %d doit valoir le double du precedent.', $tier),
            );
        }
    }

    /**
     * Le mannequin ne fait progresser aucun arbre.
     *
     * T0 est le palier des mannequins d'entrainement d'ONB (GAME_BESTIARY) :
     * le premier n'attaque pas, le second ne tue pas. Ils enseignent le combat,
     * ils ne le remplacent pas — et un palier inconnu ne rend pas un gain par
     * defaut, il rend zero.
     */
    public function testTrainingDummiesYieldNothing(): void
    {
        self::assertSame(0, DomainPointYield::quartersFor(0));
        self::assertSame(0, DomainPointYield::quartersFor(9));
        self::assertSame(0, DomainPointYield::daysToCompleteTree($this->encountersPerDay(), 0));
    }

    /**
     * Le calendrier du canon tient sur les curseurs livres.
     *
     * *16 combats par jour sur de la faune T2 valent 8 points par jour, soit
     * les 390 points en ~7 semaines de pratique soutenue.* Verifie de bout en
     * bout : la journee vient de `DailyAnchor` (energie et cout d'une chasse
     * reels), le total vient de `SkillCostScale`, et la table du gain fait le
     * pont. **Aucun des trois n'est recopie ici** — deplacer l'un des curseurs
     * deplace le calendrier, et ce test le dira.
     */
    public function testTheCanonCalendarHoldsOnTheDeliveredCursors(): void
    {
        $encounters = $this->encountersPerDay();
        self::assertSame(16, $encounters);

        // 8 points par jour, exprimes en quarts.
        self::assertSame(8 * DomainPointYield::QUARTERS_PER_POINT, DomainPointYield::quartersPerDay($encounters, 2));

        $days = DomainPointYield::daysToCompleteTree($encounters, 2);
        self::assertSame(49, $days);
        self::assertSame(7, intdiv($days, 7), 'Le canon annonce ~7 semaines de pratique soutenue.');
    }

    /**
     * On ne monte pas un arbre en tapant des rats — chiffre.
     *
     * A budget d'energie identique, chasser un palier en dessous **double** le
     * temps qu'il faut pour finir l'arbre. C'est la propriete que le § 6.2
     * cherche : la progression pousse vers le contenu de son palier, sans
     * qu'aucune regle n'interdise quoi que ce soit.
     */
    public function testFarmingTrivialContentTakesTwiceAsLong(): void
    {
        $encounters = $this->encountersPerDay();

        self::assertSame(98, DomainPointYield::daysToCompleteTree($encounters, 1));
        self::assertSame(
            2 * DomainPointYield::daysToCompleteTree($encounters, 2),
            DomainPointYield::daysToCompleteTree($encounters, 1),
        );
    }

    /**
     * Le raccourci lit les memes curseurs que le chemin long.
     *
     * Deux facons de poser la question ne doivent pas rendre deux calendriers.
     */
    public function testTheShorthandAgreesWithTheLongPath(): void
    {
        foreach ([1, 2, 3, 4] as $tier) {
            self::assertSame(
                DomainPointYield::daysToCompleteTree($this->encountersPerDay(), $tier),
                DomainPointYield::daysToCompleteTreeAtTier(
                    $tier,
                    HuntService::DEFAULT_COST,
                    ActionEnergyManager::DEFAULT_REGEN_SECONDS,
                ),
            );
        }
    }

    /**
     * Une journee sans rencontre ne fait pas tomber d'arbre.
     */
    public function testADayWithoutEncountersYieldsNothing(): void
    {
        self::assertSame(0, DomainPointYield::quartersPerDay(0, 2));
        self::assertSame(0, DomainPointYield::daysToCompleteTree(0, 2));
        self::assertSame(0, DomainPointYield::quartersPerDay(-5, 2));
    }

    /**
     * Le quart est bien la plus petite unite de la table.
     *
     * Si le canon descendait un jour sous 0,25, le denominateur devrait suivre
     * — sans quoi le compteur perdrait des restes et un joueur de bas palier
     * gagnerait zero, arrondi apres arrondi.
     */
    public function testTheQuarterIsTheSmallestUnitTheTableNames(): void
    {
        self::assertSame(4, DomainPointYield::QUARTERS_PER_POINT);
        self::assertSame(1, min(DomainPointYield::QUARTERS_BY_TIER));
        self::assertGreaterThan(0, SkillCostScale::COMPLETE_TREE);
    }
}
