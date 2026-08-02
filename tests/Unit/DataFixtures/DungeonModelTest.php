<?php

namespace App\Tests\Unit\DataFixtures;

use PHPUnit\Framework\TestCase;

/**
 * DON-01 — un seul modele : le donjon de zone (GAME_DUNGEONS §2).
 *
 * Deux modeles incompatibles coexistaient, et aucun ne tenait : les donjons
 * solo reposaient sur la carte navigable supprimee par ZON-21 (entrer
 * teleportait sur une `Map`, leurs mobs n'avaient aucune zone, l'ecran
 * n'offrait aucune action). Le modele unique : tout donjon est rattache a une
 * zone du graphe et se lance depuis son ecran de zone — le solo est un donjon
 * a `maxPlayers: 1`, par la meme mecanique.
 */
class DungeonModelTest extends TestCase
{
    private function root(): string
    {
        return \dirname(__DIR__, 3);
    }

    /**
     * Aucun donjon sans zone : chaque `new Dungeon()` des fixtures recoit un
     * `setZone(...)`. Un donjon hors graphe est un donjon que l'ecran de zone
     * ne proposera jamais — du contenu mort.
     */
    public function testEveryDungeonBelongsToAZone(): void
    {
        $source = (string) file_get_contents($this->root() . '/src/DataFixtures/DungeonFixtures.php');

        $created = preg_match_all('/new Dungeon\(\)/', $source);
        $zoned = preg_match_all('/->setZone\(/', $source);

        $this->assertGreaterThan(0, $created, 'Le test ne verifie rien si aucune fixture de donjon n\'existe.');
        $this->assertSame(
            $created,
            $zoned,
            sprintf('%d donjon(s) crees pour %d rattachements de zone : un donjon sans zone est hors du monde (DON-01).', $created, $zoned),
        );
    }

    /**
     * Le seuil d'experience se calcule en un seul endroit —
     * `Dungeon::getRequiredExperience()`. Il vivait en trois (DungeonManager
     * x2, ZoneController), et trois formules finissent toujours par diverger.
     */
    public function testTheExperienceThresholdHasOneHome(): void
    {
        $offenders = [];
        $directory = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($this->root() . '/src'));
        foreach ($directory as $file) {
            if (!$file instanceof \SplFileInfo || 'php' !== $file->getExtension()) {
                continue;
            }
            $content = (string) file_get_contents($file->getPathname());
            if (str_contains($content, 'getMinLevel() * 100')) {
                $offenders[] = substr($file->getPathname(), \strlen($this->root()) + 1);
            }
        }

        $this->assertSame(
            [],
            $offenders,
            sprintf('Le seuil d\'XP se recalcule hors de Dungeon::getRequiredExperience() (DON-01) : %s.', implode(', ', $offenders)),
        );

        $entity = (string) file_get_contents($this->root() . '/src/Entity/Game/Dungeon.php');
        $this->assertStringContainsString('public function getRequiredExperience', $entity, 'Le seuil n\'a plus de maison.');
    }

    /**
     * DON-01b — le chemin solo mort le reste : l'entree par teleportation sur
     * une `Map`, `DungeonRun` et ses coordonnees d'origine, l'ecran
     * `/game/dungeon` separe. Tout donjon passe par la voie de zone.
     */
    public function testTheDeadSoloPathStaysDead(): void
    {
        $dead = [
            'src/Controller/Game/DungeonController.php',
            'src/Entity/App/DungeonRun.php',
            'src/Repository/DungeonRunRepository.php',
            'src/GameEngine/Dungeon/DungeonCompletionListener.php',
            'src/Event/Game/DungeonCompletedEvent.php',
            'src/Enum/DungeonDifficulty.php',
            'templates/game/dungeon',
        ];

        foreach ($dead as $path) {
            $this->assertFileDoesNotExist(
                $this->root() . '/' . $path,
                sprintf('"%s" est revenu : le chemin solo d\'avant le pivot n\'a qu\'un remplacant, le donjon de zone (DON-01).', $path),
            );
        }
    }
}
