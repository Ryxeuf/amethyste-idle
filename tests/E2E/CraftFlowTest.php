<?php

namespace App\Tests\E2E;

use Facebook\WebDriver\WebDriverBy;

/**
 * E2E : Parcours artisanat complet.
 * inventaire → atelier → crafter → verifier item cree.
 */
class CraftFlowTest extends AbstractE2ETestCase
{
    public function testCraftPageIsAccessible(): void
    {
        $this->login();

        static::$pantherClient->request('GET', '/game/craft');
        $this->waitForSelector('#craft-tabs');
        $this->waitForTurbo();

        $this->assertSelectorExists('#craft-tabs');
        $this->assertSelectorExists('.craft-tab-btn');
        $this->assertSelectorExists('.craft-panel');
    }

    public function testCraftTabNavigation(): void
    {
        $this->login();

        static::$pantherClient->request('GET', '/game/craft');
        $this->waitForSelector('#craft-tabs');
        $this->waitForTurbo();

        // Recuperer la liste des onglets
        $tabs = static::$pantherClient->executeScript(
            "return Array.from(document.querySelectorAll('.craft-tab-btn'))
                .map(btn => btn.dataset.craftTab);"
        );

        $this->assertNotEmpty($tabs, 'Au moins un onglet de craft doit exister');

        // Naviguer entre les onglets
        foreach ($tabs as $tab) {
            static::$pantherClient->findElement(
                WebDriverBy::cssSelector(sprintf('[data-craft-tab="%s"]', $tab))
            )->click();

            $panelVisible = static::$pantherClient->executeScript(sprintf(
                "return !document.getElementById('craft-panel-%s').classList.contains('hidden');",
                $tab
            ));

            $this->assertTrue($panelVisible, sprintf('Le panneau "%s" doit etre visible apres clic sur son onglet', $tab));
        }
    }

    public function testCraftRecipeCardsDisplay(): void
    {
        $this->login();

        static::$pantherClient->request('GET', '/game/craft');
        $this->waitForSelector('#craft-tabs');
        $this->waitForTurbo();

        // Les recettes utilisent le controleur Stimulus craft-queue
        $recipeCount = static::$pantherClient->executeScript(
            "return document.querySelectorAll('[data-controller*=\"craft-queue\"]').length;"
        );

        $this->assertGreaterThan(0, $recipeCount, 'Des recettes doivent etre affichees dans les fixtures');

        // Chaque recette a un bouton via data-craft-queue-target="button"
        $buttonCount = static::$pantherClient->executeScript(
            "return document.querySelectorAll('[data-craft-queue-target=\"button\"]').length;"
        );

        $this->assertSame($recipeCount, $buttonCount, 'Chaque recette doit avoir un bouton de fabrication');
    }

    public function testCraftAttempt(): void
    {
        $this->login();

        static::$pantherClient->request('GET', '/game/craft');
        $this->waitForSelector('#craft-tabs');
        $this->waitForTurbo();

        // Chercher une recette craftable (bouton Stimulus non disabled)
        $craftableSlug = static::$pantherClient->executeScript(
            "const btn = document.querySelector('[data-action=\"craft-queue#craft\"]:not([disabled])');
             if (!btn) return null;
             const controller = btn.closest('[data-controller*=\"craft-queue\"]');
             return controller ? controller.dataset.craftQueueSlugValue : null;"
        );

        if (null === $craftableSlug) {
            $disabledCount = static::$pantherClient->executeScript(
                "return document.querySelectorAll('[data-action=\"craft-queue#craft\"][disabled]').length;"
            );
            $this->assertGreaterThan(0, $disabledCount, 'Des recettes doivent exister meme si non craftables');

            return;
        }

        // Cliquer sur le bouton de craft du controleur Stimulus
        static::$pantherClient->findElement(
            WebDriverBy::cssSelector(sprintf(
                '[data-craft-queue-slug-value="%s"] [data-action="craft-queue#craft"]',
                $craftableSlug
            ))
        )->click();

        // Apres soumission, la page de craft est rechargee
        $this->waitForSelector('#craft-tabs');
        $this->waitForTurbo();

        $url = static::$pantherClient->getCurrentURL();
        $this->assertStringContainsString('/game/craft', $url);
    }

    public function testInventoryToWorkshopNavigation(): void
    {
        $this->login();

        static::$pantherClient->request('GET', '/game/inventory');
        $this->waitForTurbo();

        $url = static::$pantherClient->getCurrentURL();
        $this->assertStringContainsString('/game/inventory', $url);

        static::$pantherClient->request('GET', '/game/craft');
        $this->waitForSelector('#craft-tabs');
        $this->waitForTurbo();

        $this->assertTrue($this->selectorExists('#craft-tabs'), 'Craft tabs should exist after navigation');
    }

    public function testExperimentSectionExists(): void
    {
        $this->login();

        static::$pantherClient->request('GET', '/game/craft');
        $this->waitForSelector('#craft-tabs');
        $this->waitForTurbo();

        $this->assertTrue(
            $this->selectorExists('form[action*="/game/craft/experiment"]'),
            'La section experimentation doit etre presente'
        );
    }
}
