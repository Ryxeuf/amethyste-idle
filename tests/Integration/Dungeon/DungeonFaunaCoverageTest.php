<?php

namespace App\Tests\Integration\Dungeon;

use App\Enum\MonsterRank;
use App\GameEngine\Dungeon\DungeonEncounterPicker;
use App\Tests\Integration\AbstractIntegrationTestCase;

/**
 * DON-03 — un donjon se peuple tout seul, a tout palier.
 *
 * Le donjon ne definit pas ses creatures : il puise dans la faune de son
 * palier (GAME_DUNGEONS §3). Le contrat verifie que le tirage rend une
 * creature pour **chaque etape de chaque palier** — les 4 paliers x 3 rangs —
 * sur les donnees reellement livrees : le jour ou une redistribution de faune
 * viderait une case, c'est ici que ca se verrait, pas dans un donjon muet.
 */
final class DungeonFaunaCoverageTest extends AbstractIntegrationTestCase
{
    public function testEveryTierServesItsThreeSteps(): void
    {
        $picker = new DungeonEncounterPicker($this->em);

        foreach ([1, 2, 3, 4] as $tier) {
            foreach (MonsterRank::cases() as $rank) {
                $candidates = $picker->candidates($tier, $rank);

                $this->assertNotEmpty(
                    $candidates,
                    sprintf('Le palier T%d n\'a aucune creature de rang %s : une etape de donjon serait vide (DON-03).', $tier, $rank->value),
                );

                foreach ($candidates as $monster) {
                    $this->assertSame($tier, $monster->getTier(), sprintf('« %s » servi hors de son palier.', $monster->getName()));
                    $this->assertSame($rank, $monster->getRank());
                    $this->assertNull($monster->getTrainingMode(), 'Un mannequin d\'entrainement ne peut pas etre une rencontre de donjon.');
                    $this->assertGreaterThan(0, $monster->getLife(), sprintf('« %s » n\'a pas de vie : la barre de rencontre serait vide.', $monster->getName()));
                }
            }
        }
    }
}
