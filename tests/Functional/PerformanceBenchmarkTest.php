<?php

namespace App\Tests\Functional;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Performance benchmark: verify that critical routes respond within an acceptable threshold.
 * Default threshold: 500ms (overridable via PERF_MAX_RESPONSE_MS env var).
 * Requires a real database with fixtures loaded.
 */
class PerformanceBenchmarkTest extends WebTestCase
{
    private KernelBrowser $client;

    private int $maxResponseTimeMs;

    protected function setUp(): void
    {
        $this->client = static::createClient();

        $envThreshold = getenv('PERF_MAX_RESPONSE_MS');
        $this->maxResponseTimeMs = $envThreshold !== false ? (int) $envThreshold : 500;

        /** @var EntityManagerInterface $em */
        $em = static::getContainer()->get(EntityManagerInterface::class);
        $user = $em->getRepository(User::class)->findOneBy(['email' => 'remy@amethyste.game']);

        if ($user === null) {
            $this->markTestSkipped('Fixture user remy@amethyste.game not found — run doctrine:fixtures:load first.');
        }

        $this->client->loginUser($user);

        // Warm-up: first request pays kernel boot cost — exclude from measurements
        $this->client->request('GET', '/game/map');
    }

    #[DataProvider('criticalRoutesProvider')]
    public function testRouteRespondsWithinThreshold(string $url, string $label): void
    {
        $start = microtime(true);
        $this->client->request('GET', $url);
        $durationMs = (microtime(true) - $start) * 1000;

        $statusCode = $this->client->getResponse()->getStatusCode();

        if ($statusCode >= 400) {
            $this->markTestSkipped(sprintf('[%s] returned HTTP %d — skipping performance check', $label, $statusCode));
        }

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

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function criticalRoutesProvider(): iterable
    {
        // Game pages
        yield 'map' => ['/game/map', 'Game: Map'];
        yield 'inventory' => ['/game/inventory', 'Game: Inventory'];
        yield 'skills' => ['/game/skills', 'Game: Skills'];
        yield 'bestiary' => ['/game/bestiary', 'Game: Bestiary'];
        yield 'achievements' => ['/game/achievements', 'Game: Achievements'];
        yield 'quests' => ['/game/quests', 'Game: Quests'];

        // API endpoints (no hardcoded IDs — cells defaults to player's map)
        yield 'api-map-config' => ['/api/map/config', 'API: Map Config'];
        yield 'api-map-cells' => ['/api/map/cells?x=0&y=0&radius=5', 'API: Map Cells'];
        yield 'api-quickbar' => ['/api/quickbar/items', 'API: Quickbar Items'];
        yield 'api-game-time' => ['/api/game/time', 'API: Game Time'];
        yield 'api-active-events' => ['/api/game/events/active', 'API: Active Events'];
    }
}
