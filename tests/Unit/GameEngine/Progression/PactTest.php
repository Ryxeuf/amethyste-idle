<?php

namespace App\Tests\Unit\GameEngine\Progression;

use App\Entity\Game\Skill;
use App\Enum\CombatLever;
use App\GameEngine\Progression\CombatLeverDefinitionException;
use App\GameEngine\Progression\CombatLeverDefinitionLoader;
use App\GameEngine\Progression\CombatLeverScale;
use App\GameEngine\Progression\EquipmentPortCatalog;
use App\GameEngine\Progression\PactGrant;
use App\GameEngine\Progression\SkillLeverPresenter;
use App\GameEngine\Progression\SkillLeverReader;
use PHPUnit\Framework\TestCase;

/**
 * Le pacte : un malus rend du budget (ARC-15).
 *
 * GAME_ARCHETYPES § 6.5. **La seule mecanique du document qui rende un
 * personnage mesurablement plus faible quelque part** — sans elle, tous les
 * builds sont des additions, et « un archetype memorable est mauvais a quelque
 * chose, et il l'a choisi » reste une phrase.
 *
 * C'est aussi la mecanique la plus facile a degenerer du canon, d'ou six
 * regles serrees. Celles qui portent sur le nœud sont ici ; celles qui portent
 * sur l'arbre sont dans `PactRule`.
 */
class PactTest extends TestCase
{
    private function reader(): SkillLeverReader
    {
        $root = \dirname(__DIR__, 4);

        return new SkillLeverReader(
            new CombatLeverScale(new CombatLeverDefinitionLoader($root)),
            new EquipmentPortCatalog($root),
        );
    }

    /**
     * Un pacte se lit, et son nœud vaut ce que son cran annonce.
     *
     * Mineur : 5 pb rendus, le nœud en vaut 14. Majeur : 10 rendus, le nœud en
     * vaut 19. C'est la grille du § 6.5, et elle a deux crans parce que le cran
     * decide **quels leviers peuvent l'accueillir**.
     */
    public function testTheTwoCransAreReadAndWeighWhatTheyAnnounce(): void
    {
        $minor = $this->reader()->read([['lever' => 'power', 'points' => 14, 'pact' => ['lever' => 'life', 'points' => 5]]])[0];
        $major = $this->reader()->read([['lever' => 'power', 'points' => 19, 'pact' => ['lever' => 'life', 'points' => 10]]])[0];

        self::assertTrue($minor->isPact());
        self::assertTrue($major->isPact());
        self::assertFalse($minor->pact?->isMajor());
        self::assertTrue($major->pact?->isMajor());
    }

    /**
     * **Le pacte ne change pas ce qu'un arbre pese, il change sa forme.**.
     *
     * L'invariant central : un nœud a 19 pb dont 10 sont rendus pese **9** —
     * la valeur d'un nœud de palier 3 ordinaire. Compter le brut ferait
     * depasser ses 50 pb a l'arbre sans qu'il ait rien gagne.
     */
    public function testAPactChangesTheShapeOfATreeNeverItsWeight(): void
    {
        $reader = $this->reader();

        $plain = $reader->read([['lever' => 'power', 'points' => 9]])[0];
        $minor = $reader->read([['lever' => 'power', 'points' => 14, 'pact' => ['lever' => 'life', 'points' => 5]]])[0];
        $major = $reader->read([['lever' => 'power', 'points' => 19, 'pact' => ['lever' => 'life', 'points' => 10]]])[0];

        self::assertSame(9, $plain->netBudgetPoints());
        self::assertSame(9, $minor->netBudgetPoints());
        self::assertSame(9, $major->netBudgetPoints());
    }

    /**
     * Un pacte rend bien de la **puissance**, pas seulement du budget.
     *
     * Sans cet ecart, le pacte serait une comptabilite : le joueur perdrait
     * quelque chose et ne gagnerait rien de visible.
     */
    public function testAPactBuysRealPowerAndNotJustBookkeeping(): void
    {
        $reader = $this->reader();

        $plain = $reader->read([['lever' => 'power', 'points' => 9]])[0];
        $major = $reader->read([['lever' => 'power', 'points' => 19, 'pact' => ['lever' => 'life', 'points' => 10]]])[0];

        self::assertGreaterThan($reader->effectOf($plain), $reader->effectOf($major));
    }

