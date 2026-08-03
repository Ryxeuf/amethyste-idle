<?php

namespace App\Tests\Unit\Scheduler;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Yaml\Yaml;

/**
 * Le calendrier est-il reellement consomme apres un deploiement ?
 * (tache 134, jalon F.0)
 *
 * `ScheduledCommandTest` garde le **contenu** du calendrier : toute commande y
 * est planifiee ou declaree manuelle. Il ne dit rien de ce qui l'execute — et
 * c'est precisement la que le jeu a echoue pendant des mois : le calendrier
 * etait juste, complet, et **personne ne le lisait**.
 *
 * Ce test garde donc le cablage de deploiement. Il ne demande pas Docker : les
 * quatre pieges de l'activation laissent tous une trace dans un fichier
 * versionne, et c'est cette trace qu'on verifie.
 *
 * L'echec que ces assertions previennent est silencieux par nature : rien ne
 * casse, rien ne leve, le monde se fige simplement — les encheres n'expirent
 * plus (l'escrow reste immobilise), les boutiques ne se reapprovisionnent plus,
 * les filons ne repoussent plus, la semaine ne tourne plus.
 */
class SchedulerWorkerDeploymentTest extends TestCase
{
    private const TRANSPORT = 'scheduler_default';

    private function projectDir(): string
    {
        return \dirname(__DIR__, 3);
    }

    private function read(string $relativePath): string
    {
        $path = $this->projectDir() . '/' . $relativePath;
        $this->assertFileExists($path, sprintf('%s a disparu : le worker ne peut plus demarrer.', $relativePath));

        return (string) file_get_contents($path);
    }

    /**
     * L'entrypoint du worker, prive de ses commentaires.
     *
     * Le fichier explique longuement ce que fait l'entrypoint **du web** —
     * migrations, `tailwind:build`, `asset-map:compile` — pour dire pourquoi
     * celui-ci existe. Sans ce filtre, les assertions ci-dessous liraient ces
     * explications comme du code et se tromperaient dans les deux sens : elles
     * verraient un `messenger:consume` avant l'effacement de l'arriere, et une
     * compilation d'assets qui n'a jamais lieu.
     */
    private function schedulerEntrypointCode(): string
    {
        $lines = array_filter(
            explode("\n", $this->read('frankenphp/scheduler-entrypoint.sh')),
            static fn (string $line): bool => !str_starts_with(ltrim($line), '#'),
        );

        return implode("\n", $lines);
    }

    /**
     * @return array<string, mixed>
     */
    private function service(string $composeFile, string $name): array
    {
        /** @var array{services?: array<string, array<string, mixed>>} $parsed */
        $parsed = Yaml::parse($this->read($composeFile));

        $this->assertArrayHasKey(
            $name,
            $parsed['services'] ?? [],
            sprintf('Le service « %s » a disparu de %s.', $name, $composeFile),
        );

        return $parsed['services'][$name];
    }

    /**
     * Un service consomme le transport du calendrier.
     *
     * C'est F.0 lui-meme : sans consommateur, `symfony/scheduler` publie dans
     * le vide.
     */
    public function testAServiceConsumesTheScheduleTransport(): void
    {
        $worker = $this->service('compose.yaml', 'worker');
        $entrypoint = $this->schedulerEntrypointCode();

        $this->assertSame(
            ['scheduler-entrypoint'],
            $worker['entrypoint'] ?? null,
            'Le worker doit passer par son entrypoint dedie.',
        );
        $this->assertStringContainsString(
            'messenger:consume ' . self::TRANSPORT,
            $entrypoint,
            'L\'entrypoint du worker doit consommer le transport du calendrier.',
        );
    }

    /**
     * Le transport consomme est bien celui que le calendrier alimente.
     *
     * `#[AsSchedule]` sans argument nomme le calendrier « default », d'ou
     * `scheduler_default`. Nommer le calendrier autrement sans corriger
     * l'entrypoint rendrait le worker inutile — il consommerait un transport
     * vide, sans la moindre erreur.
     */
    public function testTheConsumedTransportMatchesTheScheduleName(): void
    {
        $provider = $this->read('src/Scheduler/DefaultScheduleProvider.php');

        $this->assertMatchesRegularExpression(
            '/#\[AsSchedule\]\s/',
            $provider,
            'Le calendrier n\'est plus nomme « default » : l\'entrypoint consomme alors un transport vide.',
        );
    }

