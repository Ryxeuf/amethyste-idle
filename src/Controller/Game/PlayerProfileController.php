<?php

namespace App\Controller\Game;

use App\Entity\App\Inventory;
use App\Entity\App\Player;
use App\Entity\App\PlayerAchievement;
use App\Entity\App\PlayerItem;
use App\Entity\Game\Item;
use App\Enum\PlayerRenownTier;
use App\Enum\PlayerReportReason;
use App\GameEngine\Renown\PlayerReportManager;
use App\Helper\PlayerHelper;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class PlayerProfileController extends AbstractController
{
    private const MAX_FEATURED_ACHIEVEMENTS = 5;

    public function __construct(
        private readonly PlayerHelper $playerHelper,
        private readonly EntityManagerInterface $entityManager,
        private readonly PlayerReportManager $reportManager,
    ) {
    }

    #[Route('/game/player/{id}/profile', name: 'app_game_player_profile', methods: ['GET'])]
    public function show(int $id): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $targetPlayer = $this->entityManager->getRepository(Player::class)->find($id);

        if (!$targetPlayer) {
            throw $this->createNotFoundException('Joueur introuvable.');
        }

        $currentPlayer = $this->playerHelper->getPlayer();

        // Completed achievements
        $playerAchievements = $this->entityManager->getRepository(PlayerAchievement::class)->findBy(['player' => $targetPlayer]);
        $completedAchievements = array_filter($playerAchievements, fn (PlayerAchievement $pa) => $pa->isCompleted());

        // Featured achievements (max 5, shown prominently)
        $featuredAchievements = array_filter($completedAchievements, fn (PlayerAchievement $pa) => $pa->isFeatured());
        $featuredAchievements = array_slice(array_values($featuredAchievements), 0, self::MAX_FEATURED_ACHIEVEMENTS);

        // Domain experiences sorted by total XP desc
        $domainExperiences = $targetPlayer->getDomainExperiences()->toArray();
        usort($domainExperiences, fn ($a, $b) => $b->getTotalExperience() - $a->getTotalExperience());

        // Bestiary stats
        $bestiaryEntries = $targetPlayer->getBestiaryEntries();
        $totalKills = 0;
        foreach ($bestiaryEntries as $entry) {
            $totalKills += $entry->getKillCount();
        }

        // Equipped items for the target player
        $equippedItems = $this->getEquippedItemsForPlayer($targetPlayer);

        // Renown (global player reputation)
        $renownScore = $targetPlayer->getRenownScore();
        $renownTier = PlayerRenownTier::fromScore($renownScore);
        $renownPointsToNext = PlayerRenownTier::pointsToNextTier($renownScore);

        return $this->render('game/profile/show.html.twig', [
            'targetPlayer' => $targetPlayer,
            'player' => $currentPlayer,
            'isOwnProfile' => $currentPlayer && $currentPlayer->getId() === $targetPlayer->getId(),
            'completedAchievements' => $completedAchievements,
            'featuredAchievements' => $featuredAchievements,
            'domainExperiences' => $domainExperiences,
            'bestiaryEntries' => $bestiaryEntries,
            'totalKills' => $totalKills,
            'skillCount' => $targetPlayer->getSkills()->count(),
            'equippedItems' => $equippedItems,
            'renownScore' => $renownScore,
            'renownTier' => $renownTier,
            'renownPointsToNext' => $renownPointsToNext,
        ]);
    }

    #[Route('/game/player/{id}/report', name: 'app_game_player_report', methods: ['POST'])]
    public function report(int $id, Request $request): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $reportedPlayer = $this->entityManager->getRepository(Player::class)->find($id);
        if (!$reportedPlayer) {
            throw $this->createNotFoundException('Joueur introuvable.');
        }

        $reporter = $this->playerHelper->getPlayer();
        if (!$reporter || $reporter->getId() === $reportedPlayer->getId()) {
            $this->addFlash('error', 'Vous ne pouvez pas vous signaler vous-meme.');

            return $this->redirectToRoute('app_game_player_profile', ['id' => $id]);
        }

        if (!$this->isCsrfTokenValid('report_player_' . $id, (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Jeton CSRF invalide.');

            return $this->redirectToRoute('app_game_player_profile', ['id' => $id]);
        }

        $reason = PlayerReportReason::tryFrom((string) $request->request->get('reason', ''));
        if ($reason === null) {
            $this->addFlash('error', 'Raison invalide.');

            return $this->redirectToRoute('app_game_player_profile', ['id' => $id]);
        }

        $description = (string) $request->request->get('description', '');

        try {
            $report = $this->reportManager->submitReport($reporter, $reportedPlayer, $reason, $description);
        } catch (\InvalidArgumentException $e) {
            $this->addFlash('error', $e->getMessage());

            return $this->redirectToRoute('app_game_player_profile', ['id' => $id]);
        }

        if ($report === null) {
            $this->addFlash('error', 'Vous avez deja signale ce joueur recemment. Merci d\'attendre 24h.');
        } else {
            $this->addFlash('success', 'Signalement envoye. Un moderateur le traitera prochainement.');
        }

        return $this->redirectToRoute('app_game_player_profile', ['id' => $id]);
    }

    #[Route('/game/player/achievement/{id}/toggle-featured', name: 'app_game_player_achievement_toggle_featured', methods: ['POST'])]
    public function toggleFeatured(int $id): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $currentPlayer = $this->playerHelper->getPlayer();
        $playerAchievement = $this->entityManager->getRepository(PlayerAchievement::class)->find($id);

        if (!$playerAchievement || $playerAchievement->getPlayer()->getId() !== $currentPlayer->getId()) {
            return new JsonResponse(['error' => 'Succes introuvable.'], Response::HTTP_NOT_FOUND);
        }

        if (!$playerAchievement->isCompleted()) {
            return new JsonResponse(['error' => 'Succes non complete.'], Response::HTTP_BAD_REQUEST);
        }

        // If trying to feature, check the limit
        if (!$playerAchievement->isFeatured()) {
            $currentFeaturedCount = $this->entityManager->getRepository(PlayerAchievement::class)->count([
                'player' => $currentPlayer,
                'featured' => true,
            ]);

            if ($currentFeaturedCount >= self::MAX_FEATURED_ACHIEVEMENTS) {
                return new JsonResponse([
                    'error' => 'Vous ne pouvez mettre en avant que ' . self::MAX_FEATURED_ACHIEVEMENTS . ' succes maximum.',
                ], Response::HTTP_BAD_REQUEST);
            }
        }

        $playerAchievement->setFeatured(!$playerAchievement->isFeatured());
        $this->entityManager->flush();

        return new JsonResponse([
            'featured' => $playerAchievement->isFeatured(),
        ]);
    }

    /**
     * @return array<string, PlayerItem|null>
     */
    private function getEquippedItemsForPlayer(Player $targetPlayer): array
    {
        $bagInventory = null;
        foreach ($targetPlayer->getInventories() as $inventory) {
            if ($inventory->getType() === Inventory::TYPE_BAG) {
                $bagInventory = $inventory;
                break;
            }
        }

        $equipped = [];
        if ($bagInventory) {
            foreach (Item::GEAR_LOCATIONS as $location) {
                $gearBit = $this->getGearBitByLocation($location);
                $equipped[$location] = null;
                if ($gearBit !== null) {
                    foreach ($bagInventory->getItems() as $item) {
                        if ($item->getGear() & $gearBit) {
                            $equipped[$location] = $item;
                            break;
                        }
                    }
                }
            }
        }

        return $equipped;
    }

    private function getGearBitByLocation(string $location): ?int
    {
        return match ($location) {
            Item::GEAR_LOCATION_HEAD => PlayerItem::GEAR_HEAD,
            Item::GEAR_LOCATION_NECK => PlayerItem::GEAR_NECK,
            Item::GEAR_LOCATION_CHEST => PlayerItem::GEAR_CHEST,
            Item::GEAR_LOCATION_HAND => PlayerItem::GEAR_HAND,
            Item::GEAR_LOCATION_MAIN_WEAPON, Item::GEAR_LOCATION_MAIN_HAND => PlayerItem::GEAR_MAIN_WEAPON,
            Item::GEAR_LOCATION_SIDE_WEAPON, Item::GEAR_LOCATION_OFF_HAND => PlayerItem::GEAR_SIDE_WEAPON,
            Item::GEAR_LOCATION_BELT => PlayerItem::GEAR_BELT,
            Item::GEAR_LOCATION_LEG, Item::GEAR_LOCATION_LEGS => PlayerItem::GEAR_LEG,
            Item::GEAR_LOCATION_FOOT, Item::GEAR_LOCATION_FEET => PlayerItem::GEAR_FOOT,
            Item::GEAR_LOCATION_RING_1, Item::GEAR_LOCATION_FINGER => PlayerItem::GEAR_RING_1,
            Item::GEAR_LOCATION_RING_2 => PlayerItem::GEAR_RING_2,
            Item::GEAR_LOCATION_SHOULDER => PlayerItem::GEAR_SHOULDER,
            default => null,
        };
    }
}
