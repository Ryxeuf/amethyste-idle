<?php

namespace App\Controller\Game;

use App\Entity\App\Mob;
use App\Entity\App\ObjectLayer;
use App\GameEngine\Job\ButcheringManager;
use App\GameEngine\Job\FishingManager;
use App\Helper\PlayerHelper;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class HarvestController extends AbstractController
{
    public function __construct(
        private readonly FishingManager $fishingManager,
        private readonly ButcheringManager $butcheringManager,
        private readonly PlayerHelper $playerHelper,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    /**
     * Démarre ou termine une session de pêche.
     * POST avec action=start : démarre la pêche
     * POST avec action=complete + tension : termine la pêche
     */
    #[Route('/game/harvest/fish', name: 'app_game_harvest_fish', methods: ['POST'])]
    public function fish(Request $request): JsonResponse
    {
        $player = $this->playerHelper->getPlayer();
        if ($player === null) {
            return $this->json(['error' => 'Joueur non trouvé.'], Response::HTTP_UNAUTHORIZED);
        }

        $spotId = $request->request->getInt('spot_id');
        $spot = $this->entityManager->getRepository(ObjectLayer::class)->find($spotId);

        if ($spot === null) {
            return $this->json(['error' => 'Spot de pêche non trouvé.'], Response::HTTP_NOT_FOUND);
        }

        $action = $request->request->getString('action', 'start');

        if ($action === 'start') {
            $result = $this->fishingManager->startFishing($player, $spot);

            return $this->json($result, $result['started'] ? Response::HTTP_OK : Response::HTTP_BAD_REQUEST);
        }

        if ($action === 'complete') {
            $tension = $request->request->getInt('tension', 50);
            $tension = max(0, min(100, $tension));

            $result = $this->fishingManager->completeFishing($player, $spot, $tension);

            return $this->json($result);
        }

        return $this->json(['error' => 'Action inconnue.'], Response::HTTP_BAD_REQUEST);
    }

    /**
     * Dépèce un mob vaincu.
     */
    #[Route('/game/harvest/butcher/{mobId}', name: 'app_game_harvest_butcher', methods: ['POST'])]
    public function butcher(int $mobId): JsonResponse
    {
        $player = $this->playerHelper->getPlayer();
        if ($player === null) {
            return $this->json(['error' => 'Joueur non trouvé.'], Response::HTTP_UNAUTHORIZED);
        }

        $mob = $this->entityManager->getRepository(Mob::class)->find($mobId);
        if ($mob === null) {
            return $this->json(['error' => 'Mob non trouvé.'], Response::HTTP_NOT_FOUND);
        }

        if (!$this->butcheringManager->canButcher($player)) {
            return $this->json(
                ['error' => 'Vous n\'avez pas de couteau de dépeçage.'],
                Response::HTTP_BAD_REQUEST
            );
        }

        $result = $this->butcheringManager->butcher($player, $mob);

        return $this->json($result);
    }
}
