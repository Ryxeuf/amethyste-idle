<?php

namespace App\Tests\Integration\Settlement;

use App\GameEngine\Settlement\SettlementGate;
use App\GameEngine\Settlement\SettlementServiceDirectory;
use App\Tests\Integration\AbstractIntegrationTestCase;
use Symfony\Component\Routing\RouterInterface;

/**
 * Gate ↔ routeur : un service annonce mene quelque part (REP-04).
 *
 * L'audit du 2026-07-29 avait nomme le defaut sur l'Autel d'eveil : *« gate dans
 * `settlements.yaml` mais **absent du routeur** — un service “ouvert” sans
 * ecran »*. Le panneau de foyer l'annoncait a la Metropole, et il ne menait a
 * rien.
 *
 * Le defaut est **muet par nature** : il ne casse aucune page, il promet
 * seulement quelque chose qui n'existe pas — et personne ne s'en apercoit avant
 * qu'un serveur n'atteigne le rang, c'est-a-dire des mois plus tard.
 *
 * Ce contrat le ferme. Les promesses **restantes** sont nommees, une par une,
 * avec le jalon qui les livrera : *une promesse assumee est une decision, une
 * promesse oubliee est un bug*.
 */
class GatedServiceRoutingTest extends AbstractIntegrationTestCase
{
    /**
     * Les services gates qui n'ont pas encore d'ecran, et le jalon attendu.
     *
     * Cliquet : cette liste ne peut que **retrecir**. Un service qui s'y
     * ajouterait sans raison ecrite serait exactement le defaut qu'on ferme.
     *
     * @var array<string, string>
     */
    private const AWAITING_THEIR_SCREEN = [
        'rented_stall' => 'ECO Piste D — l\'etal loue',
    ];

    /**
     * Tout service gate mene a une route reelle, ou figure dans la liste des
     * promesses assumees.
     */
    public function testEveryGatedServiceEitherRoutesOrIsADeclaredPromise(): void
    {
        /** @var SettlementGate $gate */
        $gate = self::getContainer()->get(SettlementGate::class);

        $services = array_keys($gate->services());
        self::assertNotSame([], $services, 'Aucun service gate : le contrat ne mesure rien.');

        $dangling = [];
        foreach ($services as $service) {
            if (\array_key_exists($service, self::AWAITING_THEIR_SCREEN)) {
                continue;
            }

            if ($this->routeOf($service) === null) {
                $dangling[] = $service;
            }
        }

        self::assertSame([], $dangling, sprintf(
            "Ces services sont gates sans mener nulle part : %s.\n"
            . 'Un service annonce par le panneau de foyer doit ouvrir un ecran, ou etre declare comme promesse.',
            implode(', ', $dangling),
        ));
    }

    /**
     * Et l'inverse : une promesse tenue doit sortir de la liste.
     *
     * Sans cette moitie, la liste grossirait en silence et finirait par
     * autoriser exactement ce qu'elle interdit.
     */
    public function testAKeptPromiseLeavesTheList(): void
    {
        $kept = [];
        foreach (self::AWAITING_THEIR_SCREEN as $service => $milestone) {
            if ($this->routeOf($service) !== null) {
                $kept[] = sprintf('%s (%s)', $service, $milestone);
            }
        }

        self::assertSame([], $kept, sprintf(
            'Ces promesses sont tenues : retirez-les de AWAITING_THEIR_SCREEN — %s.',
            implode(', ', $kept),
        ));
    }

    /**
     * Une route declaree par le repertoire existe vraiment.
     *
     * L'autre sens du meme defaut : le repertoire peut nommer une route que
     * personne n'a ecrite, et le panneau de foyer produirait alors une erreur
     * de generation d'URL au lieu d'un lien.
     */
    public function testEveryDeclaredRouteExists(): void
    {
        /** @var RouterInterface $router */
        $router = self::getContainer()->get(RouterInterface::class);

        foreach (SettlementServiceDirectory::routes() as $service => $route) {
            self::assertNotNull(
                $router->getRouteCollection()->get($route),
                sprintf('Le service « %s » pointe sur la route « %s », qui n\'existe pas.', $service, $route),
            );
        }
    }

    /**
     * L'Autel, nommement : c'est lui que l'audit avait trouve, et il doit
     * desormais mener quelque part.
     */
    public function testTheAwakeningAltarHasItsScreen(): void
    {
        self::assertNotNull($this->routeOf('awakening_altar'), 'L\'Autel est redevenu une promesse.');
    }

    /**
     * La route d'un service, **lue dans le repertoire** et jamais recopiee ici.
     */
    private function routeOf(string $service): ?string
    {
        /** @var RouterInterface $router */
        $router = self::getContainer()->get(RouterInterface::class);

        $route = SettlementServiceDirectory::routes()[$service] ?? null;

        return $route !== null && $router->getRouteCollection()->get($route) !== null ? $route : null;
    }
}