    /**
     * Regle 3 — un pacte ne se paie jamais dans sa propre monnaie.
     *
     * `power +19 %, power −10 %` ne serait pas un renoncement, ce serait une
     * soustraction ecrite en deux lignes.
     */
    public function testAPactCannotPayForItself(): void
    {
        $this->expectException(CombatLeverDefinitionException::class);

        $this->reader()->read([['lever' => 'power', 'points' => 19, 'pact' => ['lever' => 'power', 'points' => 10]]]);
    }

    /**
     * Rien entre les deux crans.
     *
     * Une echelle continue rejouerait le defaut qu'ARC-06a a corrige sur les
     * couts : deux valeurs voisines ne diraient pas deux decisions, elles
     * diraient qu'on a dose a la main.
     */
    public function testThereIsNothingBetweenTheTwoCrans(): void
    {
        foreach ([1, 3, 7, 8, 12, 15] as $points) {
            try {
                $this->reader()->read([['lever' => 'power', 'points' => 9 + $points, 'pact' => ['lever' => 'life', 'points' => $points]]]);
                self::fail(sprintf('Un pacte de %d pb devrait etre refuse.', $points));
            } catch (CombatLeverDefinitionException) {
                self::assertTrue(true);
            }
        }
    }

    /**
     * Un nœud a pacte qui ne rend rien est refuse.
     *
     * Si le nœud ne vaut pas le passif plus le malus, le pacte **ajoute** au
     * lieu de rendre — c'est-a-dire qu'il achete de la puissance gratuite, la
     * porte de sortie que la regle 5 ferme.
     */
    public function testAPactThatAddsInsteadOfTradingIsRefused(): void
    {
        $this->expectException(CombatLeverDefinitionException::class);

        // 20 pb pour un malus de 10 : le nœud devrait en valoir 19.
        $this->reader()->read([['lever' => 'power', 'points' => 20, 'pact' => ['lever' => 'life', 'points' => 10]]]);
    }

    /**
     * Regle 5 — les plafonds par levier tiennent toujours.
     *
     * Le pacte contourne le budget de l'arbre, jamais le plafond d'un levier.
     * Consequence mesurable, et c'est elle qui fait qu'**un arbre a pacte est
     * un autre arbre** : un pacte majeur (19 pb) n'entre que sur les leviers
     * plafonnes a 20, donc pas sur `guard`, plafonne a 15.
     */
    public function testAMajorPactDoesNotFitOnALeverCappedBelowNineteen(): void
    {
        $scale = new CombatLeverScale(new CombatLeverDefinitionLoader(\dirname(__DIR__, 4)));
        self::assertLessThan(19, $scale->capOf(CombatLever::Guard), 'Le test perd son sens si `guard` monte a 19.');

        $this->expectException(CombatLeverDefinitionException::class);
        $this->reader()->read([['lever' => 'guard', 'points' => 19, 'pact' => ['lever' => 'power', 'points' => 10]]]);
    }

    /**
     * Regle 6 — le malus est visible **avant** d'apprendre.
     *
     * *On assume un choix, on ne se fait pas pieger.* Un pacte qu'on decouvre
     * apres l'avoir appris n'est pas un renoncement.
     */
    public function testTheMalusIsShownBeforeLearning(): void
    {
        $root = \dirname(__DIR__, 4);
        $presenter = new SkillLeverPresenter(
            $this->reader(),
            new CombatLeverScale(new CombatLeverDefinitionLoader($root)),
            new EquipmentPortCatalog($root),
        );

        $skill = new Skill();
        $skill->setSlug('sang-qui-bout');
        $skill->setLevers([['lever' => 'power', 'points' => 19, 'pact' => ['lever' => 'life', 'points' => 10]]]);

        $readout = $presenter->readoutsOf($skill)[0];

        self::assertTrue($readout->isPact());
        self::assertNotNull($readout->pactCost);
        self::assertStringContainsString('−', $readout->pactCost, 'Le malus se lit comme une perte, pas comme un gain.');
    }

    /**
     * Un nœud ordinaire n'est pas un pacte, et ne pretend pas l'etre.
     */
    public function testAnOrdinaryNodeCarriesNoPact(): void
    {
        $grant = $this->reader()->read([['lever' => 'power', 'points' => 9]])[0];

        self::assertFalse($grant->isPact());
        self::assertNull($grant->pact);
        self::assertSame($grant->budgetPoints, $grant->netBudgetPoints());
    }

    /**
     * Les deux crans sont ceux du canon, et il n'y en a pas de troisieme.
     */
    public function testTheGridHasExactlyTwoCrans(): void
    {
        self::assertSame([5, 10], PactGrant::CRANS);
    }
}
