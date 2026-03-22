<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\Entity\App\GameEvent;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/game')]
class ActiveEventsController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $em,
    ) {
    }

    #[Route('/events/active', name: 'api_game_events_active', methods: ['GET'])]
    public function __invoke(): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $events = $this->em->getRepository(GameEvent::class)->findBy([
            'status' => GameEvent::STATUS_ACTIVE,
        ]);

        $data = array_map(fn (GameEvent $e) => [
            'id' => $e->getId(),
            'name' => $e->getName(),
            'type' => $e->getType(),
            'typeLabel' => $e->getTypeLabel(),
            'description' => $e->getDescription(),
            'endsAt' => $e->getEndsAt()->format('c'),
            'mapId' => $e->getMap()?->getId(),
        ], $events);

        return $this->json($data);
    }
}
