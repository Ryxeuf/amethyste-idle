<?php

namespace App\GameEngine\Codex;

use App\Entity\App\Player;
use App\Entity\App\PlayerCodexEntry;
use App\Entity\Game\CodexEntry;
use App\Repository\CodexEntryRepository;
use App\Repository\PlayerCodexEntryRepository;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Deblocage des entrees de Codex par la decouverte (NAR-05). Idempotent :
 * une entree deja debloquee pour un joueur n'est jamais dupliquee.
 */
class CodexUnlockService
{
    public function __construct(
        private readonly CodexEntryRepository $codexEntryRepository,
        private readonly PlayerCodexEntryRepository $playerCodexEntryRepository,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    /**
     * Debloque une entree pour un joueur. Retourne true si le deblocage a eu
     * lieu, false s'il etait deja acquis (idempotence).
     */
    public function unlock(Player $player, CodexEntry $entry): bool
    {
        if ($this->playerCodexEntryRepository->hasUnlocked($player, $entry)) {
            return false;
        }

        $this->entityManager->persist(new PlayerCodexEntry($player, $entry));
        $this->entityManager->flush();

        return true;
    }

    /**
     * Debloque toutes les entrees associees a un declencheur (type + cle) pour
     * le joueur. Retourne le nombre d'entrees nouvellement debloquees.
     */
    public function unlockByTrigger(Player $player, string $unlockType, string $unlockKey): int
    {
        $unlocked = 0;
        foreach ($this->codexEntryRepository->findByUnlock($unlockType, $unlockKey) as $entry) {
            if ($this->unlock($player, $entry)) {
                ++$unlocked;
            }
        }

        return $unlocked;
    }
}
