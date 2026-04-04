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

    public function getTarget(Fight $fight): ?CharacterInterface
    {
        $targetType = $fight->getMetadataValue('targetType');
        $targetId = $fight->getMetadataValue('target');

        if ($targetType === null || $targetId === null) {
            return null;
        }

        $targetClass = match ($targetType) {
            'mob' => Mob::class,
            'player' => Player::class,
            default => null,
        };

        if ($targetClass === null) {
            return null;
        }

        $target = $this->entityManager->getRepository($targetClass)->find($targetId);

        return $target instanceof CharacterInterface ? $target : null;
    }
}
