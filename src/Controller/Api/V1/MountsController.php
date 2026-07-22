<?php

namespace App\Controller\Api\V1;

use App\Api\ApiResponse;
use App\Entity\Game\Mount;
use App\Helper\PlayerHelper;
use App\Repository\PlayerMountRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Catalogue de montures en JSON (migration API-first, ecrans meta).
 * Lecture seule : monter/acheter restent sur les endpoints legacy.
 */
#[Route('/api/v1')]
class MountsController extends AbstractController
{
    public function __construct(
        private readonly PlayerHelper $playerHelper,
        private readonly EntityManagerInterface $entityManager,
        private readonly PlayerMountRepository $playerMountRepository,
    ) {
    }

    #[Route('/mounts', name: 'api_v1_mounts', methods: ['GET'])]
    public function mounts(Request $request): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $player = $this->playerHelper->getPlayer();
        if ($player === null) {
            return ApiResponse::error('not_found', 'Player not found.', 404);
        }

        $locale = $request->getLocale();

        $allowedTypes = Mount::getObtentionTypes();
        $rawFilter = (string) $request->query->get('type', '');
        $selectedFilter = \in_array($rawFilter, $allowedTypes, true) ? $rawFilter : '';

        $criteria = ['enabled' => true];
        if ($selectedFilter !== '') {
            $criteria['obtentionType'] = $selectedFilter;
        }

        $ownedMountIds = $this->playerMountRepository->findOwnedMountIds($player);
        $activeMount = $player->getActiveMount();

        $mounts = [];
        foreach ($this->entityManager->getRepository(Mount::class)->findBy($criteria, ['requiredLevel' => 'ASC', 'gilCost' => 'ASC']) as $mount) {
            $mounts[] = [
                'id' => $mount->getId(),
                'slug' => $mount->getSlug(),
                'name' => $mount->getLocalizedName($locale),
                'description' => $mount->getLocalizedDescription($locale),
                'icon' => $mount->getIconPath(),
                'speedBonus' => $mount->getSpeedBonus(),
                'obtentionType' => $mount->getObtentionType(),
                'gilCost' => $mount->getGilCost(),
                'requiredLevel' => $mount->getRequiredLevel(),
                'owned' => \in_array($mount->getId(), $ownedMountIds, true),
                'active' => $activeMount !== null && $activeMount->getId() === $mount->getId(),
            ];
        }

        return ApiResponse::success([
            'filters' => [
                'available' => $allowedTypes,
                'selected' => $selectedFilter,
            ],
            'playerGils' => $player->getGils(),
            'activeMountId' => $activeMount?->getId(),
            'mounts' => $mounts,
        ]);
    }
}
