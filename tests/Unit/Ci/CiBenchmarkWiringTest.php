<?php

namespace App\Tests\Unit\Ci;

use App\Tests\Functional\PerformanceBenchmarkTest;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Yaml\Yaml;

/**
 * Le banc d'essai tourne-t-il reellement, et sans profileur ?
 *
 * `PerformanceBenchmarkTest` **refuse de mesurer** quand un pilote de
 * couverture est actif — un chiffre faux vaut moins que pas de chiffre. Ce
 * refus a un cout : il ouvre la porte a l'echec le plus silencieux qui soit,
 * celui ou le banc ne tourne **nulle part** et ou la CI est verte pour cette
 * raison. Il suffirait de supprimer l'etape dediee ; plus rien ne le dirait,
 * puisque la course de couverture l'exclut deja par son groupe.
 *
 * C'est la meme famille de garde-fou que `SchedulerWorkerDeploymentTest` : le
 * calendrier y etait juste, complet, et personne ne le lisait. Ici le banc
 * serait juste, complet, et personne ne le lancerait.
 *
 * Trois choses sont donc verifiees, et aucune ne demande de lancer la CI :
 * l'etape existe et vise le groupe ; elle coupe l'instrumentation ; et la
 * course de couverture, elle, l'exclut — sinon on paierait la mesure deux fois
 * pour n'en garder qu'une de fausse.
 */
class CiBenchmarkWiringTest extends TestCase
{
    private const GROUP = 'benchmark';

    /**
     * `--exclude-group a,b` est **deprecie** depuis PHPUnit 11 et disparaît en
     * 12 ; le lanceur emet alors un avertissement, et un avertissement du
     * lanceur suffit a rendre la course rouge. C'est ce qui a fait echouer la
     * premiere version de ce correctif : la CI ne tombait plus sur une mesure
     * fausse, elle tombait sur la facon de l'exclure.
     *
     * Le drapeau se repete donc, une fois par groupe — et le garde-fou le
     * verifie, faute de quoi la forme deprecie reviendrait au premier
     * copier-coller et la CI redeviendrait rouge pour une raison qui n'a rien a
     * voir avec le code teste.
     */
    private const REPEATED_FLAG_ONLY = '/--exclude-group\s+[\w-]+,/';

    /**
     * @return array<string, mixed>
     */
    private function testsJob(): array
    {
        $path = \dirname(__DIR__, 3) . '/.github/workflows/ci.yml';
        self::assertFileExists($path, 'Le workflow de CI a disparu.');

        /** @var array<string, mixed> $workflow */
        $workflow = Yaml::parseFile($path);

        /** @var array<string, mixed> $job */
        $job = $workflow['jobs']['tests'];

        return $job;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function steps(): array
    {
        /** @var list<array<string, mixed>> $steps */
        $steps = $this->testsJob()['steps'];

        self::assertGreaterThan(3, \count($steps), 'Le job de tests ne se lit plus : le garde-fou ne mesure rien.');

        return $steps;
    }

    /**
     * L'etape dediee existe, et elle vise le groupe.
     */
    public function testTheBenchmarkHasItsOwnStep(): void
    {
        $found = null;

        foreach ($this->steps() as $step) {
            $run = $step['run'] ?? null;
            if (\is_string($run) && str_contains($run, '--group ' . self::GROUP)) {
                $found = $step;
                break;
            }
        }

        self::assertNotNull($found, sprintf(
            'Aucune etape de CI ne lance le groupe « %s ». Le banc d\'essai est exclu de la course de '
            . 'couverture et se refuse sous instrumentation : sans cette etape, il ne tourne nulle part '
            . 'et la CI est verte pour cette raison.',
            self::GROUP,
        ));
    }

    /**
     * **Et elle coupe l'instrumentation.**.
     *
     * Xdebug coute des que son mode contient `coverage`, que PHPUnit collecte
     * ou non : retirer `--coverage-*` ne retire pas le cout. Sans
     * `XDEBUG_MODE=off`, l'etape dediee mesurerait exactement ce que mesurait
     * celle qu'elle remplace — et le test, se voyant sous couverture, se
     * contenterait de passer en « skipped ».
     */
    public function testTheBenchmarkStepTurnsInstrumentationOff(): void
    {
        foreach ($this->steps() as $step) {
            $run = $step['run'] ?? null;
            if (!\is_string($run) || !str_contains($run, '--group ' . self::GROUP)) {
                continue;
            }

            $mode = $step['env']['XDEBUG_MODE'] ?? null;

            self::assertSame('off', \is_string($mode) ? $mode : null, sprintf(
                'L\'etape du banc d\'essai ne coupe pas Xdebug (XDEBUG_MODE = %s). '
                . 'Elle mesurerait le profileur, et le test se mettrait en « skipped ».',
                var_export($mode, true),
            ));

            self::assertStringNotContainsString('--coverage', $run, 'Le banc d\'essai collecte de la couverture.');

            return;
        }

        self::fail('Etape du banc d\'essai introuvable.');
    }

    /**
     * La course de couverture, elle, l'exclut.
     */
    public function testTheCoverageRunExcludesTheBenchmark(): void
    {
        $coverageRuns = 0;

        foreach ($this->steps() as $step) {
            $run = $step['run'] ?? null;
            if (!\is_string($run) || !str_contains($run, 'vendor/bin/phpunit') || !str_contains($run, '--coverage')) {
                continue;
            }

            ++$coverageRuns;
            self::assertMatchesRegularExpression(
                '/--exclude-group ' . self::GROUP . '\b/',
                $run,
                'La course de couverture chronometre le banc d\'essai : elle mesurerait le profileur.',
            );
        }

        self::assertSame(1, $coverageRuns, 'Le nombre de courses avec couverture a change : le garde-fou doit etre relu.');
    }

    /**
     * Aucune commande n'emploie la forme depreciee `--exclude-group a,b`.
     *
     * Elle leve un avertissement du lanceur, et un avertissement du lanceur
     * rend la course rouge : la CI tomberait sur la maniere d'exclure le banc,
     * pas sur ce qu'il mesure.
     */
    public function testNoCommandUsesTheDeprecatedCommaSeparatedForm(): void
    {
        $offenders = [];

        foreach ($this->steps() as $step) {
            $run = $step['run'] ?? null;
            if (\is_string($run) && preg_match(self::REPEATED_FLAG_ONLY, $run) === 1) {
                $offenders[] = trim($run);
            }
        }

        self::assertSame([], $offenders, sprintf(
            'Forme depreciee `--exclude-group a,b` (retiree en PHPUnit 12), qui leve un avertissement '
            . "du lanceur et rend la course rouge :\n%s\nRepetez le drapeau, une fois par groupe.",
            implode("\n", $offenders),
        ));
    }

    /**
     * Le seuil du workflow est celui que le test declare par defaut.
     *
     * Deux valeurs qui disent la meme chose finissent par diverger, et la
     * divergence serait muette : la CI garderait un seuil, un `phpunit` lance a
     * la main en garderait un autre, et les deux se contrediraient sans que
     * personne ne sache lequel fait foi.
     */
    public function testTheWorkflowThresholdMatchesTheDeclaredDefault(): void
    {
        $threshold = $this->testsJob()['env']['PERF_MAX_RESPONSE_MS'] ?? null;

        self::assertSame(
            PerformanceBenchmarkTest::DEFAULT_THRESHOLD_MS,
            (int) $threshold,
            'Le seuil de la CI et celui du test ont divergé.',
        );
    }
}
