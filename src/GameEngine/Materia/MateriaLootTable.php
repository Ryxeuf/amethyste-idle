<?php

namespace App\GameEngine\Materia;

use App\Entity\Game\Item;
use App\Entity\Game\Monster;
use App\Enum\Element;
use App\Enum\MonsterRank;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Le butin de materia derive (MAT-05, GAME_MATERIA §4.2).
 *
 * La regle actee : **un monstre lache des materia de son element, a un palier
 * borne par son palier de monde** — la table cesse d'etre ecrite a la main,
 * elle se derive comme la materia elle-meme. C'est la voie normale et
 * abondante du canon (« abondante a la base, rare au sommet »).
 *
 * Bornes : m1-m3 en voie normale (le palier suit le tier, plafonne a m3),
 * m4 en rare (reserve au T4, jamais sur le tout-venant), **m5 jamais en
 * butin** — le haut du catalogue passe par les coffres et les donjons
 * (MAT-06). Un monstre sans element (les mannequins) ne lache rien.
 */
final class MateriaLootTable
{
    /**
     * Fourchette de probabilite canonique : 4-10 % (GAME_MATERIA §4.2).
     *
     * @var array<string, int>
     */
    public const DROP_CHANCE = [
        'common' => 4,
        'elite' => 7,
        'boss' => 10,
    ];

    /**
     * Palier de materia par palier de monde — plafonne a m3 en voie normale.
     *
     * @var array<int, int>
     */
    public const TIER_CAP = [0 => 0, 1 => 1, 2 => 2, 3 => 3, 4 => 3];

    /**
     * La chance (sur 100) qu'un butin reussi du T4 elite ou boss monte a m4.
     */
    public const RARE_UPGRADE_CHANCE = 20;

    /**
     * MAT-06 — les coffres d'exploration : la chance qu'un coffre contienne
     * une materia en plus de ses gils. Palier m3 dans les zones T1-T2, m4
     * dans les zones T3-T4 — le palier moyen et haut, indexe sur la zone.
     */
    public const CHEST_MATERIA_CHANCE = 10;

    /**
     * MAT-06 — les donjons prennent m4-m5 : la chance qu'une recompense de
     * donjon monte a m5 — le seul canal du sommet du catalogue.
     */
    public const DUNGEON_M5_CHANCE = 20;

    public function __construct(private readonly EntityManagerInterface $entityManager)
    {
    }

    /**
     * Tire le butin de materia d'un monstre — `null` si rien ne tombe.
     *
     * Les jets sont injectables (0-99) pour rendre le tirage testable sans
     * toucher au generateur : un `null` tire au hasard.
     */
    public function roll(Monster $monster, float $dropMultiplier = 1.0, ?int $chanceRoll = null, ?int $rareRoll = null, ?int $pickRoll = null): ?Item
    {
        if ($monster->getElement() === Element::None) {
            return null;
        }

        $chance = min(100, (int) round(self::DROP_CHANCE[$monster->getRank()->value] * $dropMultiplier));
        if (($chanceRoll ?? random_int(0, 99)) >= $chance) {
            return null;
        }

        return $this->pick($monster, $rareRoll, $pickRoll);
    }

    /**
     * Choisit la materia d'un butin reussi : l'element du monstre, au palier
     * borne — en redescendant d'un palier quand l'element n'a rien a offrir
     * a celui vise (un butin reussi ne doit pas s'evaporer sur un trou de
     * catalogue).
     */
    public function pick(Monster $monster, ?int $rareRoll = null, ?int $pickRoll = null): ?Item
    {
        $palier = self::TIER_CAP[$monster->getTier()] ?? 0;
        if ($palier < 1) {
            return null;
        }

        // m4 en rare : reserve au T4, jamais sur le tout-venant.
        if ($monster->getTier() >= 4 && $monster->getRank() !== MonsterRank::Common
            && ($rareRoll ?? random_int(0, 99)) < self::RARE_UPGRADE_CHANCE) {
            $palier = 4;
        }

        return $this->pickForPalier($monster->getElement(), $palier, $pickRoll);
    }

    /**
     * MAT-06 — le coffre d'exploration : m3-m4 indexe sur le palier de la
     * zone, d'un element tire au hasard. `null` neuf fois sur dix — le coffre
     * garde ses gils, la materia est le bonus.
     */
    public function chestRoll(int $zoneTier, ?int $chanceRoll = null, ?int $elementRoll = null, ?int $pickRoll = null): ?Item
    {
        if (($chanceRoll ?? random_int(0, 99)) >= self::CHEST_MATERIA_CHANCE) {
            return null;
        }

        return $this->pickForPalier($this->randomElement($elementRoll), self::chestPalier($zoneTier), $pickRoll);
    }

    /**
     * Le palier d'un coffre suit la zone : m3 en T1-T2, m4 en T3-T4.
     */
    public static function chestPalier(int $zoneTier): int
    {
        return $zoneTier >= 3 ? 4 : 3;
    }

    /**
     * MAT-06 — la recompense de donjon : m4 garanti, m5 en rare. Le seul
     * canal du sommet du catalogue — la premiere raison mecanique d'entrer.
     */
    public function dungeonPick(?int $rareRoll = null, ?int $elementRoll = null, ?int $pickRoll = null): ?Item
    {
        $palier = (($rareRoll ?? random_int(0, 99)) < self::DUNGEON_M5_CHANCE) ? 5 : 4;

        return $this->pickForPalier($this->randomElement($elementRoll), $palier, $pickRoll);
    }

    private function randomElement(?int $elementRoll = null): Element
    {
        $elements = array_values(array_filter(Element::cases(), static fn (Element $e): bool => $e !== Element::None));

        return $elements[($elementRoll ?? random_int(0, 99)) % \count($elements)];
    }

    private function pickForPalier(Element $element, int $palier, ?int $pickRoll = null): ?Item
    {
        $repository = $this->entityManager->getRepository(Item::class);
        for (; $palier >= 1; --$palier) {
            /** @var list<Item> $candidates */
            $candidates = $repository->findBy([
                'type' => Item::TYPE_MATERIA,
                'element' => $element,
                'level' => $palier,
            ]);

            if ([] !== $candidates) {
                return $candidates[($pickRoll ?? random_int(0, 99)) % \count($candidates)];
            }
        }

        return null;
    }
}
