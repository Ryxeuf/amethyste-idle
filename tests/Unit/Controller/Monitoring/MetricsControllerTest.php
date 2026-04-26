<?php

namespace App\Tests\Unit\Controller\Monitoring;

use App\Controller\Monitoring\MetricsController;
use App\Service\Monitoring\MetricsCollector;
use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Cache\Adapter\ArrayAdapter;

class MetricsControllerTest extends TestCase
{
    private ArrayAdapter $cache;
    private MetricsCollector $collector;
    private EntityManagerInterface&MockObject $em;
    private EntityRepository&MockObject $fightRepository;

    /** @var QueryBuilder&MockObject */
    private $playerQueryBuilder;
    /** @var QueryBuilder&MockObject */
    private $mobQueryBuilder;
    /** @var AbstractQuery&MockObject */
    private $playerQuery;
    /** @var AbstractQuery&MockObject */
    private $mobQuery;

    private int $playerCountCalls = 0;
    private int $mobCountCalls = 0;
    private int $fightCountCalls = 0;

    protected function setUp(): void
    {
        $this->cache = new ArrayAdapter();
        $this->collector = new MetricsCollector(new ArrayAdapter());
        $this->em = $this->createMock(EntityManagerInterface::class);

        $this->playerQuery = $this->createMock(AbstractQuery::class);
        $this->playerQuery->method('getSingleScalarResult')
            ->willReturnCallback(function () {
                ++$this->playerCountCalls;

                return 42;
            });
        $this->mobQuery = $this->createMock(AbstractQuery::class);
        $this->mobQuery->method('getSingleScalarResult')
            ->willReturnCallback(function () {
                ++$this->mobCountCalls;

                return 17;
            });

        $this->playerQueryBuilder = $this->createMock(QueryBuilder::class);
        $this->playerQueryBuilder->method('select')->willReturnSelf();
        $this->playerQueryBuilder->method('from')->willReturnSelf();
        $this->playerQueryBuilder->method('where')->willReturnSelf();
        $this->playerQueryBuilder->method('setParameter')->willReturnSelf();
        $this->playerQueryBuilder->method('getQuery')->willReturn($this->playerQuery);

        $this->mobQueryBuilder = $this->createMock(QueryBuilder::class);
        $this->mobQueryBuilder->method('select')->willReturnSelf();
        $this->mobQueryBuilder->method('from')->willReturnSelf();
        $this->mobQueryBuilder->method('where')->willReturnSelf();
        $this->mobQueryBuilder->method('getQuery')->willReturn($this->mobQuery);

        $this->em->method('createQueryBuilder')
            ->willReturnOnConsecutiveCalls(
                $this->playerQueryBuilder,
                $this->mobQueryBuilder,
                $this->playerQueryBuilder,
                $this->mobQueryBuilder,
            );

        $this->fightRepository = $this->createMock(EntityRepository::class);
        $this->fightRepository->method('count')
            ->willReturnCallback(function () {
                ++$this->fightCountCalls;

                return 3;
            });
        $this->em->method('getRepository')->willReturn($this->fightRepository);
    }

    public function testFirstCallCollectsGameGaugesAndPopulatesCache(): void
    {
        $controller = new MetricsController($this->collector, $this->em, $this->cache);

        $response = $controller();

        $this->assertSame(200, $response->getStatusCode());
        $this->assertStringContainsString('amethyste_players_online 42', $response->getContent());
        $this->assertStringContainsString('amethyste_mobs_alive 17', $response->getContent());
        $this->assertStringContainsString('amethyste_fights_active 3', $response->getContent());
        $this->assertSame(1, $this->playerCountCalls);
        $this->assertSame(1, $this->mobCountCalls);
        $this->assertSame(1, $this->fightCountCalls);
        $this->assertTrue($this->cache->getItem('metrics_gauges_collected')->isHit());
    }

    public function testSecondCallSkipsCollectionWhileFreshnessKeyIsHit(): void
    {
        $controller = new MetricsController($this->collector, $this->em, $this->cache);

        $controller();
        $response = $controller();

        $this->assertSame(200, $response->getStatusCode());
        // Les valeurs sont toujours rendues car elles ont ete persistees lors du 1er appel.
        $this->assertStringContainsString('amethyste_players_online 42', $response->getContent());
        $this->assertStringContainsString('amethyste_mobs_alive 17', $response->getContent());
        $this->assertStringContainsString('amethyste_fights_active 3', $response->getContent());
        // Mais aucune nouvelle requete n'a ete declenchee.
        $this->assertSame(1, $this->playerCountCalls);
        $this->assertSame(1, $this->mobCountCalls);
        $this->assertSame(1, $this->fightCountCalls);
    }

    public function testCollectionRunsAgainAfterFreshnessExpires(): void
    {
        $controller = new MetricsController($this->collector, $this->em, $this->cache);

        $controller();
        $this->cache->deleteItem('metrics_gauges_collected');
        $controller();

        $this->assertSame(2, $this->playerCountCalls);
        $this->assertSame(2, $this->mobCountCalls);
        $this->assertSame(2, $this->fightCountCalls);
    }

    public function testResponseHasPrometheusContentType(): void
    {
        $controller = new MetricsController($this->collector, $this->em, $this->cache);

        $response = $controller();

        $this->assertSame('text/plain; version=0.0.4; charset=utf-8', $response->headers->get('Content-Type'));
    }
}
