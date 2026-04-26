<?php

namespace App\Controller\Monitoring;

use App\Entity\App\Fight;
use App\Entity\App\Mob;
use App\Entity\App\Player;
use App\Service\Monitoring\MetricsCollector;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class MetricsController extends AbstractController
{
    private const GAUGES_FRESHNESS_KEY = 'metrics_gauges_collected';
    private const GAUGES_FRESHNESS_TTL_SECONDS = 10;

    public function __construct(
        private readonly MetricsCollector $metricsCollector,
        private readonly EntityManagerInterface $em,
        private readonly CacheItemPoolInterface $cache,
    ) {
    }

    #[Route('/metrics', name: 'monitoring_metrics', methods: ['GET'])]
    public function __invoke(): Response
    {
        $this->maybeCollectGameGauges();

        $body = $this->metricsCollector->renderPrometheus();

        return new Response($body, Response::HTTP_OK, [
            'Content-Type' => 'text/plain; version=0.0.4; charset=utf-8',
        ]);
    }

    private function maybeCollectGameGauges(): void
    {
        $freshness = $this->cache->getItem(self::GAUGES_FRESHNESS_KEY);
        if ($freshness->isHit()) {
            return;
        }

        $this->collectGameGauges();

        $freshness->set(true);
        $freshness->expiresAfter(self::GAUGES_FRESHNESS_TTL_SECONDS);
        $this->cache->save($freshness);
    }

    private function collectGameGauges(): void
    {
        $connectedThreshold = new \DateTimeImmutable('-15 minutes');

        $activePlayers = (int) $this->em->createQueryBuilder()
            ->select('COUNT(p.id)')
            ->from(Player::class, 'p')
            ->where('p.updatedAt >= :since')
            ->setParameter('since', $connectedThreshold)
            ->getQuery()
            ->getSingleScalarResult();

        $activeFights = $this->em->getRepository(Fight::class)->count(['inProgress' => true]);

        $aliveMobs = (int) $this->em->createQueryBuilder()
            ->select('COUNT(m.id)')
            ->from(Mob::class, 'm')
            ->where('m.diedAt IS NULL')
            ->getQuery()
            ->getSingleScalarResult();

        $this->metricsCollector->setGauge('players_online', (float) $activePlayers);
        $this->metricsCollector->setGauge('fights_active', (float) $activeFights);
        $this->metricsCollector->setGauge('mobs_alive', (float) $aliveMobs);
    }
}
