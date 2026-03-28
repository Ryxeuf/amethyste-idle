<?php

namespace App\Controller\Monitoring;

use App\Service\Monitoring\HealthChecker;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class HealthController extends AbstractController
{
    #[Route('/health', name: 'monitoring_health', methods: ['GET'])]
    public function __invoke(HealthChecker $healthChecker): JsonResponse
    {
        $result = $healthChecker->check();

        $statusCode = $result['status'] === 'healthy'
            ? Response::HTTP_OK
            : Response::HTTP_SERVICE_UNAVAILABLE;

        return $this->json($result, $statusCode);
    }
}
