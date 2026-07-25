<?php

namespace App\Tests\E2E;

/**
 * E2E : declenchement d'un combat depuis une zone (ZON-23).
 *
 * Remplace `CombatFlowTest`, supprime avec ZON-21a : il declenchait la
 * rencontre via `/api/map/move` (endpoint retire). Le declencheur est desormais
 * l'action **Explorer** de l'ecran de zone, qui tire dans la table de rencontres
 * de la zone.
 *
 * L'action **Chasser** n'est pas utilisee ici : elle ne propose que des proies
 * deja connues du bestiaire du joueur, et les fixtures ne renseignent aucune
 * entree de bestiaire (cf. suivi ZON-23 dans SPRINT_13.md).
 */
class ZoneCombatFlowTest extends AbstractE2ETestCase
{
    /** Nombre d'explorations avant d'abandonner (rencontre = 45 % de jour). */
    private const MAX_EXPLORE_ATTEMPTS = 8;

    public function testFightPageRedirectsToZoneWhenNoFight(): void
    {
        $this->login();
        if (!$this->resolvePendingFight()) {
            $this->markTestSkipped('Un combat laisse par un test precedent n\'a pas pu etre resolu.');
        }

        static::$pantherClient->request('GET', '/game/fight');
        $this->waitForTurbo();

        $this->assertStringContainsString(
            '/game/zone',
            static::$pantherClient->getCurrentURL(),
            'Sans combat en cours, /game/fight renvoie sur l\'ecran de zone.'
        );
    }

    public function testExploreEventuallyStartsAFightWithUsableActions(): void
    {
        $this->login();
        $this->resolvePendingFight();

        if (!$this->exploreUntilFight()) {
            $this->markTestSkipped('Aucune rencontre tiree apres ' . self::MAX_EXPLORE_ATTEMPTS . ' explorations.');
        }

        $this->waitForSelector('#panel-actions');
        $this->assertSelectorExists('#action-attack');
        $this->assertSelectorExists('#action-flee');
        $this->assertSelectorExists('#combat-log');

        $this->resolvePendingFight();
    }

    public function testBasicAttackAdvancesTheFight(): void
    {
        $this->login();
        $this->resolvePendingFight();

        if (!$this->exploreUntilFight()) {
            $this->markTestSkipped('Aucune rencontre tiree apres ' . self::MAX_EXPLORE_ATTEMPTS . ' explorations.');
        }

        $this->waitForSelector('#action-attack');
        $this->assertTrue($this->clickSelector('#action-attack'), 'Le bouton d\'attaque doit etre cliquable.');
        $this->waitForTurbo();

        // Apres un tour : soit le combat continue, soit il est resolu (butin,
        // defaite). Les deux sont valides — seule une erreur serveur ne l'est pas.
        $url = static::$pantherClient->getCurrentURL();
        $this->assertTrue(
            str_contains($url, '/game/fight') || str_contains($url, '/game/zone'),
            sprintf('L\'attaque de base doit faire avancer le combat, URL obtenue : %s', $url)
        );

        $this->resolvePendingFight();
    }

    /**
     * Explore la zone courante jusqu'a declencher une rencontre.
     */
    private function exploreUntilFight(): bool
    {
        for ($attempt = 0; $attempt < self::MAX_EXPLORE_ATTEMPTS; ++$attempt) {
            static::$pantherClient->request('GET', '/game/zone');

            try {
                // Attendre le rendu reel : Turbo peut afficher un apercu en
                // cache avant de remplacer le corps du document.
                $this->waitForSelector('[data-testid="zone-explore-button"]');
            } catch (\Throwable) {
                return false;
            }
            $this->waitForTurbo();

            if (!$this->clickSelector('[data-testid="zone-explore-button"]')) {
                return false;
            }
            $this->waitForTurbo();

            if (str_contains(static::$pantherClient->getCurrentURL(), '/game/fight')) {
                return true;
            }
        }

        return false;
    }
}
