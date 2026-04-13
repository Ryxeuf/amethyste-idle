<?php

namespace App\Controller\Game;

use App\Entity\App\PlayerItem;
use App\Entity\App\Region;
use App\GameEngine\GoldSink\GoldSinkManager;
use App\Helper\PlayerHelper;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/game/services')]
class GoldSinkController extends AbstractController
{
    public function __construct(
        private readonly PlayerHelper $playerHelper,
        private readonly GoldSinkManager $goldSinkManager,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    #[Route('', name: 'app_game_services', methods: ['GET'])]
    public function index(): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $player = $this->playerHelper->getPlayer();

        $destinations = $this->goldSinkManager->getAvailableDestinations($player);

        $repairableItems = [];
        foreach ($player->getInventories() as $inventory) {
            foreach ($inventory->getItems() as $playerItem) {
                if ($playerItem->getGear() === 0) {
                    continue;
                }
                $cost = $this->goldSinkManager->getRepairCost($playerItem);
                if ($cost > 0) {
                    $repairableItems[] = ['item' => $playerItem, 'cost' => $cost];
                }
            }
        }

        return $this->render('game/gold_sink/index.html.twig', [
            'player' => $player,
            'destinations' => $destinations,
            'travelCost' => GoldSinkManager::TRAVEL_BASE_COST,
            'renameCost' => GoldSinkManager::RENAME_COST,
            'repairableItems' => $repairableItems,
        ]);
    }

    #[Route('/rename/{id}', name: 'app_game_services_rename', methods: ['POST'])]
    public function rename(int $id, Request $request): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $player = $this->playerHelper->getPlayer();
        $playerItem = $this->entityManager->getRepository(PlayerItem::class)->find($id);

        if (!$playerItem || !$this->ownsItem($playerItem)) {
            return new JsonResponse(['error' => 'Objet introuvable.'], Response::HTTP_NOT_FOUND);
        }

        $data = json_decode($request->getContent(), true);
        $newName = $data['name'] ?? '';

        $result = $this->goldSinkManager->renameItem($player, $playerItem, $newName);

        if (!$result['success']) {
            return new JsonResponse(['error' => $result['message']], Response::HTTP_BAD_REQUEST);
        }

        return new JsonResponse(['success' => true, 'message' => $result['message'], 'gils' => $player->getGils()]);
    }

    #[Route('/travel', name: 'app_game_services_travel', methods: ['POST'])]
    public function travel(Request $request): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $player = $this->playerHelper->getPlayer();

        $data = json_decode($request->getContent(), true);
        $regionId = (int) ($data['regionId'] ?? 0);

        $region = $this->entityManager->getRepository(Region::class)->find($regionId);
        if (!$region) {
            return new JsonResponse(['error' => 'Region introuvable.'], Response::HTTP_NOT_FOUND);
        }

        $result = $this->goldSinkManager->fastTravel($player, $region);

        if (!$result['success']) {
            return new JsonResponse(['error' => $result['message']], Response::HTTP_BAD_REQUEST);
        }

        return new JsonResponse(['success' => true, 'message' => $result['message'], 'gils' => $player->getGils()]);
    }

    #[Route('/repair/{id}', name: 'app_game_services_repair', methods: ['POST'])]
    public function repair(int $id): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $player = $this->playerHelper->getPlayer();
        $playerItem = $this->entityManager->getRepository(PlayerItem::class)->find($id);

        if (!$playerItem || !$this->ownsItem($playerItem)) {
            return new JsonResponse(['error' => 'Objet introuvable.'], Response::HTTP_NOT_FOUND);
        }

        $result = $this->goldSinkManager->repairItem($player, $playerItem);

        if (!$result['success']) {
            return new JsonResponse(['error' => $result['message']], Response::HTTP_BAD_REQUEST);
        }

        return new JsonResponse(['success' => true, 'message' => $result['message'], 'gils' => $player->getGils()]);
    }

    private function ownsItem(PlayerItem $playerItem): bool
    {
        $player = $this->playerHelper->getPlayer();

        foreach ($player->getInventories() as $inventory) {
            foreach ($inventory->getItems() as $item) {
                if ($item->getId() === $playerItem->getId()) {
                    return true;
                }
            }
        }

        return false;
    }
}
