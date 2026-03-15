<?php

namespace App\Controller\Api;

use App\Entity\App\Mob;
use App\Entity\App\ObjectLayer;
use App\Entity\Game\Item;
use App\GameEngine\Gathering\GatheringManager;
use App\GameEngine\Gathering\ToolDurabilityManager;
use App\GameEngine\Job\ButcheringManager;
use App\GameEngine\Job\FishingManager;
use App\GameEngine\Job\HarvestManager;
use App\GameEngine\Player\PlayerActionHelper;
use App\Helper\PlayerHelper;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/gathering')]
class GatheringController extends AbstractController
{
    public function __construct(
        private readonly PlayerHelper $playerHelper,
        private readonly EntityManagerInterface $entityManager,
        private readonly GatheringManager $gatheringManager,
        private readonly ToolDurabilityManager $toolDurabilityManager,
        private readonly FishingManager $fishingManager,
        private readonly ButcheringManager $butcheringManager,
        private readonly HarvestManager $harvestManager,
        private readonly PlayerActionHelper $playerActionHelper,
    ) {
    }

    /**
     * Harvest a resource spot (mining, herbalism).
     */
    #[Route('/harvest/{spotId}', name: 'api_gathering_harvest', methods: ['POST'])]
    public function harvest(int $spotId): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_USER');
        $player = $this->playerHelper->getPlayer();

        if (!$player) {
            return $this->json(['error' => 'Player not found'], 404);
        }

        $spot = $this->entityManager->getRepository(ObjectLayer::class)->find($spotId);
        if (!$spot) {
            return $this->json(['error' => 'Spot non trouvé.'], 404);
        }

        if (!$spot->isHarvestSpot()) {
            return $this->json(['error' => 'Ce n\'est pas un spot de récolte.'], 400);
        }

        if (!$spot->isAvailable()) {
            return $this->json([
                'error' => 'Spot indisponible.',
                'remainingSeconds' => $spot->getRemainingRespawnSeconds(),
            ], 400);
        }

        if (!$this->playerActionHelper->canHarvest($spot->getSlug())) {
            return $this->json(['error' => 'Compétence insuffisante.'], 403);
        }

        try {
            $this->harvestManager->checkToolRequirement($player, $spot);
        } catch (\Exception $e) {
            return $this->json(['error' => $e->getMessage()], 400);
        }

        $result = $this->harvestManager->harvestResources($spot, $player);

        return $this->json([
            'success' => count($result['items']) > 0,
            'items' => array_map(fn ($pi) => [
                'name' => $pi->getGenericItem()->getName(),
                'slug' => $pi->getGenericItem()->getSlug(),
            ], $result['items']),
            'respawnDelay' => $spot->getRespawnDelay(),
            'toolBroken' => $result['toolBroken'],
        ]);
    }

    /**
     * Start fishing at the player's current location.
     */
    #[Route('/fish', name: 'api_gathering_fish', methods: ['POST'])]
    public function fish(Request $request): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_USER');
        $player = $this->playerHelper->getPlayer();

        if (!$player) {
            return $this->json(['error' => 'Player not found'], 404);
        }

        // Find a fishing spot at the player's current position
        $spot = $this->entityManager->getRepository(ObjectLayer::class)->findOneBy([
            'coordinates' => $player->getCoordinates(),
            'map' => $player->getMap(),
            'type' => ObjectLayer::TYPE_HARVEST_SPOT,
        ]);

        if (!$spot) {
            return $this->json(['error' => 'Aucun spot de pêche ici.'], 400);
        }

        // Check required tool type
        if ($spot->getRequiredToolType() !== Item::TOOL_TYPE_FISHING_ROD) {
            return $this->json(['error' => 'Ce spot ne permet pas la pêche.'], 400);
        }

        // Use the FishingManager for the actual fishing logic
        $data = json_decode($request->getContent(), true) ?? [];
        $tension = (int) ($data['tension'] ?? 50);

        $result = $this->fishingManager->completeFishing($player, $spot, $tension);

        return $this->json($result);
    }

    /**
     * Skin a dead mob to harvest materials.
     */
    #[Route('/skin/{mobId}', name: 'api_gathering_skin', methods: ['POST'])]
    public function skin(int $mobId): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_USER');
        $player = $this->playerHelper->getPlayer();

        if (!$player) {
            return $this->json(['error' => 'Player not found'], 404);
        }

        $mob = $this->entityManager->getRepository(Mob::class)->find($mobId);

        if (!$mob) {
            return $this->json(['error' => 'Mob non trouvé.'], 404);
        }

        // Check the mob is on the same map as the player
        if ($mob->getMap() === null || $mob->getMap()->getId() !== $player->getMap()->getId()) {
            return $this->json(['error' => 'Ce mob n\'est pas sur votre carte.'], 400);
        }

        // Use the ButcheringManager for the skinning logic
        $result = $this->butcheringManager->butcher($player, $mob);

        return $this->json($result);
    }

    /**
     * List the player's gathering tools and their durability.
     */
    #[Route('/tools', name: 'api_gathering_tools', methods: ['GET'])]
    public function tools(): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_USER');
        $player = $this->playerHelper->getPlayer();

        if (!$player) {
            return $this->json(['error' => 'Player not found'], 404);
        }

        $tools = [];

        foreach ($player->getInventories() as $inventory) {
            if (!$inventory->isBag()) {
                continue;
            }
            foreach ($inventory->getItems() as $playerItem) {
                $genericItem = $playerItem->getGenericItem();
                if (!$genericItem->isTool()) {
                    continue;
                }

                $maxDurability = $genericItem->getDurability();
                $currentDurability = $this->toolDurabilityManager->checkDurability($playerItem);
                $isBroken = $this->toolDurabilityManager->isToolBroken($playerItem);

                $tools[] = [
                    'id' => $playerItem->getId(),
                    'name' => $genericItem->getName(),
                    'slug' => $genericItem->getSlug(),
                    'toolType' => $genericItem->getToolType(),
                    'toolTier' => $genericItem->getToolTier(),
                    'currentDurability' => $currentDurability,
                    'maxDurability' => $maxDurability,
                    'isBroken' => $isBroken,
                ];
            }
        }

        return $this->json(['tools' => $tools]);
    }
}
