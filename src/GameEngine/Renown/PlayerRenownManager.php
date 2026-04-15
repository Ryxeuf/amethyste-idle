<?php

namespace App\GameEngine\Renown;

use App\Entity\App\Player;
use App\Enum\PlayerRenownTier;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Gere la renommee globale d'un joueur (reputation joueur-monde, distincte des factions).
 *
 * Le score de renommee progresse via quetes, succes, evenements et aide au groupe.
 * Il determine un palier (PlayerRenownTier) affiche sur le profil public.
 */
class PlayerRenownManager
{
    private LoggerInterface $logger;

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        ?LoggerInterface $logger = null,
    ) {
        $this->logger = $logger ?? new NullLogger();
    }

    /**
     * Ajoute un montant de renommee au joueur. Les montants negatifs sont bornes a 0.
     * Retourne le nouveau score.
     */
    public function addRenown(Player $player, int $amount, string $reason = ''): int
    {
        if ($amount === 0) {
            return $player->getRenownScore();
        }

        $previousTier = PlayerRenownTier::fromScore($player->getRenownScore());
        $player->addRenownScore($amount);
        $newScore = $player->getRenownScore();
        $newTier = PlayerRenownTier::fromScore($newScore);

        if ($newTier !== $previousTier) {
            $this->logger->info('Player renown tier changed', [
                'player_id' => $player->getId(),
                'previous_tier' => $previousTier->value,
                'new_tier' => $newTier->value,
                'score' => $newScore,
                'reason' => $reason,
            ]);
        }

        $this->entityManager->flush();

        return $newScore;
    }

    public function getTier(Player $player): PlayerRenownTier
    {
        return PlayerRenownTier::fromScore($player->getRenownScore());
    }

    public function getPointsToNextTier(Player $player): ?int
    {
        return PlayerRenownTier::pointsToNextTier($player->getRenownScore());
    }

    /**
     * Montant de renommee accorde a la completion d'une quete.
     * Les quetes daily donnent moins (rejouables), les quetes narratives davantage.
     */
    public function getQuestRewardAmount(bool $isDaily): int
    {
        return $isDaily ? 5 : 25;
    }

    /**
     * Montant de renommee accorde a la validation d'un succes selon la categorie.
     */
    public function getAchievementRewardAmount(string $category): int
    {
        return match ($category) {
            'exploration', 'combat', 'quest', 'progression' => 20,
            'craft', 'gathering', 'social' => 15,
            default => 10,
        };
    }
}
