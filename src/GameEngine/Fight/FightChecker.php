<?php

namespace App\GameEngine\Fight;

use App\Entity\App\Fight;
use App\Helper\PlayerHelper;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityNotFoundException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class FightChecker
{
    /**
     * FightChecker constructor.
     */
    public function __construct(private readonly EntityManagerInterface $entityManager, private readonly PlayerHelper $playerHelper)
    {
    }

    /**
     * @throws EntityNotFoundException
     * @throws AccessDeniedException
     */
    public function checkFight(Fight|int $fight): Fight
    {
        if (is_int($fight)) {
            /** @var Fight $fight */
            $fight = $this->entityManager->getRepository(Fight::class)->find($fight);
        }
        if($fight === null){
            throw new EntityNotFoundException();
        }
        $player = $this->playerHelper->getPlayer();
        if($fight !== $player->getFight()) {
            throw new AccessDeniedException();
        }

        return $fight;
    }
}
