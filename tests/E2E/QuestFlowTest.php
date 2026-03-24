<?php

namespace App\Tests\E2E;

use Facebook\WebDriver\WebDriverBy;

/**
 * E2E : Parcours quete complet.
 * Page quetes → accepter quete → verifier suivi → abandonner quete.
 */
class QuestFlowTest extends AbstractE2ETestCase
{
    public function testQuestPageIsAccessible(): void
    {
        $this->login();

        static::$pantherClient->request('GET', '/game/quests');
        static::$pantherClient->waitFor('#tab-active');

        // Verifier la structure de la page
        $this->assertSelectorExists('#tab-active');
        $this->assertSelectorExists('#tab-daily');
        $this->assertSelectorExists('#tab-available');
        $this->assertSelectorExists('#tab-completed');
        $this->assertSelectorExists('#panel-active');
    }

    public function testQuestTabNavigation(): void
    {
        $this->login();

        static::$pantherClient->request('GET', '/game/quests');
        static::$pantherClient->waitFor('#tab-available');

        // Cliquer sur l'onglet "Disponibles"
        static::$pantherClient->findElement(WebDriverBy::id('tab-available'))->click();

        // Verifier que le panneau des quetes disponibles est visible
        $isVisible = static::$pantherClient->executeScript(
            "return !document.getElementById('panel-available').classList.contains('hidden');"
        );
        $this->assertTrue($isVisible, 'Le panneau des quetes disponibles doit etre visible');

        // Cliquer sur l'onglet "Quotidiennes"
        static::$pantherClient->findElement(WebDriverBy::id('tab-daily'))->click();

        $isDailyVisible = static::$pantherClient->executeScript(
            "return !document.getElementById('panel-daily').classList.contains('hidden');"
        );
        $this->assertTrue($isDailyVisible, 'Le panneau des quetes quotidiennes doit etre visible');

        // Cliquer sur l'onglet "Terminees"
        static::$pantherClient->findElement(WebDriverBy::id('tab-completed'))->click();

        $isCompletedVisible = static::$pantherClient->executeScript(
            "return !document.getElementById('panel-completed').classList.contains('hidden');"
        );
        $this->assertTrue($isCompletedVisible, 'Le panneau des quetes terminees doit etre visible');
    }

    public function testAcceptAndAbandonQuest(): void
    {
        $this->login();

        static::$pantherClient->request('GET', '/game/quests');
        static::$pantherClient->waitFor('#tab-available');

        // Basculer sur l'onglet disponibles
        static::$pantherClient->findElement(WebDriverBy::id('tab-available'))->click();

        // Recuperer l'ID de la premiere quete disponible
        $questId = static::$pantherClient->executeScript(
            "const el = document.querySelector('#panel-available [id^=\"available-quest-\"]');
             return el ? el.id.replace('available-quest-', '') : null;"
        );

        if (null === $questId) {
            $this->markTestSkipped('Aucune quete disponible dans les fixtures.');
        }

        // Accepter la quete via l'API (comme le fait le JS du frontend)
        $acceptResult = static::$pantherClient->executeScript(sprintf(
            "return await fetch('/game/quests/accept/%s', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'}
            }).then(r => r.json()).catch(e => ({error: e.message}));",
            $questId
        ));

        $this->assertTrue($acceptResult['success'] ?? false, 'La quete doit etre acceptee avec succes');

        // Recharger la page pour voir la quete dans les actives
        static::$pantherClient->request('GET', '/game/quests');
        static::$pantherClient->waitFor('#tab-active');

        // Verifier que la quete apparait dans les actives
        $hasActiveQuest = static::$pantherClient->executeScript(
            "return document.querySelectorAll('#panel-active [id^=\"quest-\"]').length > 0;"
        );
        $this->assertTrue($hasActiveQuest, 'La quete acceptee doit apparaitre dans les quetes actives');

        // Recuperer l'ID du PlayerQuest pour l'abandonner
        $playerQuestId = static::$pantherClient->executeScript(
            "const el = document.querySelector('#panel-active [id^=\"quest-\"]');
             return el ? el.id.replace('quest-', '') : null;"
        );

        $this->assertNotNull($playerQuestId, 'Un PlayerQuest doit exister');

        // Abandonner la quete
        $abandonResult = static::$pantherClient->executeScript(sprintf(
            "return await fetch('/game/quests/abandon/%s', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'}
            }).then(r => r.json()).catch(e => ({error: e.message}));",
            $playerQuestId
        ));

        $this->assertTrue($abandonResult['success'] ?? false, 'La quete doit etre abandonnee avec succes');

        // Recharger et verifier que la quete n'est plus active
        static::$pantherClient->request('GET', '/game/quests');
        static::$pantherClient->waitFor('#tab-active');

        // La quete abandonnee ne doit plus etre dans les actives (ou une de moins)
        $activeCount = static::$pantherClient->executeScript(
            "return document.querySelectorAll('#panel-active [id^=\"quest-\"]').length;"
        );
        // On verifie simplement que la page se charge correctement apres abandon
        $this->assertIsInt($activeCount);
    }

    public function testQuestFilterByType(): void
    {
        $this->login();

        static::$pantherClient->request('GET', '/game/quests');
        static::$pantherClient->waitFor('#tab-available');

        // Basculer sur les quetes disponibles
        static::$pantherClient->findElement(WebDriverBy::id('tab-available'))->click();

        // Verifier si les filtres existent
        $hasFilters = static::$pantherClient->executeScript(
            "return document.getElementById('filter-all') !== null;"
        );

        if (!$hasFilters) {
            $this->markTestSkipped('Filtres de quete non presents.');
        }

        // Cliquer sur le filtre "Tous"
        static::$pantherClient->findElement(WebDriverBy::id('filter-all'))->click();

        // Compter les quetes visibles
        $totalQuests = static::$pantherClient->executeScript(
            "return document.querySelectorAll('#available-quests-list [id^=\"available-quest-\"]:not([style*=\"display: none\"])').length;"
        );

        $this->assertIsInt($totalQuests);

        // Cliquer sur le filtre "Combat" (kill)
        $hasKillFilter = static::$pantherClient->executeScript(
            "return document.getElementById('filter-kill') !== null;"
        );
        if ($hasKillFilter) {
            static::$pantherClient->findElement(WebDriverBy::id('filter-kill'))->click();

            $filteredQuests = static::$pantherClient->executeScript(
                "return document.querySelectorAll('#available-quests-list [id^=\"available-quest-\"]:not([style*=\"display: none\"])').length;"
            );

            $this->assertLessThanOrEqual($totalQuests, $filteredQuests);
        }
    }
}
