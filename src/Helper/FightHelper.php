<?php

namespace App\Helper;

use App\ApiResource\FightResource;
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
     * @return object|null|CharacterInterface
     */
    public function getTarget(FightResource $fight): ?CharacterInterface
    {
        if (!$targetClass = $this->getTargetClass($fight)){
            return null;
        }

        return $this->entityManager->getRepository($targetClass)->find($fight->target);
    }

    private function getTargetClass(FightResource $fight): ?string
    {
        $targetClass = match ($fight->targetType) {
            'mob' => Mob::class,
            'player' => Player::class,
            default => null,
        };

        return $targetClass;
    }
}
