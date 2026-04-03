<?php

namespace App\GameEngine\Fight;

use App\Entity\App\Fight;
use App\Helper\PlayerHelper;
use App\Repository\FightRepository;
use Doctrine\ORM\EntityNotFoundException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class FightChecker
{
    public function __construct(
        private readonly FightRepository $fightRepository,
        private readonly PlayerHelper $playerHelper,
    ) {
    }

    /**
     * @throws EntityNotFoundException
     * @throws AccessDeniedException
     */
    public function checkFight(Fight|int $fight, int $targetId, string $targetType): Fight
    {
        if (is_int($fight)) {
            $fight = $this->fightRepository->findWithRelations($fight);
            if ($fight === null) {
                throw new EntityNotFoundException();
            }
        }
        $player = $this->playerHelper->getPlayer();
        if ($fight !== $player->getFight()) {
            throw new AccessDeniedException();
        }
        if ($targetType === 'player') {
            $playerIds = array_map(fn ($player) => $player->getId(), $fight->getPlayers()->toArray());
            $this->validatePlayerId($targetId, $playerIds);
        }
        if ($targetType === 'mob') {
            $mobIds = array_map(fn ($mob) => $mob->getId(), $fight->getMobs()->toArray());
            $this->validateMobId($targetId, $mobIds);
        }

        return $fight;
    }

    private function validatePlayerId(int $targetId, array $playerIds): void
    {
        if (!in_array($targetId, $playerIds)) {
            throw new AccessDeniedException("Le joueur n'appartient pas à ce combat");
        }
    }

    private function validateMobId(int $targetId, array $mobIds): void
    {
        if (!in_array($targetId, $mobIds)) {
            throw new AccessDeniedException("Le monstre n'appartient pas à ce combat");
        }
    }
}
