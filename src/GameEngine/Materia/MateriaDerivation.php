<?php

namespace App\GameEngine\Materia;

use App\Entity\Game\Spell;

/**
 * Une materia ne s'ecrit pas, elle se derive (MAT-02, GAME_MATERIA §2.1).
 *
 * Tout ce qu'une materia est vient du sort qu'elle porte : l'element n'est
 * jamais redeclare, le nom reprend celui du sort, le slug suit la convention
 * actee `m<niveau du sort>-<slug du sort>` — deductible, donc verifiable par
 * un test, et la collision devient impossible puisque les slugs de sort sont
 * uniques. Prix et cout en energie sortent des grilles par palier, figees sur
 * les medianes observees des 68 materia livrees.
 *
 * La rarete n'apparait nulle part ici : elle se lit du prefixe de slug
 * (`ItemFixtures::inferRarity()`). La declarer serait creer une deuxieme
 * source de verite.
 */
final class MateriaDerivation
{
    /**
     * Grille de prix par palier (GAME_MATERIA §2.3).
     *
     * @var array<int, int>
     */
    public const PRICES = [1 => 130, 2 => 180, 3 => 280, 4 => 320, 5 => 380];

    /**
     * Grille de cout en energie par palier (GAME_MATERIA §2.3).
     *
     * @var array<int, int>
     */
    public const ENERGY_COSTS = [1 => 10, 2 => 15, 3 => 20, 4 => 25, 5 => 30];

    /**
     * La convention de slug actee : `m<niveau du sort>-<slug du sort>`.
     */
    public function slugFor(Spell $spell): string
    {
        return sprintf('m%d-%s', $spell->getLevel(), $spell->getSlug());
    }

    public function derive(Spell $spell): MateriaBlueprint
    {
        $tier = $spell->getLevel();
        if (!isset(self::PRICES[$tier], self::ENERGY_COSTS[$tier])) {
            throw new \InvalidArgumentException(sprintf(
                'Le sort "%s" est de niveau %d : la grille de derivation ne connait que les paliers 1 a 5.',
                $spell->getSlug(),
                $tier,
            ));
        }

        return new MateriaBlueprint(
            slug: $this->slugFor($spell),
            name: sprintf('Matéria : %s', $spell->getName()),
            nameTranslations: $this->deriveTranslations($spell->getNameTranslations(), 'Materia: %s'),
            description: sprintf('Matéria contenant le sort « %s ».', $spell->getName()),
            descriptionTranslations: $this->deriveTranslations($spell->getNameTranslations(), 'Materia containing the "%s" spell.'),
            element: $spell->getElement(),
            tier: $tier,
            price: self::PRICES[$tier],
            energyCost: self::ENERGY_COSTS[$tier],
            spellSlug: $spell->getSlug(),
        );
    }

    /**
     * Les traductions suivent celles du sort : une locale que le sort ne
     * traduit pas n'est pas inventee ici.
     *
     * @param array<string, string> $spellTranslations
     *
     * @return array<string, string>
     */
    private function deriveTranslations(array $spellTranslations, string $pattern): array
    {
        $translations = [];
        foreach ($spellTranslations as $locale => $translatedName) {
            $translations[$locale] = sprintf($pattern, $translatedName);
        }

        return $translations;
    }
}