    /**
     * Piege n° 1 — l'arriere de loyers est efface avant toute consommation.
     *
     * `extendRent()` repart de l'echeance precedente et ne rattrape qu'une
     * periode par execution : sans cette etape, une base laissee sans
     * planificateur pendant six mois se ferait prelever 26 semaines de loyer,
     * une par jour, jusqu'a rattrapage.
     */
    public function testTheRentBacklogIsClearedBeforeConsuming(): void
    {
        $entrypoint = $this->schedulerEntrypointCode();

        $reset = strpos($entrypoint, 'app:economy:rent-backlog-reset');
        $consume = strpos($entrypoint, 'messenger:consume');

        $this->assertNotFalse($reset, 'L\'entrypoint doit effacer l\'arriere de loyers.');
        $this->assertNotFalse($consume);
        $this->assertLessThan(
            $consume,
            $reset,
            'L\'arriere doit etre efface **avant** la premiere consommation, pas apres.',
        );
    }

    /**
     * ... et avec un seuil, sinon l'appel automatique devient une fuite.
     *
     * L'entrypoint tourne a **chaque** demarrage du worker. Sans seuil, un
     * redemarrage a 00 h 10 effacerait une echeance tombee a 00 h 00 que la
     * tache de 00 h 15 s'appretait a prelever : le loyer ne rentrerait jamais.
     */
    public function testTheAutomaticResetIsBoundedByAThreshold(): void
    {
        $this->assertMatchesRegularExpression(
            '/app:economy:rent-backlog-reset[^\n]*--min-periods=[1-9]/',
            $this->schedulerEntrypointCode(),
            'L\'effacement automatique doit porter un seuil : sinon il annule les loyers du jour.',
        );
    }

    /**
     * Piege n° 2 — l'entrypoint du web n'est pas celui du worker.
     *
     * `frankenphp/docker-entrypoint.sh` declenche migrations, `cache:clear`,
     * `tailwind:build` et `asset-map:compile` pour tout argv commencant par
     * `php` ou `bin/console`. Un worker qui l'emprunterait rejouerait les
     * migrations **en concurrence avec le conteneur web** a chaque redemarrage.
     */
    public function testTheWorkerDoesNotReplayMigrationsNorAssets(): void
    {
        $entrypoint = $this->schedulerEntrypointCode();

        foreach (['doctrine:migrations:migrate', 'tailwind:build', 'asset-map:compile'] as $forbidden) {
            $this->assertStringNotContainsString(
                $forbidden,
                $entrypoint,
                sprintf('Le worker ne doit pas executer « %s » : c\'est le role du conteneur web.', $forbidden),
            );
        }
    }

    /**
     * L'entrypoint dedie est bien installe dans l'image.
     *
     * Le stage de prod fait `rm -Rf frankenphp/` : un script seulement present
     * dans les sources ne survivrait pas au build, et le worker demarrerait sur
     * un « executable file not found » — apres le deploiement, en production.
     */
    public function testTheEntrypointIsInstalledInTheImage(): void
    {
        $this->assertMatchesRegularExpression(
            '#COPY[^\n]*frankenphp/scheduler-entrypoint\.sh[^\n]*/usr/local/bin/scheduler-entrypoint#',
            $this->read('Dockerfile'),
            'L\'entrypoint du worker doit etre copie hors de /app, comme celui du web.',
        );
    }

    /**
     * Piege n° 3 — exactement une replique.
     *
     * Le calendrier n'a pas de verrou (jalon F.1 : `Schedule::lock()` n'est pas
     * appele et `symfony/lock` n'est pas installe). A deux repliques, chaque
     * tache se declenche deux fois, et les degats sont economiques et
     * irreversibles : loyers preleves deux fois, recompenses de saison versees
     * deux fois, deux releves de masse monetaire par jour.
     */
    public function testExactlyOneReplicaOfTheWorker(): void
    {
        $worker = $this->service('compose.yaml', 'worker');

        $this->assertSame(
            1,
            $worker['deploy']['replicas'] ?? null,
            'Le worker doit declarer une replique unique tant que le calendrier n\'a pas de verrou (F.1).',
        );
    }

