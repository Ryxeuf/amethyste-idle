<?php

namespace App\Tests\Unit\DataFixtures;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Yaml\Yaml;

/**
 * BES-01 — deux axes : `tier` et `rank`.
 *
 * Le contrat tient quatre choses : les trois anciennes echelles ont disparu
 * des donnees (`level`, `difficulty`, `isBoss`), chaque monstre porte un
 * palier valide et un rang connu, le palier d'un monstre est celui de la
 * zone qui le place (ecarts explicites : le fond des Mines), et les phases
 * de boss n'appartiennent qu'au rang `boss`.
 */
class MonsterTierRankTest extends TestCase
{
    /**
     * Ecarts au palier de zone, explicites et commentes dans la fixture
     * (GAME_BESTIARY §2.1 : fond des Mines T4).
     *
     * @var array<string, int>
     */
    private const EXPLICIT_TIER_EXCEPTIONS = [
        'abyssal_blacksmith' => 4,
        'forge_lord' => 4,
    ];

    private function monsterSource(): string
    {
        return (string) file_get_contents(\dirname(__DIR__, 3) . '/src/DataFixtures/MonsterFixtures.php');
    }

    /**
     * @return array<string, array{tier: ?int, rank: string, hasBossPhases: bool}>
     */
    private function monsters(): array
    {
        $source = $this->monsterSource();
        preg_match_all("/\n            '([a-z_0-9]+)' => \[/", $source, $matches, PREG_OFFSET_CAPTURE);
        $blocks = $matches[1];

        $monsters = [];
        foreach ($blocks as $i => [$slug, $offset]) {
            $end = isset($blocks[$i + 1]) ? $blocks[$i + 1][1] : \strlen($source);
            $block = substr($source, $offset, $end - $offset);

            preg_match("/'tier' => (\d+),/", $block, $tier);
            preg_match("/'rank' => '([a-z]+)'/", $block, $rank);

            $monsters[$slug] = [
                'tier' => isset($tier[1]) ? (int) $tier[1] : null,
                'rank' => $rank[1] ?? 'common',
                'hasBossPhases' => str_contains($block, "'bossPhases'"),
            ];
        }

        return $monsters;
    }

    /**
     * Les trois anciennes echelles ont disparu des donnees : deux axes, pas
     * quatre (invariant 1 de GAME_BESTIARY §6).
     */
    public function testLegacyScalesAreGone(): void
    {
        $source = $this->monsterSource();

        $this->assertStringNotContainsString("'level' =>", $source, 'L\'echelle 1-40 a disparu (BES-01).');
        $this->assertStringNotContainsString("'difficulty' =>", $source, 'L\'echelle 1-5 a disparu (BES-01).');
        $this->assertStringNotContainsString("'isBoss' =>", $source, 'Le booleen isBoss a disparu au profit du rang (BES-01).');
    }

    /**
     * Chaque monstre porte un palier 0-4 et un rang connu ; T0 est reserve
     * aux mannequins d'entrainement.
     */
    public function testEveryMonsterCarriesTierAndKnownRank(): void
    {
        $monsters = $this->monsters();
        $this->assertNotEmpty($monsters, 'Le test ne verifie rien si l\'extraction echoue.');

        $dummies = ['training_dummy_still', 'training_dummy_sparring'];

        foreach ($monsters as $slug => $data) {
            $this->assertNotNull($data['tier'], sprintf('%s ne declare pas de palier.', $slug));
            $this->assertLessThanOrEqual(4, $data['tier'], sprintf('%s : palier inconnu.', $slug));
            $this->assertContains($data['rank'], ['common', 'elite', 'boss'], sprintf('%s : rang inconnu "%s".', $slug, $data['rank']));

            if (\in_array($slug, $dummies, true)) {
                $this->assertSame(0, $data['tier'], sprintf('%s : un mannequin vit hors du monde (T0).', $slug));
            } else {
                $this->assertGreaterThanOrEqual(1, $data['tier'], sprintf('%s : un vrai monstre vit dans un palier T1+ (T0 est sur).', $slug));
            }
        }
    }

    /**
     * Le palier d'un monstre est celui de la zone qui le place — jamais
     * invente (invariant 2). Pour une espece placee dans plusieurs zones, le
     * palier est celui de sa zone la plus basse : c'est la qu'elle doit
     * rester battable.
     */
    public function testMonsterTierFollowsItsZone(): void
    {
        $config = Yaml::parseFile(\dirname(__DIR__, 3) . '/config/game/zones/world_1.yaml');
        $zones = $config['world']['zones'] ?? $config['zones'] ?? null;
        $this->assertIsArray($zones, 'Le YAML des zones doit exposer ses zones.');

        $placements = [];
        foreach ($zones as $zoneSlug => $zone) {
            $this->assertArrayHasKey('tier', $zone, sprintf('La zone "%s" doit declarer son palier (BES-01).', $zoneSlug));
            foreach ($zone['mobs'] ?? [] as $mob) {
                $placements[$mob['monster']][] = (int) $zone['tier'];
            }
        }

        $this->assertNotEmpty($placements, 'Le test ne verifie rien si aucune zone ne place de faune.');

        $monsters = $this->monsters();
        foreach ($placements as $slug => $zoneTiers) {
            $this->assertArrayHasKey($slug, $monsters, sprintf('"%s" est place en zone mais absent des fixtures.', $slug));

            $expected = self::EXPLICIT_TIER_EXCEPTIONS[$slug] ?? min($zoneTiers);
            $this->assertSame(
                $expected,
                $monsters[$slug]['tier'],
                sprintf('%s : le palier du monstre (%s) ne suit pas celui de sa zone (%d attendu).', $slug, (string) $monsters[$slug]['tier'], $expected),
            );
        }
    }

    /**
     * Les phases de boss n'appartiennent qu'au rang `boss` — le rang a
     * absorbe le booleen, il en reprend les responsabilites.
     */
    public function testBossPhasesBelongToBossRank(): void
    {
        foreach ($this->monsters() as $slug => $data) {
            if ($data['hasBossPhases']) {
                $this->assertSame('boss', $data['rank'], sprintf('%s declare des bossPhases sans etre de rang boss.', $slug));
            }
        }
    }
}
