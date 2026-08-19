<?php

namespace App\Tests\Unit\GameEngine\Repertoire;

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

    private function catalog(): RepertoireCatalog
    {
        return new RepertoireCatalog(\dirname(__DIR__, 4));
    }
}
