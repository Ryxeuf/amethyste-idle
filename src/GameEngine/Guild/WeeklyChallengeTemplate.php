<?php

namespace App\GameEngine\Guild;

use App\Enum\InfluenceActivityType;

/**
 * Un gabarit de defi hebdomadaire, tel qu'ecrit dans
 * `config/game/weekly_challenges.yaml` (RET-01).
 *
 * Un objet plutot qu'un tableau associatif : la rotation lit ces champs a
 * chaque semaine creee, et une faute de frappe sur une cle doit tomber a
 * l'analyse statique, pas un lundi a minuit.
 */
final readonly class WeeklyChallengeTemplate
{
    public function __construct(
        public string $slug,
        public InfluenceActivityType $activity,
        public string $title,
        public ?string $titleEn,
        public string $description,
        public ?string $descriptionEn,
        public int $target,
        public int $bonusPoints,
    ) {
    }
}
