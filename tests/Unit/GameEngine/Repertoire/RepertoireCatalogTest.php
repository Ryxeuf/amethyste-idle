<?php

namespace App\Tests\Unit\GameEngine\Repertoire;

use App\Enum\Element;
use App\GameEngine\Repertoire\RepertoireCatalog;
use App\GameEngine\Repertoire\RepertoireDefinitionException;
use PHPUnit\Framework\TestCase;

/**
 * Le plafond anti-forcage, tel qu'il se declare (REP-01).
 */
class RepertoireCatalogTest extends TestCase
{
    public function testTheShippedFileDeclaresAPositiveCap(): void
    {
        self::assertGreaterThan(0, $this->catalog()->dailyReadingsPerPlayer());
    }

    /**
     * Les deux erreurs que le loader refuse sont **muettes en jeu** : on ne
     * s'apercoit d'un souvenir qui ne se remplit pas qu'au moment ou un seuil
     * aurait du tomber, c'est-a-dire des mois plus tard.
     */
    public function testAMissingOrEmptyCapIsRefusedRatherThanDefaulted(): void
    {
        foreach ([[], ['daily_readings_per_player' => 0], ['daily_readings_per_player' => 'cinq']] as $raw) {
            try {
                $this->catalog()->normalize($raw);
                self::fail('Un plafond invalide a ete accepte.');
            } catch (RepertoireDefinitionException) {
                self::assertTrue(true);
            }
        }
    }

    // =====================================================================
    // REP-02 — le bassin des gestes retrouves
    // =====================================================================

    /**
     * Le bassin livre couvre **les huit elements**.
     *
     * L'element est l'axe de premier rang de la dominante : un element sans
     * geste serait un vecu de serveur auquel le Repertoire n'aurait rien a
     * repondre — il lirait du metal toute l'annee et ne retrouverait jamais
     * rien.
     */
    public function testThePoolCoversEveryElement(): void
    {
        $covered = [];
        foreach ($this->catalog()->foundGestures() as $gesture) {
            foreach ($gesture['elements'] as $element) {
                $covered[$element] = true;
            }
        }

        $missing = array_values(array_diff(
            array_map(static fn (Element $e): string => $e->value, Element::cases()),
            array_keys($covered),
        ));

        // `none` n'est pas un element mais son absence (§ 9 quater) : aucune
        // materia ne le porte, donc aucun geste ne peut le retrouver. C'est le
        // seul manquant, et `Element::cases()` en compte neuf — `wood` ayant ete
        // tenu hors de l'enumeration par DOM-09, precisement pour qu'il ne
        // reclame ni materia, ni marque, ni geste.
        self::assertSame(['none'], $missing, 'Le bassin ne couvre plus les huit elements qui marquent.');
    }

    /**
     * **Une petite part du bassin est hors de portee de la plupart des
     * serveurs**, et c'est la ou l'exclusivite naît : de la condition, jamais
     * d'un marquage par serveur.
     *
     * Le test borne des **deux** cotes : aucune condition rare et le bassin est
     * une liste que tout le monde epuise ; trop de conditions rares et la
     * plupart des serveurs ne retrouvent presque rien.
     */
    public function testASmallPartOfThePoolIsRare(): void
    {
        $gestures = $this->catalog()->foundGestures();
        $rare = array_filter($gestures, static fn (array $g): bool => $g['condition'] !== null);

        self::assertGreaterThanOrEqual(2, \count($rare));
        self::assertLessThan(\count($gestures) / 3, \count($rare), 'Trop de gestes sont hors de portee : le bassin cesse d\'etre traversable.');
    }

    /**
     * Les quatre refus du chargeur.
     *
     * Le dernier est celui qui porte la **regle laterale** : une cle inconnue
     * est refusee plutot qu'ignoree, donc il n'existe aucun endroit ou ecrire
     * une statistique. *Ce qu'on ne peut pas ecrire ne peut pas deriver.*
     */
    public function testTheLoaderRefusesWhatWouldBeSilentlyBroken(): void
    {
        foreach ([
            'sans materia' => ['elements' => ['fire'], 'revelation' => 'a', 'revelation_en' => 'a'],
            'sans element' => ['awakens' => 'm4-meteor-strike', 'revelation' => 'a', 'revelation_en' => 'a'],
            'element inconnu' => ['awakens' => 'm4-meteor-strike', 'elements' => ['plasma'], 'revelation' => 'a', 'revelation_en' => 'a'],
            'condition inconnue' => ['awakens' => 'm4-meteor-strike', 'elements' => ['fire'], 'condition' => 'un_jour_peut_etre', 'revelation' => 'a', 'revelation_en' => 'a'],
            'sans revelation' => ['awakens' => 'm4-meteor-strike', 'elements' => ['fire']],
            'une statistique' => ['awakens' => 'm4-meteor-strike', 'elements' => ['fire'], 'revelation' => 'a', 'revelation_en' => 'a', 'damage' => 12],
        ] as $case => $gesture) {
            try {
                $this->catalog()->normalizeGestures(['found_gestures' => ['essai' => $gesture]]);
                self::fail(sprintf('Le geste « %s » a ete accepte.', $case));
            } catch (RepertoireDefinitionException) {
                self::assertTrue(true);
            }
        }
    }

    /**
     * **Aucun champ ou ecrire une date, un quota ou un serveur.**.
     *
     * La regle 1 du canon — *un seul bassin, ecrit une fois* — se tient par la
     * forme du fichier autant que par un test : le bassin n'a aucun moyen de
     * dire « pour ce serveur », « cette semaine-la » ou « les cinq premiers ».
     */
    public function testNothingInThePoolCanBeDatedQuotedOrReservedToAServer(): void
    {
        foreach ($this->catalog()->foundGestures() as $key => $gesture) {
            self::assertSame(
                ['awakens', 'elements', 'provenances', 'places', 'condition', 'revelation', 'revelation_en'],
                array_keys($gesture),
                sprintf('« %s » declare autre chose qu\'un geste tague.', $key),
            );
        }
    }

    private function catalog(): RepertoireCatalog
    {
        return new RepertoireCatalog(\dirname(__DIR__, 4));
    }
}
