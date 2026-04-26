<?php

namespace App\Tests\Unit\Controller\Monitoring;

use App\Controller\Monitoring\MetricsController;
use App\Service\Monitoring\MetricsCollector;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Cache\Adapter\ArrayAdapter;

/**
 * Spy minimal qui compte les invocations de collectGameGauges sans toucher
 * a la chaine EntityManager + QueryBuilder + Query (le test cible uniquement
 * la logique de gating par cle de fraicheur).
 */
final class SpyMetricsController extends MetricsController
{
    public int $collectCallCount = 0;

    protected function collectGameGauges(): void
    {
        ++$this->collectCallCount;
    }
}

class MetricsControllerTest extends TestCase
{
    private ArrayAdapter $cache;
    private MetricsCollector $collector;
    private EntityManagerInterface $em;

    protected function setUp(): void
    {
        $this->cache = new ArrayAdapter();
        $this->collector = new MetricsCollector(new ArrayAdapter());
        $this->em = $this->createMock(EntityManagerInterface::class);
    }

    public function testFirstCallCollectsGameGaugesAndPopulatesFreshnessKey(): void
    {
        $controller = new SpyMetricsController($this->collector, $this->em, $this->cache);

        $response = $controller();

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame(1, $controller->collectCallCount);
        $this->assertTrue($this->cache->getItem('metrics_gauges_collected')->isHit());
    }

    public function testSecondCallSkipsCollectionWhileFreshnessKeyIsHit(): void
    {
        $controller = new SpyMetricsController($this->collector, $this->em, $this->cache);

        $controller();
        $controller();

        $this->assertSame(1, $controller->collectCallCount);
    }

    public function testCollectionRunsAgainAfterFreshnessKeyIsDeleted(): void
    {
        $controller = new SpyMetricsController($this->collector, $this->em, $this->cache);

        $controller();
        $this->cache->deleteItem('metrics_gauges_collected');
        $controller();

        $this->assertSame(2, $controller->collectCallCount);
    }

    public function testResponseExposesPersistedGaugesViaCollector(): void
    {
        // Pre-seed les gauges via le collector, puis verifie que le rendu Prometheus
        // les expose meme quand le spy ne fait rien d'autre que compter.
        $this->collector->setGauge('players_online', 42.0);
        $this->collector->setGauge('mobs_alive', 17.0);
        $this->collector->setGauge('fights_active', 3.0);

        $controller = new SpyMetricsController($this->collector, $this->em, $this->cache);

        $response = $controller();

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('text/plain; version=0.0.4; charset=utf-8', $response->headers->get('Content-Type'));
        $this->assertStringContainsString('amethyste_players_online 42', $response->getContent());
        $this->assertStringContainsString('amethyste_mobs_alive 17', $response->getContent());
        $this->assertStringContainsString('amethyste_fights_active 3', $response->getContent());
    }
}
