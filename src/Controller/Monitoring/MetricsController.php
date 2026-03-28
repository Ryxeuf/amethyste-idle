<?php

namespace App\Controller\Monitoring;

use App\Entity\App\Fight;
use App\Entity\App\Mob;
use App\Entity\App\Player;
use App\Service\Monitoring\MetricsCollector;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class MetricsController extends AbstractController
{
    public function __construct(
        private readonly MetricsCollector $metricsCollector,
        private readonly EntityManagerInterface $em,
    ) {
    }

    #[Route('/metrics', name: 'monitoring_metrics', methods: ['GET'])]
    public function __invoke(): Response
    {
        $this->collectGameGauges();

        $body = $this->metricsCollector->renderPrometheus();

        return new Response($body, Response::HTTP_OK, [
            'Content-Type' => 'text/plain; version=0.0.4; charset=utf-8',
        ]);
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
