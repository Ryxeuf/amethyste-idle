<?php

namespace App\Tests\Unit\GameEngine\Reputation;

use App\GameEngine\Reputation\FactionTensionDefinitionException;
use App\GameEngine\Reputation\GestureReputationCatalog;
use PHPUnit\Framework\TestCase;

/**
 * Le routage geste → faction, et ce que son loader refuse (FAC-02).
 *
 * Le catalogue est declaratif : un geste sans destinataire, un montant qui
 * n'est pas un gain, un plafond absent — aucun de ces defauts ne se verrait a
 * l'execution, ils produiraient des silences. Le loader les transforme en
 * erreurs.
 */
class GestureReputationCatalogTest extends TestCase
{
    private function catalog(): GestureReputationCatalog
    {
        return new GestureReputationCatalog(\dirname(__DIR__, 4));
    }

    /**
     * Le fichier livre porte les cinq routes du plan : les deux gestes actifs
     * (vente HV, mort-vivant) et les trois crochets declares en avance
     * (fondre, lire, marche gris) — la meme doctrine que la paire de tension
     * de la Fonderie : personne ne se souvient de revenir cabler un crochet.
     */
    public function testTheShippedFileRoutesTheFiveGestures(): void
    {
        $catalog = $this->catalog();

        self::assertSame(
            ['auction_sale', 'undead_kill', 'materia_melt', 'materia_read', 'grey_market_sale'],
            $catalog->gestures(),
        );

        self::assertSame('marchands', $catalog->routeFor('auction_sale')['faction']);
        self::assertSame('chevaliers', $catalog->routeFor('undead_kill')['faction']);
        self::assertSame('fonderie', $catalog->routeFor('materia_melt')['faction']);
        self::assertSame('mages', $catalog->routeFor('materia_read')['faction']);
        self::assertSame('ombres', $catalog->routeFor('grey_market_sale')['faction']);

        self::assertNull(
            $catalog->routeFor('undead_kill')['amount'],
            'Le montant du kill suit le palier du monstre : la route ne le fixe pas.',
        );
        self::assertGreaterThan(0, $catalog->dailyCap());
        self::assertNull($catalog->routeFor('unknown_gesture'));
    }

    /**
     * Chaque slug de la liste des morts-vivants existe dans le bestiaire — une
     * coquille dans un slug ferait du geste un silence, jamais une erreur.
     */
    public function testEveryUndeadSlugExistsInTheBestiary(): void
    {
        $catalog = $this->catalog();
        $fixtures = (string) file_get_contents(\dirname(__DIR__, 4) . '/src/DataFixtures/MonsterFixtures.php');

        self::assertNotEmpty($catalog->undeadSlugs());
        foreach ($catalog->undeadSlugs() as $slug) {
            self::assertStringContainsString(
                sprintf("'%s'", $slug),
                $fixtures,
                sprintf('Le slug "%s" de la liste des morts-vivants ne correspond a aucun monstre du bestiaire.', $slug),
            );
        }

        self::assertTrue($catalog->isUndead('skeleton'));
        self::assertFalse($catalog->isUndead('slime'));
    }

    public function testARouteWithoutAFactionIsRefused(): void
    {
        $this->expectException(FactionTensionDefinitionException::class);

        $this->catalog()->normalize([
            'gestures' => [
                'daily_cap' => 200,
                'routes' => ['auction_sale' => ['amount' => 10]],
            ],
        ]);
    }

    public function testANonPositiveAmountIsRefused(): void
    {
        $this->expectException(FactionTensionDefinitionException::class);

        $this->catalog()->normalize([
            'gestures' => [
                'daily_cap' => 200,
                'routes' => ['auction_sale' => ['faction' => 'marchands', 'amount' => 0]],
            ],
        ]);
    }

    public function testAMissingOrNonPositiveDailyCapIsRefused(): void
    {
        $this->expectException(FactionTensionDefinitionException::class);

        $this->catalog()->normalize([
            'gestures' => [
                'routes' => ['auction_sale' => ['faction' => 'marchands']],
            ],
        ]);
    }

    public function testAMissingGesturesBlockIsRefused(): void
    {
        $this->expectException(FactionTensionDefinitionException::class);

        $this->catalog()->normalize(['tension_pairs' => []]);
    }
}
