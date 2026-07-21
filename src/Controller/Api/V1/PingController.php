<?php

namespace App\Controller\Api\V1;

use App\Api\ApiResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Endpoint de sante de l'API v1 : sert de reference d'implementation
 * pour la convention d'enveloppe ApiResponse (migration API-first, phase 0.1).
 */
#[Route('/api/v1')]
class PingController extends AbstractController
{
    public function __construct(
        #[Autowire('%app.version%')]
        private readonly string $appVersion,
    ) {
    }

    #[Route('/ping', name: 'api_v1_ping', methods: ['GET'])]
    public function ping(): JsonResponse
    {
        return ApiResponse::success([
            'pong' => true,
            'version' => $this->appVersion,
            'serverTime' => (new \DateTimeImmutable())->format(\DateTimeInterface::ATOM),
        ]);
    }
}
