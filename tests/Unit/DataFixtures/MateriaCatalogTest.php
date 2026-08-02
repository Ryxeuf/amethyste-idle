<?php

namespace App\Tests\Unit\DataFixtures;

use App\DataFixtures\MateriaCatalogFixtures;
use PHPUnit\Framework\TestCase;

/**
 * MAT-03 — le catalogue se derive, il ne s'ecrit pas.
 *
 * Le contrat de source : plus aucune materia en donnees manuelles, chaque
 * nœud `unlock` cite un sort qui existe, et la liste des orphelines (a
 * raccrocher par MAT-07) ne recouvre aucun nœud — une entree qui y resterait
 * apres son raccrochage serait un doublon silencieux.
 */
class MateriaCatalogTest extends TestCase
{
    private function source(string $path): string
    {
        return (string) file_get_contents(\dirname(__DIR__, 3) . '/' . $path);
    }

    /**
     * Une materia ecrite a la main est un bug (GAME_MATERIA §2.1) : le
     * catalogue entier vient de `MateriaCatalogFixtures`, par derivation.
     */
    public function testNoHandWrittenMateriaRemains(): void
    {
        $this->assertStringNotContainsString(
            "'type' => 'materia'",
            $this->source('src/DataFixtures/ItemFixtures.php'),
            'Une materia est revenue en donnees manuelles : le catalogue se derive (MateriaCatalogFixtures, MAT-03).',
        );
    }

    /**
     * Aucun nœud ne ment : tout `actions.materia.unlock` cite un sort qui
     * existe — c'est ce qui garantit que la derivation couvre 100 % des
     * promesses des arbres.
     */
    public function testEveryUnlockResolvesToASpell(): void
    {
        preg_match_all("/'unlock' => '([a-z0-9-]+)'/", $this->source('src/DataFixtures/Game/SkillFixtures.php'), $unlocks);
        preg_match_all("/'slug' => '([a-z0-9-]+)'/", $this->source('src/DataFixtures/SpellFixtures.php'), $spells);

        $distinct = array_unique($unlocks[1]);
        $this->assertNotEmpty($distinct, 'Le test ne verifie rien si l\'extraction echoue.');

        $lying = array_values(array_diff($distinct, $spells[1]));
        $this->assertSame([], $lying, sprintf('Des nœuds ouvrent des sorts inexistants : %s.', implode(', ', $lying)));
    }

    /**
     * Les orphelines declarees existent comme sorts et ne sont ouvertes par
     * aucun nœud — le jour ou MAT-07 les raccroche, l'entree doit sortir de
     * la liste.
     */
    public function testOrphanListIsAccurate(): void
    {
        preg_match_all("/'unlock' => '([a-z0-9-]+)'/", $this->source('src/DataFixtures/Game/SkillFixtures.php'), $unlocks);
        preg_match_all("/'slug' => '([a-z0-9-]+)'/", $this->source('src/DataFixtures/SpellFixtures.php'), $spells);

        foreach (MateriaCatalogFixtures::ORPHAN_SPELLS as $slug) {
            $this->assertContains($slug, $spells[1], sprintf('L\'orpheline "%s" cite un sort inexistant.', $slug));
            $this->assertNotContains(
                $slug,
                $unlocks[1],
                sprintf('"%s" est desormais ouverte par un nœud : la retirer d\'ORPHAN_SPELLS (elle serait derivee deux fois pour rien).', $slug),
            );
        }
    }
}
