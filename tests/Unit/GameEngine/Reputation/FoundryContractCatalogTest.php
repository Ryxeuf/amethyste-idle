<?php

namespace App\Tests\Unit\GameEngine\Reputation;

use App\GameEngine\Reputation\FactionTensionDefinitionException;
use App\GameEngine\Reputation\FoundryContractCatalog;
use PHPUnit\Framework\TestCase;

/**
 * Le pool des contrats d'approvisionnement, et ce que son loader refuse
 * (FAC-05).
 *
 * Un contrat sans matiere ne demande rien, un volume ou un prix nul ne paie
 * pas, une essence nulle casserait le paiement mixte — et une matiere qui
 * n'existe pas ferait une affiche que personne ne peut honorer, en silence.
 */
class FoundryContractCatalogTest extends TestCase
{
    private function catalog(): FoundryContractCatalog
    {
        return new FoundryContractCatalog(\dirname(__DIR__, 4));
    }

    public function testTheShippedPoolIsSoundAndMixedPaid(): void
    {
        $contracts = $this->catalog()->contracts();

        self::assertNotEmpty($contracts);
        foreach ($contracts as $contract) {
            self::assertGreaterThan(0, $contract['volume']);
            self::assertGreaterThan(0, $contract['gils_per_unit']);
            self::assertGreaterThan(0, $contract['essence'], sprintf(
                'Le contrat "%s" ne paie pas d\'essence : le paiement de la Fonderie est mixte par doctrine.',
                $contract['item'],
            ));
        }
    }

    /**
     * Chaque matiere du pool existe dans les donnees : une coquille ferait
     * une affiche impossible a honorer.
     */
    public function testEveryContractItemExists(): void
    {
        $root = \dirname(__DIR__, 4);
        $known = (string) file_get_contents($root . '/src/DataFixtures/ItemFixtures.php');
        foreach ((array) glob($root . '/fixtures/game/item/*.yaml') as $file) {
            $known .= (string) file_get_contents((string) $file);
        }

        foreach ($this->catalog()->contracts() as $contract) {
            self::assertSame(1, preg_match(
                sprintf("/slug' => '%s'|slug: '%s'/", preg_quote($contract['item'], '/'), preg_quote($contract['item'], '/')),
                $known,
            ), sprintf('La matiere "%s" du pool ne correspond a aucun objet.', $contract['item']));
        }
    }

    public function testAContractWithoutAnItemIsRefused(): void
    {
        $this->expectException(FactionTensionDefinitionException::class);

        $this->catalog()->normalize(['contracts' => [['volume' => 10, 'gils_per_unit' => 2, 'essence' => 1]]]);
    }

    public function testANonPositiveFieldIsRefused(): void
    {
        $this->expectException(FactionTensionDefinitionException::class);

        $this->catalog()->normalize(['contracts' => [['item' => 'ore-iron', 'volume' => 10, 'gils_per_unit' => 2, 'essence' => 0]]]);
    }

    public function testAnEmptyPoolIsRefused(): void
    {
        $this->expectException(FactionTensionDefinitionException::class);

        $this->catalog()->normalize(['contracts' => []]);
    }
}
