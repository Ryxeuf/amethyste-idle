<?php

namespace App\Tests\Integration\Fight;

use App\GameEngine\Balance\ReferenceBuildFactory;
use App\GameEngine\Balance\ReferenceCharacterFactory;
use App\GameEngine\Balance\VitalityLaw;
use App\GameEngine\Fight\ArmorMitigationLaw;
use App\GameEngine\Progression\EquipmentPortCatalog;
use App\Tests\Integration\AbstractIntegrationTestCase;

/**
 * La mitigation d'armure, mesuree sur les arbres reels (ARC-19).
 *
 * La decision 21 du canon dit *la mitigation d'un tank vient de son armure, pas
 * de son arbre*, et GAME_VITALITY § 8 en fait son invariant 12 : **plus de la
 * moitie de l'ecart de PV effectifs entre le build le plus solide et le plus
 * fragile vient de l'armure**. Ce contrat le mesure au lieu de le supposer.
 */
class ArmorMitigationContractTest extends AbstractIntegrationTestCase
{
    /**
     * Les arbres de combat qui n'enseignent **aucune** ligne d'armure — donc
     * qui ne mitigent rien.
     *
     * Cliquet nomme : la liste peut retrecir (ARC-08 convertit les arbres
     * restants, et l'echelle de port suit), jamais s'allonger en silence. Un
     * arbre absent de toutes les lignes n'est pas un defaut de moteur — c'est
     * une donnee d'echelle a completer, et la nommer vaut mieux que de lui
     * preter une plaque par defaut.
     *
     * @var list<string>
     */
    private const TREES_WITHOUT_AN_ARMOUR_LINE = ['artificer'];

    public function testEveryCombatTreeTeachesALineOrIsNamed(): void
    {
        $catalog = new EquipmentPortCatalog(\dirname(__DIR__, 3));
        $without = [];

        foreach ($this->buildFactory()->all() as $build) {
            if ($catalog->armorLineTaughtBy($build->treeKey) === null) {
                $without[$build->treeKey] = true;
            }
        }

        self::assertSame(
            self::TREES_WITHOUT_AN_ARMOUR_LINE,
            array_values(array_keys($without)),
            'La liste des arbres sans ligne d\'armure a change : elle ne peut que retrecir.',
        );
    }

    /**
     * **L'ecart vient de l'armure, et il est chiffre.**.
     *
     * L'invariant 12 de GAME_VITALITY : par l'arbre seul, l'ecart entre le
     * build le plus solide et le plus fragile est une nuance ; avec l'armure,
     * il devient franc. On mesure les deux et on compare — *un invariant qui ne
     * se mesure pas est une intention*.
     */
    public function testMostOfTheEffectiveLifeGapComesFromTheArmour(): void
    {
        $byTreeOnly = [];
        $withArmour = [];

        foreach ($this->buildFactory()->all() as $build) {
            $character = $this->characterFactory()->of($build, VitalityLaw::FIRST_TIER);

            // Les PV effectifs : ce qu'il faut de degats bruts pour vider la
            // barre — c'est la lecture du § 2.2, et elle compose les deux
            // reductions comme la formule les compose.
            $tree = $character->maxLife / max(0.01, $character->guardMultiplier);
            $byTreeOnly[] = $tree;
            $withArmour[] = $tree / max(0.01, $character->armorMultiplier);
        }

        self::assertNotSame([], $byTreeOnly, 'Aucun build de reference : le contrat ne mesure rien.');

        // Les ecarts se **composent**, ils ne s'additionnent pas : l'ecart
        // total est le produit de ce que l'arbre apporte et de ce que l'armure
        // apporte. « Plus de la moitie vient de l'armure » se lit donc sur les
        // logarithmes — additionner des rapports melangerait deux echelles.
        $treeGap = max($byTreeOnly) / min($byTreeOnly);
        $totalGap = max($withArmour) / min($withArmour);
        $armourGap = $totalGap / $treeGap;

        self::assertGreaterThan(
            $treeGap,
            $armourGap,
            'L\'armure n\'ecarte pas les builds davantage que l\'arbre : la decision 21 du canon ne tient plus.',
        );

        self::assertGreaterThan(
            0.5,
            log($armourGap) / log($totalGap),
            'Moins de la moitie de l\'ecart de PV effectifs vient de l\'armure (invariant 12 de GAME_VITALITY).',
        );
    }

    /**
     * La ligne la plus protectrice passe le plancher de l'aggro (§ 13.4).
     *
     * Sans ce seuil, le transfert d'ARC-18d tue son porteur quoi qu'il fasse :
     * la borne de 50 % suppose une plaque a ~28 % au minimum.
     */
    public function testThePlateClearsTheAggroFloor(): void
    {
        self::assertGreaterThanOrEqual(
            ArmorMitigationLaw::AGGRO_FLOOR,
            ArmorMitigationLaw::shareOfLine('plate'),
        );
    }

    private function buildFactory(): ReferenceBuildFactory
    {
        $factory = static::getContainer()->get(ReferenceBuildFactory::class);
        self::assertInstanceOf(ReferenceBuildFactory::class, $factory);

        return $factory;
    }

    private function characterFactory(): ReferenceCharacterFactory
    {
        $factory = static::getContainer()->get(ReferenceCharacterFactory::class);
        self::assertInstanceOf(ReferenceCharacterFactory::class, $factory);

        return $factory;
    }
}
