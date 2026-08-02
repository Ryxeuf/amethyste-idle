<?php

namespace App\Tests\Unit\DataFixtures;

use PHPUnit\Framework\TestCase;

/**
 * OBJ-02 — le menage : doublons et fixtures mortes (GAME_ITEMS §5.2).
 *
 * Une matiere a un slug et un seul : les doublons legacy (la buche de decor,
 * la pioche sans palier, les herbes prefixees `herb-`, les deux peaux, le
 * pain double) sont supprimes au profit des lignes reelles. Et une fixture
 * qui n'est plus chargee ment a qui la lit — les YAML morts sont supprimes,
 * pas commentes.
 */
class ItemCleanupTest extends TestCase
{
    /**
     * Slugs supprimes par OBJ-02, et ce qui les remplace (GAME_ITEMS §5.2).
     *
     * @var list<string>
     */
    private const LEGACY_DUPLICATES = [
        'wood-log',        // la ligne du bois (wood-beech...)
        'pickaxe',         // pickaxe-bronze, premier palier de la ligne d'outils
        'herb-lavender',   // plant-lavender, recoltable
        'herb-mint',       // plant-mint, recoltable
        'leather-skin-1',  // leather-raw
        'leather-skin-2',  // leather-thick
        'food-bread',      // bread
    ];

    private function root(): string
    {
        return \dirname(__DIR__, 3);
    }

    /**
     * @return list<string> tous les slugs d'objets, sources PHP et YAML
     */
    private function allSlugs(): array
    {
        $slugs = [];

        $source = (string) file_get_contents($this->root() . '/src/DataFixtures/ItemFixtures.php');
        preg_match_all("/'slug' => '([a-z0-9-]+)'/", $source, $matches);
        foreach ($matches[1] as $slug) {
            $slugs[] = $slug;
        }

        foreach (glob($this->root() . '/fixtures/game/item/*.yaml') ?: [] as $file) {
            preg_match_all("/^\\s+slug: '([a-z0-9-]+)'$/m", (string) file_get_contents($file), $yaml);
            foreach ($yaml[1] as $slug) {
                $slugs[] = $slug;
            }
        }

        $this->assertNotEmpty($slugs, 'Le test ne verifie rien si l\'extraction echoue.');

        return $slugs;
    }

    /**
     * Aucun doublon : un slug n'apparait qu'une fois, toutes sources
     * confondues (invariant 9 de GAME_ITEMS).
     */
    public function testNoDuplicateSlug(): void
    {
        $duplicates = array_filter(array_count_values($this->allSlugs()), static fn (int $count): bool => $count > 1);

        $this->assertSame(
            [],
            $duplicates,
            sprintf('Des slugs d\'objet existent en double (OBJ-02) : %s.', implode(', ', array_keys($duplicates))),
        );
    }

    /**
     * Les doublons legacy ne reviennent pas — chacun a un remplacant dans une
     * ligne reelle, le ressusciter recreerait une matiere sans consommateur.
     */
    public function testLegacyDuplicatesStayGone(): void
    {
        $back = array_values(array_intersect(self::LEGACY_DUPLICATES, array_unique($this->allSlugs())));

        $this->assertSame(
            [],
            $back,
            sprintf('Des doublons legacy sont revenus (OBJ-02, GAME_ITEMS §5.2) : %s.', implode(', ', $back)),
        );
    }

    /**
     * Les fixtures mortes restent supprimees : plus rien ne les chargeait
     * (elles annoncaient 15 domaines quand la vraie source en a 36), et un
     * fichier de donnees que rien ne lit finit toujours par mentir.
     */
    public function testDeadFixturesStayDeleted(): void
    {
        $dead = [
            'fixtures/domain.yaml',
            'fixtures/game/skill',
            'fixtures/game/spell',
            'fixtures/game/monster',
        ];

        foreach ($dead as $path) {
            $this->assertFileDoesNotExist(
                $this->root() . '/' . $path,
                sprintf('"%s" est revenu : cette fixture n\'est chargee par personne, elle ment a qui la lit (OBJ-02).', $path),
            );
        }
    }
}
