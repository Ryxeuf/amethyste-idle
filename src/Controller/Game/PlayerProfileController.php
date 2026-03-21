<?php

namespace App\Controller\Game;

use App\Entity\App\Player;
use App\Entity\App\PlayerAchievement;
use App\Helper\PlayerHelper;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class PlayerProfileController extends AbstractController
{
    public function __construct(
        private readonly PlayerHelper $playerHelper,
        private readonly EntityManagerInterface $entityManager,
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

        // Domain experiences sorted by total XP desc
        $domainExperiences = $targetPlayer->getDomainExperiences()->toArray();
        usort($domainExperiences, fn ($a, $b) => $b->getTotalExperience() - $a->getTotalExperience());

        // Bestiary stats
        $bestiaryEntries = $targetPlayer->getBestiaryEntries();
        $totalKills = 0;
        foreach ($bestiaryEntries as $entry) {
            $totalKills += $entry->getKillCount();
        }

        return $this->render('game/profile/show.html.twig', [
            'targetPlayer' => $targetPlayer,
            'player' => $currentPlayer,
            'isOwnProfile' => $currentPlayer && $currentPlayer->getId() === $targetPlayer->getId(),
            'completedAchievements' => $completedAchievements,
            'domainExperiences' => $domainExperiences,
            'bestiaryEntries' => $bestiaryEntries,
            'totalKills' => $totalKills,
            'skillCount' => $targetPlayer->getSkills()->count(),
        ]);
    }
}
