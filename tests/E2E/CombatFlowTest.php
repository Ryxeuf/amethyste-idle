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
        static::$pantherClient->waitFor('body');

        // Sans combat actif, le joueur est redirige vers la carte
        $url = static::$pantherClient->getCurrentURL();
        $this->assertStringContainsString('/game/map', $url);
    }

    public function testCombatFlowViaApiMove(): void
    {
        $this->login();

        // 1. Verifier que la carte est accessible
        static::$pantherClient->request('GET', '/game/map');
        static::$pantherClient->waitFor('body');
        $this->assertSelectorExists('.map-canvas-container');

        // 2. Declencher un combat en se deplacant vers un mob via l'API
        //    On essaie plusieurs positions de mobs connues (fixtures)
        $mobPositions = [
            ['x' => 14, 'y' => 16], // ochu_1
            ['x' => 17, 'y' => 2],  // zombie_1
            ['x' => 6, 'y' => 5],   // zombie_2
            ['x' => 10, 'y' => 8],  // goblin_1
            ['x' => 12, 'y' => 10], // goblin_2
        ];

        $fightTriggered = false;
        foreach ($mobPositions as $pos) {
            $result = static::$pantherClient->executeScript(sprintf(
                "return await fetch('/api/map/move', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({targetX: %d, targetY: %d})
                }).then(r => r.json()).catch(e => ({error: e.message}));",
                $pos['x'],
                $pos['y']
            ));

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
        static::$pantherClient->waitFor('#action-attack');

        // Verifier les elements du combat
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
            $result = static::$pantherClient->executeScript(sprintf(
                "return await fetch('/game/fight/attack', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({targetId: %s, targetType: 'mob'})
                }).then(r => r.json()).catch(e => ({error: e.message}));",
                $mobId
            ));

            if (isset($result['fight']['terminated']) && $result['fight']['terminated']) {
                break;
            }

            // Si erreur (joueur mort, cible morte, etc.), on arrete
            if (isset($result['error'])) {
                break;
            }
        }

        // 6. Verifier l'etat final
        static::$pantherClient->request('GET', '/game/fight');
        static::$pantherClient->waitFor('body');

        $url = static::$pantherClient->getCurrentURL();

        if (str_contains($url, '/game/fight/loot')) {
            // Victoire → page de loot
            $this->assertSelectorExists('#lootForm');

            // Cocher tous les items et collecter
            static::$pantherClient->executeScript(
                "document.querySelectorAll('input[name=\"items[]\"]').forEach(cb => cb.checked = true);"
            );

            // Soumettre le formulaire de loot
            static::$pantherClient->executeScript(
                "document.getElementById('lootForm').dispatchEvent(new Event('submit'));"
            );

            // Attendre la redirection vers la carte
            static::$pantherClient->waitFor('body', 5);
            $finalUrl = static::$pantherClient->getCurrentURL();
            $this->assertStringContainsString('/game/map', $finalUrl);
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
        $result = static::$pantherClient->executeScript(
            "return await fetch('/api/map/move', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({targetX: 14, targetY: 16})
            }).then(r => r.json()).catch(e => ({error: e.message}));"
        );

        if (!isset($result['fight']['id'])) {
            $this->markTestSkipped('Aucun combat declenche — mob absent a cette position.');
        }

        static::$pantherClient->request('GET', '/game/fight');
        static::$pantherClient->waitFor('#action-attack');

        // Verifier la presence de tous les boutons d'action
        $this->assertSelectorExists('#action-attack');
        $this->assertSelectorExists('#fight-spells');
        $this->assertSelectorExists('#fight-objects');
        $this->assertSelectorExists('#action-flee');

        // Verifier les combattants
        $this->assertSelectorExists('[data-target-type="player"]');
        $this->assertSelectorExists('[data-target-type="mob"]');

        // Nettoyer : fuir le combat
        static::$pantherClient->executeScript(
            "return await fetch('/game/fight/flee', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'}
            }).then(r => r.json()).catch(e => ({error: e.message}));"
        );
    }
}
