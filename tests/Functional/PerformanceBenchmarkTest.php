<?php

namespace App\Tests\Functional;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Performance benchmark: verify that critical routes respond in < 200ms.
 * Requires a real database with fixtures loaded.
 */
class PerformanceBenchmarkTest extends WebTestCase
{
    /**
     * Maximum allowed response time in milliseconds.
     */
    private const MAX_RESPONSE_TIME_MS = 200;

    private KernelBrowser $client;

    protected function setUp(): void
    {
        $this->client = static::createClient();

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
    public function testGameRouteRespondsUnder200ms(string $url, string $label): void
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
            self::MAX_RESPONSE_TIME_MS,
            $durationMs,
            sprintf(
                '[%s] responded in %.1f ms (max: %d ms)',
                $label,
                $durationMs,
                self::MAX_RESPONSE_TIME_MS,
            ),
        );
    }

    #[DataProvider('criticalApiRoutesProvider')]
    public function testApiRouteRespondsUnder200ms(string $url, string $label): void
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
            self::MAX_RESPONSE_TIME_MS,
            $durationMs,
            sprintf(
                '[%s] responded in %.1f ms (max: %d ms)',
                $label,
                $durationMs,
                self::MAX_RESPONSE_TIME_MS,
            ),
        );
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
    public static function criticalApiRoutesProvider(): iterable
    {
        yield 'map-config' => ['/api/map/config', 'API: Map Config'];
        yield 'map-cells' => ['/api/map/cells?x=0&y=0&radius=5&mapId=1', 'API: Map Cells'];
        yield 'map-entities' => ['/api/map/entities?mapId=1', 'API: Map Entities'];
        yield 'quickbar-items' => ['/api/quickbar/items', 'API: Quickbar Items'];
        yield 'game-time' => ['/api/game/time', 'API: Game Time'];
        yield 'active-events' => ['/api/game/events/active', 'API: Active Events'];
    }
}
