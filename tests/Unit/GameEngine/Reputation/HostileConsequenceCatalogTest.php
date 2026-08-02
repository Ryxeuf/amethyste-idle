<?php

namespace App\Tests\Unit\GameEngine\Reputation;

use App\GameEngine\Reputation\FactionTensionDefinitionException;
use App\GameEngine\Reputation\HostileConsequenceCatalog;
use PHPUnit\Framework\TestCase;

/**
 * Ce qu'etre Hostile coute — et ce qu'aucune consequence ne peut etre (FAC-03).
 *
 * Le garde-fou du plan (« aucun Hostile ne bloque energie, voyage de base,
 * combat, plancher T1 ») n'est pas une liste de gardes dispersees : c'est le
 * **vocabulaire ferme** des types. Un type inconnu est refuse par le loader —
 * ajouter une consequence passe forcement par ici, ou le garde-fou se voit.
 */
class HostileConsequenceCatalogTest extends TestCase
{
    private function catalog(): HostileConsequenceCatalog
    {
        return new HostileConsequenceCatalog(\dirname(__DIR__, 4));
    }

    /**
     * Le fichier livre declare les cinq maisons : deux consequences actives
     * (surcharge Marchands, fouilles des Chevaliers) et quatre crochets qui
     * attendent leur substrat (Autel, rumeurs, Fonderie, lecture).
     */
    public function testTheShippedFileDeclaresTheFiveHouses(): void
    {
        $catalog = $this->catalog();

        self::assertSame(['marchands', 'chevaliers', 'ombres', 'fonderie', 'mages'], $catalog->factions());

        self::assertSame(10, $catalog->percentFor('marchands', 'shop_surcharge'));
        self::assertSame(50, $catalog->percentFor('chevaliers', 'bastion_travel_surcharge'));
        self::assertTrue($catalog->hasConsequence('chevaliers', 'altar_tax_ceiling'));
        self::assertTrue($catalog->hasConsequence('ombres', 'poisoned_rumors'));
        self::assertTrue($catalog->hasConsequence('fonderie', 'buyback_floor_closed'));
        self::assertTrue($catalog->hasConsequence('mages', 'materia_reading_refused'));

        self::assertSame(0, $catalog->percentFor('marchands', 'bastion_travel_surcharge'));
        self::assertSame([], $catalog->consequencesFor('faction_inconnue'));
    }

    /**
     * Le garde-fou : le vocabulaire est ferme, et aucun de ses types ne touche
     * la boucle cœur. Ce test fige la liste — l'agrandir est un acte conscient,
     * qui repasse par la doctrine de GAME_WORLD § 6.4 d.
     */
    public function testTheVocabularyIsClosedAndNeverTouchesTheCoreLoop(): void
    {
        self::assertSame([
            'shop_surcharge',
            'bastion_travel_surcharge',
            'altar_tax_ceiling',
            'poisoned_rumors',
            'buyback_floor_closed',
            'materia_reading_refused',
        ], HostileConsequenceCatalog::TYPES);

        // Aucun type ne nomme un blocage de l'energie, du voyage de base, du
        // combat ou du plancher T1 : les deux seuls types qui touchent un prix
        // ou une duree sont des surcharges — elles majorent, jamais ne ferment.
        self::assertSame(['shop_surcharge', 'bastion_travel_surcharge'], HostileConsequenceCatalog::SURCHARGE_TYPES);
    }

    public function testAnUnknownConsequenceTypeIsRefused(): void
    {
        $this->expectException(FactionTensionDefinitionException::class);

        $this->catalog()->normalize([
            'hostile' => [
                'consequences' => [
                    'marchands' => [['type' => 'block_travel']],
                ],
            ],
        ]);
    }

    public function testASurchargeWithoutAPercentIsRefused(): void
    {
        $this->expectException(FactionTensionDefinitionException::class);

        $this->catalog()->normalize([
            'hostile' => [
                'consequences' => [
                    'marchands' => [['type' => 'shop_surcharge']],
                ],
            ],
        ]);
    }

    public function testAPercentOnANonSurchargeIsRefused(): void
    {
        $this->expectException(FactionTensionDefinitionException::class);

        $this->catalog()->normalize([
            'hostile' => [
                'consequences' => [
                    'mages' => [['type' => 'materia_reading_refused', 'percent' => 10]],
                ],
            ],
        ]);
    }

    public function testAMissingHostileBlockIsRefused(): void
    {
        $this->expectException(FactionTensionDefinitionException::class);

        $this->catalog()->normalize(['tension_pairs' => []]);
    }
}
