<?php

namespace App\Tests\Integration\DataFixtures;

use App\Entity\App\Mob;
use App\Entity\App\Pnj;
use App\Entity\App\Zone;
use App\Entity\Game\Dungeon;
use App\Entity\Game\Item;
use App\Enum\Element;
use App\Enum\ItemRarity;
use App\Enum\MonsterRank;
use App\GameEngine\Materia\MateriaLootTable;
use App\Tests\Integration\AbstractIntegrationTestCase;

/**
 * MAT-08 — les invariants 4, 5 et 6 de GAME_MATERIA §6, sur la base reelle.
 *
 * L'invariant 4 est celui qui mord : **toute materia est obtenable par au
 * moins un canal du §4** — boutique PNJ, butin de creature (l'element du
 * monstre, au palier borne par son tier), coffre d'exploration (m3-m4 indexes
 * sur la zone) ou donjon (m4-m5). Une materia sans canal est du contenu mort :
 * le nœud d'arbre promet un sort que personne ne lancera jamais.
 *
 * C'est ce test qui a impose les retiers de MAT-08 : sans monstre de feu,
 * d'air ou de lumiere au T2, vingt et un m2 n'avaient aucun canal.
 */
class MateriaObtainabilityTest extends AbstractIntegrationTestCase
{
    /**
     * Invariant 4 — toute materia du catalogue a au moins un canal.
     *
     * Le butin ne compte que les especes reellement placees (un `Mob` en
     * zone ou en donjon), jamais le seul catalogue : un monstre que rien ne
     * fait apparaitre ne distribue rien.
     */
    public function testEveryMateriaIsObtainable(): void
    {
        // Butin : paliers atteignables par element, sur les especes placees.
        $lootPaliers = [];
        $rareM4Elements = [];
        foreach ($this->em->getRepository(Mob::class)->findAll() as $mob) {
            $monster = $mob->getMonster();
            $element = $monster->getElement();
            if ($element === Element::None) {
                continue;
            }
            $palier = MateriaLootTable::TIER_CAP[$monster->getTier()] ?? 0;
            if ($palier >= 1) {
                $lootPaliers[$element->value][$palier] = true;
            }
            if ($monster->getTier() >= 4 && $monster->getRank() !== MonsterRank::Common) {
                $rareM4Elements[$element->value] = true;
            }
        }

        // Boutiques : tout slug vendu par un PNJ.
        $shopSlugs = [];
        foreach ($this->em->getRepository(Pnj::class)->findAll() as $pnj) {
            foreach ($pnj->getShopItems() ?? [] as $slug) {
                $shopSlugs[(string) $slug] = true;
            }
        }

        // Coffres : les paliers que les zones du graphe ouvrent (m3 en T1-T2,
        // m4 en T3-T4) — d'un element tire au hasard, donc valables pour tous.
        $chestPaliers = [];
        foreach ($this->em->getRepository(Zone::class)->findAll() as $zone) {
            if ($zone->getTier() >= 1) {
                $chestPaliers[MateriaLootTable::chestPalier($zone->getTier())] = true;
            }
        }

        // Donjons : m4 garanti, m5 en rare — le seul canal du sommet.
        $hasDungeon = [] !== $this->em->getRepository(Dungeon::class)->findAll();

        $unobtainable = [];
        foreach ($this->em->getRepository(Item::class)->findBy(['type' => Item::TYPE_MATERIA]) as $materia) {
            $element = $materia->getElement();
            $palier = (int) $materia->getLevel();
            self::assertNotSame(Element::None, $element, sprintf('La materia "%s" ne porte aucun element.', $materia->getSlug()));

            $obtainable = isset($shopSlugs[(string) $materia->getSlug()])
                || ($palier <= 3 && isset($lootPaliers[$element->value][$palier]))
                || (4 === $palier && isset($rareM4Elements[$element->value]))
                || isset($chestPaliers[$palier])
                || ($hasDungeon && \in_array($palier, [4, 5], true));

            if (!$obtainable) {
                $unobtainable[] = $materia->getSlug();
            }
        }

        sort($unobtainable);
        self::assertSame(
            [],
            $unobtainable,
            sprintf('Des materia n\'ont aucun canal d\'obtention (GAME_MATERIA §6, invariant 4) : %s.', implode(', ', \array_slice($unobtainable, 0, 25))),
        );
    }

    /**
     * Invariant 5 (complement) — la rarete se deduit du palier, comme le slug
     * et l'element se deduisent du sort : m1 → Uncommon, m2 → Rare,
     * m3 → Epic, m4-m5 → Legendary. Jamais declaree en donnees.
     */
    public function testRarityFollowsThePalier(): void
    {
        foreach ($this->em->getRepository(Item::class)->findBy(['type' => Item::TYPE_MATERIA]) as $materia) {
            $expected = match ((int) $materia->getLevel()) {
                1 => ItemRarity::Uncommon,
                2 => ItemRarity::Rare,
                3 => ItemRarity::Epic,
                default => ItemRarity::Legendary,
            };

            self::assertSame(
                $expected->value,
                $materia->getRarity(),
                sprintf('La rarete de "%s" ne suit pas son palier (GAME_MATERIA §6, invariant 5).', $materia->getSlug()),
            );
        }
    }

    /**
     * Invariant 6 — aucune materia n'est consommable : le catalogue entier
     * porte le nb_usages illimite (-1), en base comme en fixtures (MAT-07).
     */
    public function testNoMateriaIsConsumable(): void
    {
        foreach ($this->em->getRepository(Item::class)->findBy(['type' => Item::TYPE_MATERIA]) as $materia) {
            self::assertSame(
                -1,
                $materia->getNbUsages(),
                sprintf('La materia "%s" porte des charges finies (GAME_MATERIA §6, invariant 6).', $materia->getSlug()),
            );
        }
    }
}
