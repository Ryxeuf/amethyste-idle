<?php

namespace App\Tests\E2E;

/**
 * E2E : Parcours combat complet.
 * carte → engagement mob → combat → victoire → loot → retour carte.
 */
class CombatFlowTest extends AbstractE2ETestCase
{
    public function testFightPageRedirectsToMapWhenNoFight(): void
    {
        $this->login();

        static::$pantherClient->request('GET', '/game/fight');
        $this->waitForUrlContaining('/game/map');

        $this->assertStringContainsString('/game/map', static::$pantherClient->getCurrentURL());
    }

    public function testCombatFlowViaApiMove(): void
    {
        $this->login();

        // 1. Verifier que la carte est accessible et PixiJS charge
        static::$pantherClient->request('GET', '/game/map');
        $this->waitForSelector('.map-canvas-container');
        $this->waitForPixi();
        $this->assertSelectorExists('.map-canvas-container');

        // 2. Declencher un combat en se deplacant vers un mob via l'API
        $mobPositions = [
            ['targetX' => 14, 'targetY' => 16], // ochu_1
            ['targetX' => 17, 'targetY' => 2],  // zombie_1
            ['targetX' => 6, 'targetY' => 5],   // zombie_2
            ['targetX' => 10, 'targetY' => 8],  // goblin_1
            ['targetX' => 12, 'targetY' => 10], // goblin_2
        ];

        $fightTriggered = false;
        foreach ($mobPositions as $pos) {
            $result = $this->apiFetch('/api/map/move', 'POST', $pos);

            if (isset($result['fight']['id'])) {
                $fightTriggered = true;
                break;
            }
        }

        if (!$fightTriggered) {
            $this->markTestSkipped('Aucun combat declenche par deplacement — les mobs ne sont peut-etre pas charges.');
        }

        // 3. Naviguer vers la page de combat
        static::$pantherClient->request('GET', '/game/fight');
        $this->waitForSelector('#action-attack');
        $this->waitForTurbo();

        $this->assertSelectorExists('#action-attack');
        $this->assertSelectorExists('#action-flee');
        $this->assertSelectorExists('[data-target-type="mob"]');

        // 4. Recuperer l'id du premier mob vivant
        $mobId = static::$pantherClient->executeScript(
            "const el = document.querySelector('[data-mob-id]'); return el ? el.dataset.mobId : null;"
        );
        $this->assertNotNull($mobId, 'Un mob cible doit etre present');

        // 5. Boucle d'attaque (max 50 tours pour eviter boucle infinie)
        for ($i = 0; $i < 50; ++$i) {
            $result = $this->apiFetch('/game/fight/attack', 'POST', [
                'targetId' => (int) $mobId,
                'targetType' => 'mob',
            ]);

            if (isset($result['fight']['terminated']) && $result['fight']['terminated']) {
                break;
            }

            if (isset($result['error'])) {
                break;
            }
        }

        // 6. Verifier l'etat final
        static::$pantherClient->request('GET', '/game/fight');
        $this->waitForTurbo();

        $url = static::$pantherClient->getCurrentURL();

        if (str_contains($url, '/game/fight/loot')) {
            // Victoire → page de loot
            $this->waitForSelector('#lootForm');
            $this->assertSelectorExists('#lootForm');

            // Cocher tous les items et collecter
            static::$pantherClient->executeScript(
                "document.querySelectorAll('input[name=\"items[]\"]').forEach(cb => cb.checked = true);"
            );

            static::$pantherClient->executeScript(
                "document.getElementById('lootForm').dispatchEvent(new Event('submit'));"
            );

            $this->waitForUrlContaining('/game/map');
            $this->assertStringContainsString('/game/map', static::$pantherClient->getCurrentURL());
        } elseif (str_contains($url, '/game/fight')) {
            // Defaite — la page de defaite est affichee
            $this->assertSelectorExists('a[href*="/game/map"]');
        } else {
            // Redirige vers carte (plus de combat)
            $this->assertStringContainsString('/game/map', $url);
        }
    }

    public function testFightActionsUiElements(): void
    {
        $this->login();

        // Engager un combat via API
        $result = $this->apiFetch('/api/map/move', 'POST', ['targetX' => 14, 'targetY' => 16]);

        if (!isset($result['fight']['id'])) {
            $this->markTestSkipped('Aucun combat declenche — mob absent a cette position.');
        }

        static::$pantherClient->request('GET', '/game/fight');
        $this->waitForSelector('#action-attack');
        $this->waitForTurbo();

        // Verifier la presence de tous les boutons d'action
        $this->assertSelectorExists('#action-attack');
        $this->assertSelectorExists('#fight-spells');
        $this->assertSelectorExists('#fight-objects');
        $this->assertSelectorExists('#action-flee');

        // Verifier les combattants
        $this->assertSelectorExists('[data-target-type="player"]');
        $this->assertSelectorExists('[data-target-type="mob"]');

        // Nettoyer : fuir le combat
        $this->apiFetch('/game/fight/flee');
    }
}
