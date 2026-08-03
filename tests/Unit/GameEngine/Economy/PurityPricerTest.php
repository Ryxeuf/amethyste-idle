<?php

namespace App\Tests\Unit\GameEngine\Economy;

use App\Entity\App\PlayerItem;
use App\Entity\Game\Item;
use App\Enum\Purity;
use App\GameEngine\Economy\PurityDefinitionException;
use App\GameEngine\Economy\PurityDefinitionLoader;
use App\GameEngine\Economy\PurityPricer;
use PHPUnit\Framework\TestCase;

/**
 * La valeur marchande de la bande (MET-01).
 *
 * Le constat de GAME_TRADES § 3.2 : la bande avait une valeur d'usage et
 * aucune valeur d'echange — la branche Preserver etait strictement dominee
 * avant d'exister. Le contrat du jalon : deux lots de la meme matiere en
 * trouble et en parfait ont un rapport de prix de 9, a tous les endroits ou
 * un prix s'affiche, et le rapport est identique pour toutes les matieres.
 */
class PurityPricerTest extends TestCase
{
    /**
     * Contrat sur le fichier livre : l'echelle actee du 2026-08-02, telle
     * quelle. Trouble x1, clair x1,8, pur x3,5, parfait x9.
     */
    public function testTheShippedScaleIsTheActedOne(): void
    {
        $pricer = $this->shippedPricer();

        self::assertSame(1.0, $pricer->multiplierFor(Purity::Trouble));
        self::assertSame(1.8, $pricer->multiplierFor(Purity::Clair));
        self::assertSame(3.5, $pricer->multiplierFor(Purity::Pur));
        self::assertSame(9.0, $pricer->multiplierFor(Purity::Parfait));
    }

    /**
     * Un lot sans bande — tout ce qui n'est pas la ligne du cristal — vaut son
     * prix de reference, inchange. Les herbes ne deviennent pas un tableur.
     */
    public function testALotWithoutABandKeepsItsReferencePrice(): void
    {
        $pricer = $this->shippedPricer();

        self::assertSame(1.0, $pricer->multiplierFor(null));
        self::assertSame(15, $pricer->unitValueOf($this->lot(15, null)));
    }

    /**
     * Le cœur du jalon : le rapport trouble/parfait est de 9 exactement, et il
     * est identique quelle que soit la matiere.
     */
    public function testTroubleAndParfaitKeepARatioOfNineOnEveryMaterial(): void
    {
        $pricer = $this->shippedPricer();

        foreach ([15, 40, 120] as $referencePrice) {
            $trouble = $pricer->unitValueOf($this->lot($referencePrice, Purity::Trouble));
            $parfait = $pricer->unitValueOf($this->lot($referencePrice, Purity::Parfait));

            self::assertSame($referencePrice, $trouble);
            self::assertSame(9 * $referencePrice, $parfait);
        }
    }

    /**
     * Le rachat PNJ : 30 % du prix de reference, puis la bande. L'ordre
     * preserve le rapport exact — multiplier avant de tronquer donnerait
     * x10 sur l'amethystite a 15 gils (135 x 0,3 tronque = 40, trouble = 4).
     */
    public function testTheBuybackKeepsTheRatioOfNine(): void
    {
        $pricer = $this->shippedPricer();

        $trouble = $pricer->buybackValueOf($this->lot(15, Purity::Trouble));
        $parfait = $pricer->buybackValueOf($this->lot(15, Purity::Parfait));

        self::assertSame(4, $trouble);
        self::assertSame(36, $parfait);
        self::assertSame(9 * $trouble, $parfait);
    }

    /**
     * Le rachat d'une matiere **sans lot en main** : le taux commun seul,
     * sans bande. C'est ce que le catalogue de ressources affiche (ONB-07b),
     * et le taux ne vit qu'ici — deux endroits qui l'appliqueraient
     * finiraient par diverger d'un arrondi.
     */
    public function testTheReferenceBuybackIsTheCommonRateWithoutAnyBand(): void
    {
        $pricer = $this->shippedPricer();

        $item = new Item();
        $item->setSlug('ore-copper');
        $item->setPrice(15);

        self::assertSame(4, $pricer->referenceBuybackValue($item));
        self::assertSame($pricer->buybackValueOf($this->lot(15, Purity::Trouble)), $pricer->referenceBuybackValue($item));

        // Plancher a 1 : une matiere sans prix ne rend jamais zero.
        $free = new Item();
        $free->setSlug('ore-cheap');
        $free->setPrice(0);
        self::assertSame(1, $pricer->referenceBuybackValue($free));
    }

