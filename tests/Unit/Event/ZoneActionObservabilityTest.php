<?php

namespace App\Tests\Unit\Event;

use PHPUnit\Framework\TestCase;

/**
 * Loi transverse : une action de zone qui remet quelque chose au joueur doit
 * emettre un evenement de domaine (ZON-38).
 *
 * **Le defaut que cette loi ferme.** Avant le pivot PBBG, la recolte vivait dans
 * `HarvestManager` et emettait `SpotHarvestEvent` ; l'influence de guilde, les
 * succes et les quetes s'y abonnaient. ZON-21 a deplace la boucle dans
 * `GatherService`, sur les filons declares de la zone — et l'abonnement est
 * reste accroche a l'ancien chemin, devenu inatteignable depuis l'interface.
 *
 * Resultat : pendant toute la duree du pivot, **recolter ne rapportait aucune
 * influence de guilde.** Aucune erreur, aucun log, aucune difference visible
 * avec une recolte qui aurait compte. Le joueur recoltait, le journal
 * enregistrait, la guilde ne recevait rien.
 *
 * Un observateur qui ne recoit plus rien ne se plaint jamais. C'est pourquoi la
 * verification porte sur l'**emetteur** : si une action de zone donne des
 * ressources, de l'or ou de la progression sans rien annoncer, elle est
 * inobservable, et tout ce qui devrait en decouler mourra en silence le jour ou
 * on l'y branchera.
 *
 * La liste `NOT_YET_OBSERVABLE` rend cette dette **comptable** : elle ne doit
 * que decroitre. Une entree qui gagne son evenement en sort ;
 * `testNoStaleException` s'en assure.
 *
 * **Complementaire de {@see DomainEventDispatchGuardTest}**, pas redondant avec
 * lui. Celui-la interdit un evenement **sans emetteur** ; celui-ci interdit une
 * action **sans evenement**. C'est pour cela que le premier n'a rien vu ici :
 * `SpotHarvestEvent` avait bien un emetteur — `HarvestManager` — mais plus
 * aucun appelant depuis l'interface. Un chemin de code vivant sur le papier et
 * mort a l'usage passe entre les deux mailles si l'on n'en tend qu'une.
 */
class ZoneActionObservabilityTest extends TestCase
{
    /**
     * Services d'action de zone qui remettent quelque chose au joueur.
     *
     * @var array<string, string> classe => ce qu'elle remet
     */
    private const ZONE_ACTION_SERVICES = [
        'GatherService' => 'des unites de ressource prelevees sur un filon',
        'ExploreService' => 'des decouvertes, des objets et de la progression',
        'ExpeditionService' => 'des gils et des objets a la releve',
        'TimeTrialService' => 'des recompenses de parcours chronometre',
        'ZoneBossService' => 'le butin d\'un assaut de boss',
        'ZoneTravelService' => 'une arrivee — la position elle-meme',
    ];

    /**
     * Actions encore inobservables, et le jalon qui les reconnectera.
     *
     * Ce ne sont pas des oublis : ce sont les autres victimes du meme
     * deplacement de boucle, trouvees en reparant la recolte. Les reconnecter
     * demande de decider, pour chacune, quel indice de foyer et quelle activite
     * d'influence elles nourrissent — ce qui appartient a FOY-02 et non ici.
     *
     * @var array<string, string>
     */
    private const NOT_YET_OBSERVABLE = [
        'ExploreService' => 'FOY-02 — l\'exploration nourrira le Savoir, pas le Negoce ; l\'indice reste a acter',
        'ExpeditionService' => 'FOY-02 — une releve d\'expedition depose-t-elle dans la zone d\'origine ou d\'arrivee ?',
        'TimeTrialService' => 'FOY-02 — parcours chronometre : recompense de maitrise, pas de frequentation',
        'ZoneBossService' => 'FOY-02 — l\'assaut de boss nourrira la Guerre, en meme temps que le kill',
    ];

    /**
     * @return list<string>
     */
    private function dispatchingServices(): array
    {
        $dispatching = [];
        foreach (array_keys(self::ZONE_ACTION_SERVICES) as $class) {
            $path = \dirname(__DIR__, 3) . '/src/GameEngine/Zone/' . $class . '.php';
            self::assertFileExists($path, sprintf('Le service "%s" a disparu : mettez la loi a jour.', $class));

            if (str_contains((string) file_get_contents($path), '->dispatch(')) {
                $dispatching[] = $class;
            }
        }

        return $dispatching;
    }

    public function testEveryZoneActionIsObservableOrExplicitlyExcused(): void
    {
        $silent = array_values(array_diff(
            array_keys(self::ZONE_ACTION_SERVICES),
            $this->dispatchingServices(),
            array_keys(self::NOT_YET_OBSERVABLE),
        ));

        self::assertSame([], $silent, sprintf(
            "Ces actions de zone remettent quelque chose au joueur sans rien annoncer : %s.\n"
            . 'Emettez un evenement de domaine, ou inscrivez-les dans NOT_YET_OBSERVABLE avec la raison.',
            implode(', ', $silent),
        ));
    }

    /**
     * La dette ne doit que decroitre : une action qui a gagne son evenement doit
     * sortir de la liste, sinon l'exception survit a sa raison d'etre et finit
     * par excuser une regression.
     */
    public function testNoStaleException(): void
    {
        $stale = array_values(array_intersect(
            array_keys(self::NOT_YET_OBSERVABLE),
            $this->dispatchingServices(),
        ));

        self::assertSame([], $stale, sprintf(
            'Ces actions emettent desormais un evenement : retirez-les de NOT_YET_OBSERVABLE (%s).',
            implode(', ', $stale),
        ));
    }

    /**
     * La recolte est la boucle la plus jouee du modele zone. Son observabilite
     * n'est pas negociable, et elle ne doit jamais pouvoir rejoindre la liste
     * des exceptions.
     */
    public function testGatheringIsNeverAllowedToGoSilentAgain(): void
    {
        self::assertArrayNotHasKey('GatherService', self::NOT_YET_OBSERVABLE);
        self::assertContains('GatherService', $this->dispatchingServices());
    }
}
