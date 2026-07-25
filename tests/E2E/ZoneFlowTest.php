<?php

namespace App\Tests\E2E;

use Facebook\WebDriver\WebDriverBy;

/**
 * E2E : ecran de zone (ZON-23).
 *
 * Remplace la couverture perdue avec les E2E carte, supprimes par ZON-21a :
 * l'ecran de zone est l'ecran principal du modele PBBG et n'etait plus couvert
 * du tout en bout-en-bout.
 *
 * Perimetre : le rendu reel (Twig + Turbo + navigateur) et les actions qui
 * n'engagent pas le joueur dans un etat long. Le **demarrage effectif d'un
 * voyage** n'est volontairement pas declenche ici : il immobiliserait le joueur
 * partage par la suite E2E pendant plusieurs minutes (la plus courte liaison du
 * graphe dure 5 min). Les transitions d'etat du voyage sont couvertes cote
 * fonctionnel (`ZoneControllerTest::testTravelStartsAndRedirectsWithSuccessFlash`
 * et `testIndexExposesTravelStateWhileTraveling`).
 */
class ZoneFlowTest extends AbstractE2ETestCase
{
    public function testZoneScreenShowsZoneAndResources(): void
    {
        $this->login();
        $this->resolvePendingFight();

        static::$pantherClient->request('GET', '/game/zone');
        $this->waitForSelector('[data-testid="zone-header"]');
        $this->waitForTurbo();

        $this->assertSelectorExists('[data-testid="zone-name"]');
        $this->assertSelectorExists('[data-testid="zone-energy"]');

        $zoneName = static::$pantherClient
            ->findElement(WebDriverBy::cssSelector('[data-testid="zone-name"]'))
            ->getText();
        $this->assertNotSame('', trim($zoneName), 'La zone courante doit etre nommee.');
    }

    public function testZoneOffersTravelConnections(): void
    {
        $this->login();
        $this->resolvePendingFight();

        static::$pantherClient->request('GET', '/game/zone');
        $this->waitForSelector('[data-testid="zone-header"]');
        $this->waitForTurbo();

        $forms = static::$pantherClient->findElements(
            WebDriverBy::cssSelector('[data-testid="zone-travel-form"]')
        );

        if ([] === $forms) {
            $this->markTestSkipped('Le joueur est en voyage ou la zone n\'a aucune connexion ouverte.');
        }

        // Chaque connexion voyageable poste vers l'endpoint de voyage avec un
        // jeton CSRF : c'est la seule sortie de zone du modele PBBG.
        $action = $forms[0]->getAttribute('action');
        $this->assertStringContainsString('/game/zone/travel/', (string) $action);
        $this->assertSelectorExists('[data-testid="zone-travel-button"]');
    }

    public function testExploreKeepsPlayerInGame(): void
    {
        $this->login();
        $this->resolvePendingFight();

        static::$pantherClient->request('GET', '/game/zone');
        $this->waitForSelector('[data-testid="zone-header"]');
        $this->waitForTurbo();

        $exploreButtons = static::$pantherClient->findElements(
            WebDriverBy::cssSelector('[data-testid="zone-explore-button"]')
        );

        if ([] === $exploreButtons) {
            $this->markTestSkipped("L'action Explorer n'est pas disponible dans cette zone.");
        }

        $exploreButtons[0]->click();
        $this->waitForTurbo();

        // Explorer tire un evenement : rencontre (redirection combat) ou
        // resultat affiche sur l'ecran de zone. Aucune erreur serveur possible.
        $url = static::$pantherClient->getCurrentURL();
        $this->assertTrue(
            str_contains($url, '/game/zone') || str_contains($url, '/game/fight'),
            sprintf('Explorer doit rester dans le jeu (zone ou combat), URL obtenue : %s', $url)
        );

        $this->resolvePendingFight();
    }
}
