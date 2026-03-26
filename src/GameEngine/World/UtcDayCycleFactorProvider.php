<?php

declare(strict_types=1);

namespace App\GameEngine\World;

use App\Entity\App\Parameter;
use Doctrine\ORM\EntityManagerInterface;

final class UtcDayCycleFactorProvider implements UtcDayCycleFactorProviderInterface
{
    public const PARAM_NAME = 'world.utc_day_cycle_factor';

    private const float DEFAULT_FACTOR = 1.0;

    private const float MIN_FACTOR = 0.01;

    private const float MAX_FACTOR = 24.0;

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    public function getUtcDayCycleFactor(): float
    {
        return $this->loadFromDatabase();
    }

    private function loadFromDatabase(): float
    {
        $row = $this->entityManager->getRepository(Parameter::class)->findOneBy([
            'name' => self::PARAM_NAME,
        ]);

        if ($row === null) {
            return self::DEFAULT_FACTOR;
        }

        $normalized = str_replace(',', '.', trim($row->getValue()));
        $v = (float) $normalized;

        if ($v < self::MIN_FACTOR || $v > self::MAX_FACTOR) {
            return self::DEFAULT_FACTOR;
        }

        return $v;
    }
}
