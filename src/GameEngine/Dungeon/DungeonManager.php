<?php

namespace App\GameEngine\Dungeon;

use App\Entity\App\DungeonRun;
use App\Entity\App\Player;
use App\Entity\Game\Dungeon;
use App\Enum\DungeonDifficulty;
use App\Repository\DungeonRunRepository;
use Doctrine\ORM\EntityManagerInterface;

class DungeonManager
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly DungeonRunRepository $dungeonRunRepository,
    ) {
    }

    /**
     * Tente d'entrer dans un donjon. Retourne le DungeonRun cree ou null en cas d'erreur.
     *
     * @return array{run: DungeonRun|null, error: string|null}
     */
    public function enter(Player $player, Dungeon $dungeon, DungeonDifficulty $difficulty): array
    {
        // Verifier qu'il n'y a pas de run en cours
        $activeRun = $this->dungeonRunRepository->findActiveRun($player);
        if ($activeRun !== null) {
            return ['run' => null, 'error' => 'Vous etes deja dans un donjon.'];
        }

        // Verifier le niveau minimum
        if (!$this->meetsLevelRequirement($player, $dungeon)) {
            return ['run' => null, 'error' => sprintf(
                'Experience insuffisante. Ce donjon requiert au moins %d points d\'experience dans un domaine de combat.',
                $dungeon->getMinLevel() * 100,
            )];
        }

        // Verifier le cooldown
        $cooldownRemaining = $this->getCooldownRemaining($player, $dungeon, $difficulty);
        if ($cooldownRemaining > 0) {
            return ['run' => null, 'error' => sprintf(
                'Ce donjon est en cooldown. Reessayez dans %s.',
                $this->formatDuration($cooldownRemaining),
            )];
        }

        // Verifier que le joueur n'est pas en combat
        if ($player->getFight() !== null) {
            return ['run' => null, 'error' => 'Vous ne pouvez pas entrer dans un donjon pendant un combat.'];
        }

        // Creer le run
        $run = new DungeonRun();
        $run->setDungeon($dungeon);
        $run->setPlayer($player);
        $run->setDifficulty($difficulty);
        $run->setOriginMap($player->getMap());
        $run->setOriginCoordinates($player->getCoordinates());

        $this->entityManager->persist($run);

        // Teleporter le joueur dans la carte du donjon
        $player->setLastCoordinates($player->getCoordinates());
        $player->setMap($dungeon->getMap());
        $player->setCoordinates('1.1');

        $this->entityManager->flush();

        return ['run' => $run, 'error' => null];
    }

    /**
     * Termine un run de donjon (victoire ou abandon).
     */
    public function completeRun(DungeonRun $run): void
    {
        $run->setCompletedAt(new \DateTimeImmutable());
        $this->entityManager->flush();
    }

    /**
     * Teleporte le joueur hors du donjon vers sa position d'origine.
     */
    public function teleportPlayerBack(DungeonRun $run): void
    {
        $player = $run->getPlayer();
        $originMap = $run->getOriginMap();
        $originCoords = $run->getOriginCoordinates();

        if ($originMap !== null) {
            $player->setMap($originMap);
            $player->setCoordinates($originCoords ?? '1.1');
            // Clear origin to avoid re-triggering
            $run->setOriginMap(null);
            $run->setOriginCoordinates(null);
        }

        $this->entityManager->flush();
    }

    /**
     * Abandonne un run de donjon (defaite ou abandon volontaire) et teleporte le joueur.
     */
    public function abandonRun(DungeonRun $run): void
    {
        $this->completeRun($run);
        $this->teleportPlayerBack($run);
    }

    /**
     * Temps restant avant de pouvoir relancer un donjon (en secondes).
     */
    public function getCooldownRemaining(Player $player, Dungeon $dungeon, DungeonDifficulty $difficulty): int
    {
        $lastRun = $this->dungeonRunRepository->findLastCompletedRun($player, $dungeon, $difficulty);
        if ($lastRun === null || $lastRun->getCompletedAt() === null) {
            return 0;
        }

        $cooldownEnd = $lastRun->getCompletedAt()->getTimestamp() + $difficulty->cooldownSeconds();
        $remaining = $cooldownEnd - time();

        return max(0, $remaining);
    }

    /**
     * Verifie si le joueur a assez d'experience pour le donjon.
     * Le minLevel du donjon correspond au palier d'experience : minLevel * 100 XP requis dans au moins un domaine de combat.
     */
    public function meetsLevelRequirement(Player $player, Dungeon $dungeon): bool
    {
        $requiredXp = $dungeon->getMinLevel() * 100;

        foreach ($player->getDomainExperiences() as $domainExp) {
            if ($domainExp->getTotalExperience() >= $requiredXp) {
                return true;
            }
        }

        return $requiredXp <= 0;
    }

    /**
     * Formate une duree en secondes en texte lisible.
     */
    private function formatDuration(int $seconds): string
    {
        if ($seconds >= 3600) {
            $hours = (int) floor($seconds / 3600);
            $mins = (int) floor(($seconds % 3600) / 60);

            return $mins > 0 ? sprintf('%dh%02d', $hours, $mins) : sprintf('%dh', $hours);
        }

        if ($seconds >= 60) {
            return sprintf('%d min', (int) ceil($seconds / 60));
        }

        return sprintf('%d sec', $seconds);
    }
}
