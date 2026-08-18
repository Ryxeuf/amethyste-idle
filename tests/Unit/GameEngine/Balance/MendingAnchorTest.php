<?php

namespace App\Tests\Unit\GameEngine\Balance;

use App\GameEngine\Balance\EncounterAnchor;
use App\GameEngine\Balance\MendingAnchor;
use App\GameEngine\Balance\VitalityLaw;
use PHPUnit\Framework\TestCase;

/**
 * Les soins a valeur fixe, sous une ancre symetrique (ARC-20a).
 *
 * GAME_VITALITY § 5. Le canon fixe deja ce qu'un geste **retire** (25 % de la vie
 * d'un commun de son palier) et ne disait rien de ce qu'il **rend**. La reponse
 * a « les soins fixes sont-ils viables ? » est oui — a condition que la grille se
 * derive, parce que deux tables tenues a la main divergent au premier ajustement
 * du bestiaire.
 */
class MendingAnchorTest extends TestCase
{
    /**
     * La grille tombe sur les valeurs du document.
     */
    public function testTheGridLandsOnTheDocumentedValues(): void
    {
        self::assertSame([24, 52, 110, 220], array_map(
            static fn (int $tier): int => MendingAnchor::directHealFor($tier),
            [1, 2, 3, 4],
        ));

        self::assertSame([8, 17, 35, 70], array_map(
            static fn (int $tier): int => MendingAnchor::depositPerTurnFor($tier),
            [1, 2, 3, 4],
        ));
    }

    /**
     * **La symetrie est le coeur du jalon** : un soin direct rend de la barre du
     * joueur exactement ce qu'un geste retire a la vie d'un commun.
     *
     * Les deux ancres partagent donc leur quart, et le partager **par la
     * constante** et non par un chiffre recopie est ce qui les empeche de
     * diverger — *une regle recopiee derive de son original en silence*.
     */
    public function testADirectHealMirrorsWhatAGestureTakes(): void
    {
        self::assertSame(EncounterAnchor::SHARE_OF_COMMON_LIFE, MendingAnchor::DIRECT_SHARE_OF_BAR);
    }

    /**
     * La part rendue ne depend pas du palier — le pendant, cote soin, de
     * l'invariant qui tient la barre.
     */
    public function testTheShareRestoredIsTheSameAtEveryTier(): void
    {
        foreach (range(VitalityLaw::FIRST_TIER, VitalityLaw::LAST_TIER) as $tier) {
            self::assertEqualsWithDelta(
                MendingAnchor::DIRECT_SHARE_OF_BAR,
                MendingAnchor::shareOfBar(MendingAnchor::directHealFor($tier), $tier),
                0.005,
                sprintf('T%d : un soin direct ne rend pas la meme part de barre qu\'ailleurs.', $tier),
            );
        }
    }

    /**
     * **Le direct est l'urgence, le depot la provision** (GAME_ARCHETYPES § 7 bis).
     *
     * Sur les six tours d'un depot ordinaire, celui-ci rend davantage qu'un soin
     * direct — la duree etale la valeur et la pose en avance, mais elle ne la
     * rend pas au moment ou quelqu'un tombe. Ce qui borne un depot defensif,
     * c'est la barre de sa cible, qui l'ecrete toute seule.
     */
    public function testADepositOutweighsADirectHealOverItsDuration(): void
    {
        foreach (range(VitalityLaw::FIRST_TIER, VitalityLaw::LAST_TIER) as $tier) {
            self::assertGreaterThan(
                MendingAnchor::directHealFor($tier),
                MendingAnchor::depositPerTurnFor($tier) * 6,
                sprintf('T%d : un depot qui rend moins qu\'un direct sur toute sa duree n\'a aucune raison d\'exister.', $tier),
            );
        }
    }

    /**
     * **L'obsolescence est une fonctionnalite**, et elle se mesure.
     *
     * Un soin de palier 1 rend moins de 5 % d'une barre de palier 4 : c'est ce
     * qui donne un sens a la progression de materia, et c'est la raison pour
     * laquelle le plancher du jour 1 (GAME_MATERIA § 3) doit ouvrir un soin de
     * **son** palier, jamais un soin fige au palier 1.
     */
    public function testALowTierHealBecomesNegligibleHigherUp(): void
    {
        $share = MendingAnchor::shareOfBar(
            MendingAnchor::directHealFor(VitalityLaw::FIRST_TIER),
            VitalityLaw::LAST_TIER,
        );

        self::assertLessThan(0.05, $share);
    }

    /**
     * L'ecart se mesure dans les deux sens : un soin au bon niveau vaut 1, un
     * soin livre a 12 points en vaut beaucoup plus.
     *
     * *On ne recalibre pas ce qu'on ne mesure pas* — meme instrument
     * qu'`EncounterAnchor::shortfallFor()`.
     */
    public function testTheShortfallReadsBothWays(): void
    {
        foreach (range(VitalityLaw::FIRST_TIER, VitalityLaw::LAST_TIER) as $tier) {
            self::assertEqualsWithDelta(1.0, MendingAnchor::shortfallFor(MendingAnchor::directHealFor($tier), $tier), 0.02);
        }

        self::assertGreaterThan(4.0, MendingAnchor::shortfallFor(12, VitalityLaw::LAST_TIER));
        self::assertSame(\INF, MendingAnchor::shortfallFor(0, 1));
    }

    /**
     * **Aucun soin ne lit encore l'ancre, et c'est voulu** — ARC-20a mesure,
     * ARC-20c deplacera.
     */
    public function testNothingReadsTheAnchorYet(): void
    {
        $source = file_get_contents(\dirname(__DIR__, 4) . '/src/GameEngine/Fight/SpellApplicator.php');
        self::assertIsString($source);

        self::assertStringNotContainsString(
            'MendingAnchor',
            $source,
            'Le combat lit deja l\'ancre : ARC-20a ne devait deplacer aucune valeur de jeu.',
        );
    }
}
