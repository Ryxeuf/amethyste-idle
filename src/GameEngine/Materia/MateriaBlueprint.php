<?php

namespace App\GameEngine\Materia;

use App\Enum\Element;

/**
 * Ce qu'une materia est, une fois derivee de son sort (MAT-02).
 *
 * Le gabarit ne porte **pas de rarete**, et c'est voulu : la rarete est une
 * fonction du slug (`ItemFixtures::inferRarity()` lit le prefixe m1-…m5-), et
 * le slug une fonction du sort. Une materia dont la rarete serait ecrite en
 * dur est un bug (GAME_MATERIA §2.1).
 */
final readonly class MateriaBlueprint
{
    public const TYPE = 'materia';
    public const SPACE = 1;

    /**
     * @param array<string, string> $nameTranslations
     * @param array<string, string> $descriptionTranslations
     */
    public function __construct(
        public string $slug,
        public string $name,
        public array $nameTranslations,
        public string $description,
        public array $descriptionTranslations,
        public Element $element,
        public int $tier,
        public int $price,
        public int $energyCost,
        public string $spellSlug,
    ) {
    }
}
