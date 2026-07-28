<?php

namespace App\GameEngine\Retention;

use App\Enum\InfluenceActivityType;

/**
 * Un gabarit de commission hebdomadaire (RET-02).
 *
 * Objet de valeur plutot que tableau : le pool est declaratif, mais ce qui en
 * sort circule dans le code et merite d'etre type.
 */
final readonly class WeeklyCommissionTemplate
{
    public function __construct(
        public string $slug,
        public InfluenceActivityType $activity,
        public string $domain,
        public string $title,
        public string $titleEn,
        public string $description,
        public string $descriptionEn,
        public int $target,
    ) {
    }
}
