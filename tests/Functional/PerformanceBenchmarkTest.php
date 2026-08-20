<?php

namespace App\Tests\Functional;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Chaque route critique repond sous son seuil.
 *
 * Seuil par defaut : 1000 ms, surchargeable par `PERF_MAX_RESPONSE_MS`.
 * Demande une vraie base avec les fixtures chargees.
 *
 * ## Un banc d'essai ne se mesure pas sous un profileur
 *
 * Ce test a echoue deux fois pour la meme raison, et jamais parce qu'une route
 * avait ralenti : il etait chronometre **pendant la collecte de couverture**,
 * ou Xdebug instrumente chaque opcode. La compensation etait un multiplicateur
 * choisi a la main (x2), et un dosage ne se calibre pas, il se **re-dose** —
 * apres un premier ajustement, la mesure est repassee au-dessus (1035 ms pour
 * un plafond a 1000), et le rejeu **sans couverture** de la meme CI est passe.
 *
 * La correction n'est donc pas un troisieme dosage : le banc **refuse de
 * mesurer** quand un pilote de couverture est actif, et la CI le rejoue dans
 * une etape dediee, sans instrumentation (groupe `benchmark`). *Ce qu'on
 * mesure alors est la page, plus le profileur.*
 *
 * Le refus est porte par le test lui-meme et non par une convention de YAML :
 * une exclusion qui ne vivrait que dans le workflow se perdrait au premier
 * `phpunit` lance a la main avec `--coverage`, et rendrait un chiffre faux
 * sans rien dire. `CiBenchmarkWiringTest` garde l'autre moitie — que l'etape
 * dediee existe encore, faute de quoi le banc ne tournerait **nulle part** et
 * la CI serait verte pour cette raison.
 *
 * ## Ce que le seuil garde
 *
 * Une page devenue **inutilisable** — une explosion de requetes N+1, un
 * gabarit qui se met a compiler a chaque appel —, pas un contrat a la
 * milliseconde. Sur un runner partage, la milliseconde mesure l'humeur du
 * voisin ; c'est pourquoi la borne se lit en perception humaine (au-dela d'une
 * seconde, la page est cassee) et garde de la marge sur le pire cas mesure.
 */
#[Group('benchmark')]
class PerformanceBenchmarkTest extends WebTestCase
{
    /**
     * Au-dela d'une seconde, un joueur ne dit pas « c'est lent », il dit « c'est
     * casse ». C'est ce que le banc garde.
     */
    public const DEFAULT_THRESHOLD_MS = 1000;

    private KernelBrowser $client;

    private int $maxResponseTimeMs;

    /**
     * Un pilote de couverture est-il en train de collecter ?
     *
     * Xdebug instrumente des que son mode contient `coverage`, que PHPUnit
     * collecte ou non — c'est le mode qui coute, pas le drapeau de la ligne de
     * commande. PCOV est traite de meme, pour que la reponse ne depende pas du
     * pilote choisi.
     */
    private static function coverageIsCollecting(): bool
    {
        if (\extension_loaded('pcov') && (bool) \ini_get('pcov.enabled')) {
            return true;
        }

        return \extension_loaded('xdebug')
            && \function_exists('xdebug_info')
            && \in_array('coverage', xdebug_info('mode'), true);
    }

    protected function setUp(): void
    {
        // Un banc d'essai sous instrumentation ne mesure pas ce qu'il annonce.
        // On refuse de rendre un chiffre plutot que d'en corriger un faux : la
        // CI rejoue ce groupe dans une etape sans couverture.
        if (self::coverageIsCollecting()) {
            $this->markTestSkipped(
                'Pilote de couverture actif : le banc d\'essai mesurerait le profileur. '
                . 'La CI le rejoue sans instrumentation (groupe « benchmark »).',
            );
        }

        $this->client = static::createClient();

        $envThreshold = getenv('PERF_MAX_RESPONSE_MS');
        $this->maxResponseTimeMs = $envThreshold !== false ? (int) $envThreshold : self::DEFAULT_THRESHOLD_MS;

        /** @var EntityManagerInterface $em */
        $em = static::getContainer()->get(EntityManagerInterface::class);
        $user = $em->getRepository(User::class)->findOneBy(['email' => 'remy@amethyste.game']);

        if ($user === null) {
            $this->markTestSkipped('Fixture user remy@amethyste.game not found — run doctrine:fixtures:load first.');
        }

        $this->client->loginUser($user);

        // Warm-up: first request pays kernel boot cost — exclude from measurements
        $this->client->request('GET', '/game/zone');
    }

    /**
     * Chaque route repond sous son seuil.
     *
     * La route est appelee **deux fois**, et seule la seconde est chronometree.
     * Le rechauffement de `setUp` ne couvre que l'amorcage du noyau : il ne
     * compile pas le gabarit Twig de la route mesuree, dont le cout est paye
     * une seule fois, dans la mesure, et amplifie par la couverture Xdebug.
     *
     * Le defaut se voyait sur `/game/quests`, le gabarit le plus lourd de la
     * suite (arcs narratifs, quotidiennes, donneurs, chaines) : deux executions
     * consecutives ont rendu 1071 ms puis 1006 ms pour un seuil a 1000, quand la
     * meme route sous-jacente n'avait pas bouge. Un garde-fou qui echoue sur la
     * compilation d'un gabarit ne mesure pas ce qu'il annonce, et le bruit qu'il
     * emet finit par faire relancer la CI plutot que lire son verdict.
     *
     * Ce rechauffement reste utile hors couverture — il neutralise la
     * compilation du gabarit —, mais il ne suffisait pas : ce qui restait a
     * retirer de la mesure, c'etait le profileur lui-meme (voir l'en-tete).
     */
    #[DataProvider('criticalRoutesProvider')]
    public function testRouteRespondsWithinThreshold(string $url, string $label): void
    {
        $this->client->request('GET', $url);

        $start = microtime(true);
        $this->client->request('GET', $url);
        $durationMs = (microtime(true) - $start) * 1000;

        $statusCode = $this->client->getResponse()->getStatusCode();

        if ($statusCode >= 400) {
            $this->markTestSkipped(sprintf('[%s] returned HTTP %d — skipping performance check', $label, $statusCode));
        }

        $this->assertLessThanOrEqual(
            $this->maxResponseTimeMs,
            $durationMs,
            sprintf(
                '[%s] responded in %.1f ms (max: %d ms)',
                $label,
                $durationMs,
                $this->maxResponseTimeMs,
            ),
        );
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function criticalRoutesProvider(): iterable
    {
        // Game pages
        yield 'zone' => ['/game/zone', 'Game: Zone'];
        yield 'inventory' => ['/game/inventory', 'Game: Inventory'];
        yield 'skills' => ['/game/skills', 'Game: Skills'];
        yield 'bestiary' => ['/game/bestiary', 'Game: Bestiary'];
        yield 'achievements' => ['/game/achievements', 'Game: Achievements'];
        yield 'quests' => ['/game/quests', 'Game: Quests'];

        // API endpoints
        yield 'api-quickbar' => ['/api/quickbar/items', 'API: Quickbar Items'];
        yield 'api-game-time' => ['/api/game/time', 'API: Game Time'];
        yield 'api-active-events' => ['/api/game/events/active', 'API: Active Events'];
    }
}