    /**
     * Le worker n'est pas joignable de l'exterieur.
     *
     * Une etiquette Traefik heritee par megarde exposerait au monde un
     * conteneur qui ne sert aucun HTTP — et l'exposerait sur le service
     * `amethyste`, donc dans le round-robin des vraies requetes joueur.
     */
    public function testTheWorkerCarriesNoPublicRoute(): void
    {
        foreach (['compose.yaml', 'compose.prod.yaml', 'compose.override.yaml'] as $file) {
            /** @var array{services?: array<string, array<string, mixed>>} $parsed */
            $parsed = Yaml::parse($this->read($file));
            $worker = $parsed['services']['worker'] ?? null;

            if (null === $worker) {
                continue;
            }

            $this->assertArrayNotHasKey('labels', $worker, sprintf('%s : le worker ne porte aucune etiquette.', $file));
            $this->assertArrayNotHasKey('ports', $worker, sprintf('%s : le worker n\'expose aucun port.', $file));
            $this->assertNotContains(
                'traefik-network',
                (array) ($worker['networks'] ?? []),
                sprintf('%s : le worker reste sur le reseau interne.', $file),
            );
        }
    }

    /**
     * La sonde de sante heritee de l'image est remplacee.
     *
     * L'image porte `HEALTHCHECK curl -f http://localhost:2019/metrics`, l'admin
     * de Caddy. Le worker ne sert pas de HTTP : sans remplacement, il serait
     * **toujours** declare malsain et `docker compose up --wait` echouerait a
     * chaque deploiement — un service neuf qui casse le deploiement du site.
     */
    public function testTheInheritedHttpHealthcheckIsOverridden(): void
    {
        $worker = $this->service('compose.yaml', 'worker');

        /** @var list<string> $test */
        $test = $worker['healthcheck']['test'] ?? [];

        $this->assertNotEmpty($test, 'Le worker doit remplacer la sonde HTTP heritee de l\'image.');
        $this->assertStringNotContainsString('2019', implode(' ', $test));
        $this->assertStringContainsString(
            'messenger:consume',
            implode(' ', $test),
            'La sonde doit verifier que le calendrier est effectivement consomme.',
        );
    }

    /**
     * Le worker ne demarre pas avant que les migrations soient passees.
     *
     * Il partage la base avec le conteneur web, qui est proprietaire des
     * migrations. Sans cette dependance, la premiere tache planifiee d'un
     * deploiement taperait sur le schema d'avant.
     */
    public function testTheWorkerWaitsForTheWebContainer(): void
    {
        $worker = $this->service('compose.yaml', 'worker');

        $this->assertSame(
            'service_healthy',
            $worker['depends_on']['php']['condition'] ?? null,
            'Le worker attend que le conteneur web soit sain — c\'est lui qui joue les migrations.',
        );
        $this->assertSame(
            'service_healthy',
            $worker['depends_on']['database']['condition'] ?? null,
        );
    }

    /**
     * Un deploiement releve le planificateur et le verifie.
     *
     * Sans cette etape, un worker mort survivrait a tous les deploiements sans
     * que rien ne le signale : c'est exactement l'etat dans lequel le jeu a
     * vecu jusqu'ici.
     */
    public function testTheDeployScriptChecksTheScheduler(): void
    {
        $deploy = $this->read('scripts/deploy.sh');

        $this->assertStringContainsString('up -d --wait worker', $deploy, 'Le deploiement doit relever le worker.');
        $this->assertStringContainsString(
            'messenger:consume',
            $deploy,
            'Le deploiement doit verifier que le calendrier est consomme, pas seulement que le conteneur tourne.',
        );
    }

    /**
     * Le calendrier reste sans etat.
     *
     * `Schedule::stateful()` ferait rejouer, au demarrage du worker, tous les
     * declenchements manques depuis le dernier point de reprise. Apres une
     * interruption longue, ce serait la version automatique du desastre que
     * `app:economy:rent-backlog-reset` existe pour eviter : 26 prelevements
     * d'affilee. Un declenchement saute doit rester saute.
     */
    public function testTheScheduleIsNeverReplayed(): void
    {
        $this->assertStringNotContainsString(
            '->stateful(',
            $this->read('src/Scheduler/DefaultScheduleProvider.php'),
            'Un calendrier a etat rejoue les declenchements manques — dont les loyers.',
        );
    }
}
