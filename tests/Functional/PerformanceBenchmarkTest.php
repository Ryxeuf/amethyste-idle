<?php

namespace App\Tests\Functional;

use App\Entity\App\Player;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Performance benchmark: verify that critical routes respond within an acceptable threshold.
 * Default threshold: 200ms (overridable via PERF_MAX_RESPONSE_MS env var for CI).
 * Requires a real database with fixtures loaded.
 */
class PerformanceBenchmarkTest extends WebTestCase
{
    private KernelBrowser $client;

    private int $maxResponseTimeMs;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->maxResponseTimeMs = (int) ($_ENV['PERF_MAX_RESPONSE_MS'] ?? 200);

        /** @var EntityManagerInterface $em */
        $em = static::getContainer()->get(EntityManagerInterface::class);
        $user = $em->getRepository(User::class)->findOneBy(['email' => 'remy@amethyste.game']);

        if ($user === null) {
            $this->markTestSkipped('Fixture user remy@amethyste.game not found — run doctrine:fixtures:load first.');
        }

        $this->client->loginUser($user);

        // Warm-up request to avoid measuring kernel boot time
        $this->client->request('GET', '/game/map');
    }

    #[DataProvider('criticalGameRoutesProvider')]
    public function testGameRouteRespondsWithinThreshold(string $url, string $label): void
    {
        $this->assertRoutePerformance($url, $label);
    }

    #[DataProvider('simpleApiRoutesProvider')]
    public function testApiRouteRespondsWithinThreshold(string $url, string $label): void
    {
        $this->assertRoutePerformance($url, $label);
    }

    public function testMapCellsApiRespondsWithinThreshold(): void
    {
        // Use the player's actual map — avoids hardcoding a mapId that may not exist
        $this->assertRoutePerformance('/api/map/cells?x=0&y=0&radius=5', 'API: Map Cells');
    }

    public function testMapEntitiesApiRespondsWithinThreshold(): void
    {
        /** @var EntityManagerInterface $em */
        $em = static::getContainer()->get(EntityManagerInterface::class);
        $player = $em->getRepository(Player::class)->findOneBy([]);

        if ($player === null || $player->getMap() === null) {
            $this->markTestSkipped('No player with a map found in fixtures.');
        }

        $mapId = $player->getMap()->getId();
        $this->assertRoutePerformance('/api/map/entities?mapId=' . $mapId, 'API: Map Entities');
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function criticalGameRoutesProvider(): iterable
    {
        yield 'map' => ['/game/map', 'Game: Map'];
        yield 'inventory' => ['/game/inventory', 'Game: Inventory'];
        yield 'skills' => ['/game/skills', 'Game: Skills'];
        yield 'bestiary' => ['/game/bestiary', 'Game: Bestiary'];
        yield 'achievements' => ['/game/achievements', 'Game: Achievements'];
        yield 'quests' => ['/game/quests', 'Game: Quests'];
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function simpleApiRoutesProvider(): iterable
    {
        yield 'map-config' => ['/api/map/config', 'API: Map Config'];
        yield 'quickbar-items' => ['/api/quickbar/items', 'API: Quickbar Items'];
        yield 'game-time' => ['/api/game/time', 'API: Game Time'];
        yield 'active-events' => ['/api/game/events/active', 'API: Active Events'];
    }

    private function assertRoutePerformance(string $url, string $label): void
    {
        $start = microtime(true);
        $this->client->request('GET', $url);
        $durationMs = (microtime(true) - $start) * 1000;

        $statusCode = $this->client->getResponse()->getStatusCode();

        $this->assertLessThan(
            500,
            $statusCode,
            sprintf('[%s] returned HTTP %d', $label, $statusCode),
        );

        $this->assertLessThanOrEqual(
            $this->maxResponseTimeMs,
            $durationMs,
            sprintf(
                '[%s] responded in %.1f ms (max: %d ms)',
                $label,
                $durationMs,
                $this->maxResponseTimeMs,
            ),
        );
    }
}
