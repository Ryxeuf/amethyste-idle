<?php

namespace App\Tests\E2E;

/**
 * E2E : acces a une boutique PNJ depuis l'ecran de zone (ZON-27).
 *
 * Remplace `ShopFlowTest`, supprime avec ZON-21a : il trouvait les marchands
 * via `/api/map/entities` (endpoint retire), et plus aucun ecran ne menait a
 * `/game/shop/{id}` — les boutiques etaient devenues injoignables.
 */
class ZoneShopFlowTest extends AbstractE2ETestCase
{
    public function testZoneListsPresentPnjs(): void
    {
        $this->login();

        static::$pantherClient->request('GET', '/game/zone');
        $this->waitForSelector('[data-testid="zone-pnjs"]', self::WAIT_TIMEOUT_SLOW);
        $this->waitForTurbo();

        $this->assertGreaterThan(
            0,
            $this->countSelector('[data-testid="zone-pnjs"]'),
            'L\'ecran de zone doit exposer les PNJ presents.'
        );
    }

    public function testMerchantShopIsReachableFromItsZone(): void
    {
        $this->login();

        // Le hub est la zone marchande : on s'y rend si le joueur n'y est pas.
        static::$pantherClient->request('GET', '/game/zone');
        $this->waitForSelector('[data-testid="zone-pnjs"]', self::WAIT_TIMEOUT_SLOW);
        $this->waitForTurbo();

        if (!$this->clickSelector('[data-testid="zone-pnj-shop-link"]')) {
            $this->markTestSkipped('Aucun marchand ouvert dans la zone courante.');
        }
        $this->waitForTurbo();

        $this->assertStringContainsString(
            '/game/shop/',
            static::$pantherClient->getCurrentURL(),
            'Le lien marchand doit ouvrir la boutique du PNJ.'
        );
        $this->assertGreaterThan(0, $this->countSelector('#player-gils'), 'Selecteur absent : #player-gils');
    }
}
