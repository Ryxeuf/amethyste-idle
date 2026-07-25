<?php

declare(strict_types=1);

namespace App\Tests\Unit\Narrative;

use App\Entity\App\GameEvent;
use App\Entity\App\InfluenceSeason;
use App\Entity\Game\CodexEntry;
use App\Entity\Game\Quest;
use PHPUnit\Framework\TestCase;

/**
 * Test de « contrat » du plan narratif (NAR-14) : garde-fou du vocabulaire
 * declaratif sur lequel reposent tous les jalons (arcs, Codex, beats de saison,
 * canon). Renommer/casser une de ces conventions doit faire echouer un test.
 *
 * La couverture fonctionnelle detaillee est portee par les tests dedies de
 * chaque jalon (cf. docs/roadmap/NARRATIVE_TEST_COVERAGE.md) ; ce test verrouille
 * les invariants transverses.
 */
final class NarrativePlanContractTest extends TestCase
{
    public function testCodexCategoriesAreDistinct(): void
    {
        $categories = [
            CodexEntry::CATEGORY_REGION,
            CodexEntry::CATEGORY_FACTION,
            CodexEntry::CATEGORY_BESTIARY_LORE,
            CodexEntry::CATEGORY_WORLD_FACT,
        ];

        self::assertCount(4, array_unique($categories));
        // Seul world_fact est public (journal de monde, NAR-07).
        self::assertTrue((new CodexEntry())->setCategory(CodexEntry::CATEGORY_WORLD_FACT)->isPublic());
        self::assertFalse((new CodexEntry())->setCategory(CodexEntry::CATEGORY_REGION)->isPublic());
    }

    public function testCodexUnlockTypesAreDistinct(): void
    {
        $types = [
            CodexEntry::UNLOCK_ZONE_VISIT,
            CodexEntry::UNLOCK_BOSS_KILL,
            CodexEntry::UNLOCK_ARC_COMPLETED,
            CodexEntry::UNLOCK_MANUAL,
        ];

        self::assertCount(4, array_unique($types));
    }

    public function testSeasonBeatsAreDistinctAndOrdered(): void
    {
        $beats = [
            GameEvent::BEAT_AMORCE,
            GameEvent::BEAT_MONTEE,
            GameEvent::BEAT_CLIMAX,
            GameEvent::BEAT_RESOLUTION,
        ];

        self::assertCount(4, array_unique($beats));
    }

    public function testSeasonStoryArcConvention(): void
    {
        $season = (new InfluenceSeason())->setSlug('saison-1');

        self::assertSame('season_saison-1', $season->getStoryArc());
    }

    public function testSeasonCanonDefaultsToFalse(): void
    {
        // Le monde hybride : rien n'est canon par defaut (NAR-12).
        self::assertFalse((new InfluenceSeason())->isCanon());
    }

    public function testQuestArcSortIsNullLast(): void
    {
        // Marqueur d'arc (NAR-01) : le tri place les positions nulles en fin.
        $positioned = (new Quest())->setName('a')->setArcOrder(1);
        $unpositioned = (new Quest())->setName('z');

        $sorted = Quest::sortByArcOrder([$unpositioned, $positioned]);

        self::assertSame(['a', 'z'], array_map(static fn (Quest $q): string => $q->getName(), $sorted));
    }
}
