<?php

namespace App\Tests\Unit\Event;

use PHPUnit\Framework\TestCase;

/**
 * Garde-fou anti-recidive (ZON-22).
 *
 * La suppression du code carte (ZON-21) a retire le dispatcher de
 * `PlayerMovedEvent` en laissant 6 abonnes branches dessus : quetes
 * d'exploration et d'escorte, quetes cachees, tutoriel, decouverte de region.
 * Le defaut est silencieux — aucun test ne casse, la fonctionnalite s'eteint.
 *
 * Ce test verifie que chaque evenement de domaine (`src/Event/**` portant une
 * constante `NAME`) est **emis** quelque part dans `src/`. Un evenement sans
 * emetteur est du code mort, ou pire : des abonnes qui ne se declencheront
 * jamais.
 */
class DomainEventDispatchGuardTest extends TestCase
{
    /**
     * Evenements connus sans emetteur, tolerés le temps d'etre traites.
     *
     * Ils **preexistent** au pivot PBBG et sont inscrits au jalon ZON-25
     * (Sprint 13) ; deux d'entre eux ont un impact fonctionnel reel :
     *
     * - `PnjDialogEvent` : seul declencheur de `PlayerQuestUpdater::updateTalkedTo`
     *   — les objectifs de quete « parler a un PNJ » ne progressent pas.
     * - `FightLootedEvent` : seul declencheur de `FightCleaner::removeFight` et
     *   de l'etape « inventaire » du tutoriel — les combats ne sont pas purges
     *   apres le butin.
     * - `PlayerActionHitEvent` / `PlayerActionMissEvent` : aucun abonne, code mort.
     *
     * @var list<string>
     */
    private const KNOWN_ORPHANS = [
        'PnjDialogEvent',
        'FightLootedEvent',
        'PlayerActionHitEvent',
        'PlayerActionMissEvent',
    ];

    public function testEveryDomainEventHasAnEmitter(): void
    {
        $projectDir = \dirname(__DIR__, 3);
        $sources = $this->phpFilesIn($projectDir . '/src');
        $events = $this->phpFilesIn($projectDir . '/src/Event');

        $orphans = [];

        foreach ($events as $eventFile) {
            $shortName = basename($eventFile, '.php');
            $contents = (string) file_get_contents($eventFile);

            // Seuls les evenements de domaine (constante NAME) sont concernes :
            // les classes de base abstraites n'en portent pas.
            if (!str_contains($contents, 'const NAME')) {
                continue;
            }

            if (\in_array($shortName, self::KNOWN_ORPHANS, true)) {
                continue;
            }

            if (!$this->isInstantiatedOutside($shortName, $eventFile, $sources)) {
                $orphans[] = $shortName;
            }
        }

        $this->assertSame([], $orphans, sprintf(
            "Evenement(s) de domaine sans emetteur : %s.\n"
            . "Un evenement dont plus rien n'emet laisse ses abonnes inertes (cf. ZON-22).\n"
            . 'Rebranchez-le, supprimez-le, ou ajoutez-le a KNOWN_ORPHANS avec sa raison.',
            implode(', ', $orphans)
        ));
    }

    public function testKnownOrphansAreStillOrphans(): void
    {
        $projectDir = \dirname(__DIR__, 3);
        $sources = $this->phpFilesIn($projectDir . '/src');
        $events = $this->phpFilesIn($projectDir . '/src/Event');
        $byShortName = [];
        foreach ($events as $file) {
            $byShortName[basename($file, '.php')] = $file;
        }

        foreach (self::KNOWN_ORPHANS as $orphan) {
            $this->assertArrayHasKey($orphan, $byShortName, sprintf(
                '%s a ete supprime : retirez-le de KNOWN_ORPHANS.',
                $orphan
            ));

            $this->assertFalse(
                $this->isInstantiatedOutside($orphan, $byShortName[$orphan], $sources),
                sprintf('%s a retrouve un emetteur : retirez-le de KNOWN_ORPHANS.', $orphan)
            );
        }
    }

    /**
     * @param list<string> $sources
     */
    private function isInstantiatedOutside(string $shortName, string $ownFile, array $sources): bool
    {
        foreach ($sources as $source) {
            if ($source === $ownFile) {
                continue;
            }
            if (str_contains((string) file_get_contents($source), 'new ' . $shortName . '(')) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return list<string>
     */
    private function phpFilesIn(string $directory): array
    {
        $files = [];
        $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($directory, \FilesystemIterator::SKIP_DOTS));

        /** @var \SplFileInfo $file */
        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getExtension() === 'php') {
                $files[] = $file->getPathname();
            }
        }

        sort($files);

        return $files;
    }
}
