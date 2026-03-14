<?php

namespace App\Helper;

use App\Entity\App\Fight;
use App\Entity\App\Mob;
use App\Entity\App\Player;
use App\Entity\CharacterInterface;
use Doctrine\ORM\EntityManagerInterface;

class FightHelper
{
    public function __construct(private readonly EntityManagerInterface $entityManager)
    {
    }

    /**
     * @return object|CharacterInterface|null
     */
    public function getTarget(Fight $fight): ?CharacterInterface
    {
        if (!$targetClass = $this->getTargetClass($fight)) {
            return null;
        }

        return $this->entityManager->getRepository($targetClass)->find($fight->target);
    }

    private function getTargetClass(Fight $fight): ?string
    {
        $targetClass = match ($fight->targetType) {
            'mob' => Mob::class,
            'player' => Player::class,
            default => null,
        };

        return $targetClass;
    }
}
