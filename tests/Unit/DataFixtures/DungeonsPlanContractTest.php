<?php

namespace App\Tests\Unit\DataFixtures;

use PHPUnit\Framework\TestCase;

/**
 * DON-06 — le contrat du plan donjons (GAME_DUNGEONS §6).
 *
 * Les huit invariants du cadrage, et qui les tient :
 *
 *  1. Un seul modele                  → DungeonModelTest (tout donjon a une zone, le chemin solo mort le reste)
 *  2. Tout donjon est completable     → GroupDungeonCombatServiceTest (les trois etapes jusqu'au boss) + DungeonFaunaCoverageTest (les 4 paliers x 3 rangs servis)
 *  3. Toute rencontre est un monstre  → GroupDungeonCombatServiceTest (aucun sac de PV, palier de la zone) + DungeonFaunaCoverageTest
 *  4. Le build compte                 → DungeonActionResolverTest (arme, materia, passifs — jamais le seul `hit`)
 *  5. Un donjon peut etre perdu       → GroupDungeonCombatServiceTest (STATUS_FAILED atteint quand tous les membres tombent)
 *  6. `lootPreview` ne ment pas       → ICI (le texte libre ne revient pas) + MateriaLootTableTest (l'apercu et le tirage lisent dungeonPaliers) + ZoneControllerTest
 *  7. Un donjon par palier            → DungeonModelTest (T1-T4, quatre zones distinctes, la fusion tient)
 *  8. Le seuil calcule en un point    → DungeonModelTest (Dungeon::getRequiredExperience, aucun recalcul)
 *
 * Ce fichier ne re-teste pas ce que les autres tiennent deja : il porte le
 * seul garde-fou qui manquait, verifie que l'index ci-dessus ne pourrit pas,
 * et sert de table des matieres au contrat.
 */
class DungeonsPlanContractTest extends TestCase
{
    private function root(): string
    {
        return \dirname(__DIR__, 3);
    }

    /**
     * L'index ne doit pas pourrir : chaque test cite dans le contrat existe.
     */
    public function testTheContractIndexNamesRealTests(): void
    {
        foreach ([
            'Unit/DataFixtures/DungeonModelTest.php',
            'Unit/GameEngine/Dungeon/GroupDungeonCombatServiceTest.php',
            'Unit/GameEngine/Dungeon/DungeonActionResolverTest.php',
            'Unit/GameEngine/Materia/MateriaLootTableTest.php',
            'Integration/Dungeon/DungeonFaunaCoverageTest.php',
            'Functional/Controller/Game/ZoneControllerTest.php',
        ] as $test) {
            $this->assertFileExists(
                $this->root() . '/tests/' . $test,
                sprintf('Le contrat cite %s, qui n\'existe plus : mettre l\'index a jour.', $test),
            );
        }
    }

    /**
     * Invariant 6 — le texte libre ne revient pas : l'apercu de butin se
     * derive de la table reelle (`MateriaLootTable::dungeonPaliers`), et
     * aucune fixture ni aucun service ne repose un `lootPreview` a la main.
     * Un apercu qui ment est pire que pas d'apercu — et il ne peut mentir
     * que s'il redevient une donnee.
     */
    public function testTheFreeTextLootPreviewStaysDead(): void
    {
        $offenders = [];
        $directory = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($this->root() . '/src'));
        foreach ($directory as $file) {
            if (!$file instanceof \SplFileInfo || 'php' !== $file->getExtension()) {
                continue;
            }
            $content = (string) file_get_contents($file->getPathname());
            if (str_contains($content, 'setLootPreview') || str_contains($content, 'getLootPreview')) {
                $offenders[] = substr($file->getPathname(), \strlen($this->root()) + 1);
            }
        }

        $this->assertSame(
            [],
            $offenders,
            sprintf('Le texte libre d\'apercu de butin est revenu (DON-04 l\'a supprime au profit de la derivation) : %s.', implode(', ', $offenders)),
        );

        // Et la derivation, elle, doit rester la : l'ecran de zone lit la
        // meme fonction que le tirage.
        $controller = (string) file_get_contents($this->root() . '/src/Controller/Game/ZoneController.php');
        $this->assertStringContainsString('MateriaLootTable::dungeonPaliers', $controller, 'L\'apercu ne derive plus de la table reelle.');
    }
}
