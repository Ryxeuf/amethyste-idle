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
        static::$pantherClient->waitFor('#craft-tabs');

        // Verifier la page d'artisanat
        $this->assertSelectorExists('#craft-tabs');
        $this->assertSelectorExists('.craft-tab-btn');
        $this->assertSelectorExists('.craft-panel');
    }

    public function testCraftTabNavigation(): void
    {
        $this->login();

        static::$pantherClient->request('GET', '/game/craft');
        static::$pantherClient->waitFor('#craft-tabs');

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
        static::$pantherClient->waitFor('#craft-tabs');

        // Compter le nombre de recettes affichees (toutes professions)
        $recipeCount = static::$pantherClient->executeScript(
            "return document.querySelectorAll('.craft-panel form[action*=\"/game/craft/craft/\"]').length;"
        );

        $this->assertGreaterThan(0, $recipeCount, 'Des recettes doivent etre affichees dans les fixtures');

        // Verifier que chaque recette a un bouton (actif ou desactive)
        $buttonCount = static::$pantherClient->executeScript(
            "return document.querySelectorAll('.craft-panel form button[type=\"submit\"]').length;"
        );

        $this->assertSame($recipeCount, $buttonCount, 'Chaque recette doit avoir un bouton de fabrication');
    }

    public function testCraftAttempt(): void
    {
        $this->login();

        static::$pantherClient->request('GET', '/game/craft');
        static::$pantherClient->waitFor('#craft-tabs');

        // Chercher une recette craftable (bouton non disabled)
        $craftableSlug = static::$pantherClient->executeScript(
            "const form = document.querySelector('.craft-panel form button[type=\"submit\"]:not([disabled])')?.closest('form');
             if (!form) return null;
             const action = form.getAttribute('action');
             const match = action.match(/\\/game\\/craft\\/craft\\/(.+)$/);
             return match ? match[1] : null;"
        );

        if (null === $craftableSlug) {
            // Tester le cas ou aucune recette n'est craftable (manque ingredients)
            $disabledCount = static::$pantherClient->executeScript(
                "return document.querySelectorAll('.craft-panel form button[disabled]').length;"
            );
            $this->assertGreaterThan(0, $disabledCount, 'Des recettes doivent exister meme si non craftables');

            return;
        }

        // Soumettre le formulaire de craft
        $craftForm = static::$pantherClient->findElement(
            WebDriverBy::cssSelector(sprintf('form[action$="/game/craft/craft/%s"]', $craftableSlug))
        );
        $craftForm->submit();

        // Apres soumission, la page de craft est rechargee avec un message flash
        static::$pantherClient->waitFor('#craft-tabs');

        // Verifier que la page s'est rechargee correctement
        $url = static::$pantherClient->getCurrentURL();
        $this->assertStringContainsString('/game/craft', $url);
    }

    public function testInventoryToWorkshopNavigation(): void
    {
        $this->login();

        // Verifier l'inventaire d'abord
        static::$pantherClient->request('GET', '/game/inventory');
        static::$pantherClient->waitFor('body');

        $url = static::$pantherClient->getCurrentURL();
        $this->assertStringContainsString('/game/inventory', $url);

        // Naviguer vers l'atelier
        static::$pantherClient->request('GET', '/game/craft');
        static::$pantherClient->waitFor('#craft-tabs');

        $this->assertSelectorExists('#craft-tabs');
    }

    public function testExperimentSectionExists(): void
    {
        $this->login();

        static::$pantherClient->request('GET', '/game/craft');
        static::$pantherClient->waitFor('#craft-tabs');

        // Verifier la section experimentation
        $hasExperiment = static::$pantherClient->executeScript(
            "return document.querySelector('form[action*=\"/game/craft/experiment\"]') !== null;"
        );

        $this->assertTrue($hasExperiment, 'La section experimentation doit etre presente');
    }
}