    /**
     * Un lot sans bande se rachete exactement comme avant le jalon : le
     * plancher a 1 gil et la troncature a 30 % ne bougent pas.
     */
    public function testTheBuybackOfABandlessLotIsUnchanged(): void
    {
        $pricer = $this->shippedPricer();

        self::assertSame(4, $pricer->buybackValueOf($this->lot(15, null)));
        self::assertSame(1, $pricer->buybackValueOf($this->lot(0, null)));
        self::assertSame(1, $pricer->buybackValueOf($this->lot(2, null)));
    }

    /**
     * La valeur d'une commande : chaque materiau bloque est valorise a sa
     * propre bande, jamais au prix generique.
     */
    public function testTheLotsOfAnOrderAreEachValuedAtTheirOwnBand(): void
    {
        $pricer = $this->shippedPricer();

        $total = $pricer->lotsValueOf([
            $this->lot(15, Purity::Trouble),
            $this->lot(15, Purity::Pur),
            $this->lot(15, Purity::Parfait),
            $this->lot(10, null),
        ]);

        // 15 + 53 (15 x 3,5 arrondi) + 135 + 10.
        self::assertSame(213, $total);
    }

    /**
     * L'arrondi est commercial (au plus proche) : le pur x3,5 sur une matiere
     * a 15 gils rend 53 (52,5 arrondi), pas 52 tronque. Tronquer ferait perdre
     * aux bandes hautes une part de leur promesse sur les petits prix, la ou
     * vivent toutes les matieres T1.
     */
    public function testTheRoundingIsCommercial(): void
    {
        $pricer = $this->shippedPricer();

        self::assertSame(53, $pricer->apply(15, Purity::Pur));
        self::assertSame(27, $pricer->apply(15, Purity::Clair));
    }

    /**
     * Une bande sans multiplicateur prierait le code de choisir un defaut, et
     * le choix differerait selon l'appelant.
     */
    public function testAScaleMissingABandIsRefusedAtLoad(): void
    {
        $this->expectException(PurityDefinitionException::class);
        $this->expectExceptionMessageMatches('/positive multiplier/');

        (new PurityDefinitionLoader('/project'))->normalize($this->rawWith([
            'trouble' => 1, 'clair' => 1.8, 'pur' => 3.5,
        ]));
    }

    /**
     * Une echelle qui redescend ferait payer une bande haute moins cher qu'une
     * basse — le prix contredirait le tirage.
     */
    public function testADescendingScaleIsRefusedAtLoad(): void
    {
        $this->expectException(PurityDefinitionException::class);
        $this->expectExceptionMessageMatches('/must not descend/');

        (new PurityDefinitionLoader('/project'))->normalize($this->rawWith([
            'trouble' => 1, 'clair' => 1.8, 'pur' => 1.5, 'parfait' => 9,
        ]));
    }

    /**
     * La section est obligatoire : une bande sans prix est exactement le
     * defaut que le jalon corrige, et son absence doit rougir en CI.
     */
    public function testAMissingMarketSectionIsRefusedAtLoad(): void
    {
        $this->expectException(PurityDefinitionException::class);
        $this->expectExceptionMessageMatches('/must declare "market"/');

        $raw = $this->rawWith(['trouble' => 1, 'clair' => 1.8, 'pur' => 3.5, 'parfait' => 9]);
        unset($raw['market']);

        (new PurityDefinitionLoader('/project'))->normalize($raw);
    }

    private function shippedPricer(): PurityPricer
    {
        return new PurityPricer(new PurityDefinitionLoader(\dirname(__DIR__, 4)));
    }

    private function lot(int $price, ?Purity $band): PlayerItem
    {
        $item = new Item();
        $item->setSlug('ore-copper');
        $item->setPrice($price);

        $lot = new PlayerItem();
        $lot->setGenericItem($item);
        $lot->setPurity($band);

        return $lot;
    }

    /**
     * @param array<string, int|float> $multipliers
     *
     * @return array<string, mixed>
     */
    private function rawWith(array $multipliers): array
    {
        return [
            'scope' => ['slug_prefixes' => ['ore-']],
            'draw' => [
                'base_weights' => ['trouble' => 60, 'clair' => 30, 'pur' => 9, 'parfait' => 1],
                'vitality_ceilings' => [
                    ['at_least' => 0.66, 'band' => 'parfait'],
                    ['at_least' => 0.33, 'band' => 'pur'],
                    ['at_least' => 0.10, 'band' => 'clair'],
                    ['at_least' => 0.0, 'band' => 'trouble'],
                ],
                'skill_weight_per_point' => 1,
                'skill_weight_cap' => 25,
            ],
            'market' => ['band_multipliers' => $multipliers],
        ];
    }
}
