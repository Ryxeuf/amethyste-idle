<?php

namespace App\GameEngine\Guild;

use App\Entity\App\WeeklyChallenge;

/**
 * Ce qu'a fait une rotation hebdomadaire (RET-01) — de quoi rendre compte a la
 * console, aux tests et au journal, sans que l'appelant ait a re-interroger la
 * base pour savoir ce qui vient de se passer.
 */
final readonly class WeeklyChallengeRotationResult
{
    /**
     * @param list<WeeklyChallenge> $activeChallenges defis jouables sur la semaine qui commence
     */
    public function __construct(
        public string $weekKey,
        public bool $rotated,
        public string $reason,
        public int $closedChallenges = 0,
        public int $settledProgress = 0,
        public int $awardedBonusPoints = 0,
        public int $createdChallenges = 0,
        public array $activeChallenges = [],
        public ?\DateTimeImmutable $weekStart = null,
        public ?\DateTimeImmutable $weekEnd = null,
    ) {
    }

    public static function skipped(string $weekKey, string $reason): self
    {
        return new self($weekKey, false, $reason);
    }
}
