<?php

namespace App\Tests\Integration\DataFixtures;

use App\DataFixtures\MateriaCatalogFixtures;
use App\Entity\Game\Item;
use App\Entity\Game\Skill;
use App\Tests\Integration\AbstractIntegrationTestCase;

/**
 * MAT-03 — le catalogue derive (GAME_MATERIA §6, invariants 1, 5 et 7).
 *
 * Verifie sur la base reelle ce que la derivation promet : une materia par
 * `unlock` distinct, un slug deductible du sort, et plus aucun nœud d'arbre
 * qui promette ce qui n'existe pas.
 *
 * **Le « catalogue a 200 » du titre d'origine n'est plus la cible** : ARC-07 a
 * pose un gabarit qui plafonne un arbre a ~9 accords, la ou les arbres mesures
 * par MAT-03 en portaient 11. Voir `MEASURED_UNLOCK_FLOOR`.
 */
class MateriaCatalogIntegrationTest extends AbstractIntegrationTestCase
{
    /**
     * Le plancher du catalogue, mesure — et pourquoi ce n'est plus 200.
     *
     * **Le chiffre de GAME_MATERIA §2 a ete mesure sur les arbres d'avant le
     * gabarit, et le gabarit le contredit.** Un arbre au gabarit d'ARC-07 porte
     * **~9 accords** (deux d'entree, deux par palier, un par branche, un au cout
     * du dormant) ; les arbres herites en portent **11,1 en moyenne**. Converti,
     * un arbre *rend* donc des accords au lieu d'en prendre : le Pyromancien en
     * a perdu quatre, le Necromancien cinq.
     *
     * Projection sur les 24 arbres convertis : **~197 accords ecrits**, et moins
     * une fois les partages retires. Le catalogue fini se posera **sous 200**,
     * structurellement — ce n'est pas une perte de contenu, c'est le gabarit qui
     * fait son travail. *Un plancher qu'une decision posterieure rend
     * inatteignable n'est plus un plancher, c'est une alarme qui sonnera a chaque
     * conversion.*
     *
     * Ce qui reste verifie sans concession, et qui porte l'invariant reel : **tout
     * `unlock` a sa materia** et **le catalogue en porte exactement une par
     * `unlock`**. Un nœud qui promet ce qui n'existe pas reste une faute ; un
     * arbre qui promet moins ne l'est pas.
     *
     * Cliquet : la valeur peut baisser au fil d'ARC-08 **en le disant**, et
     * remonter librement.
     *
     * **ARC-08f — le Chevalier, 199 → 197.** L'arbre portait onze accords, le
     * gabarit en autorise sept : les quatre retires sont la Peau metallique, la
     * Regeneration metallique, la Chaine d'eclairs et la Lame d'orichalque.
     * **Deux seulement quittent le catalogue** — la Regeneration metallique et
     * la Chaine d'eclairs sont ouvertes ailleurs, et *un accord partage survit
     * a la conversion de l'un de ses arbres*. C'est la meme mesure qu'ARC-08b
     * sur l'Assassin, pour la meme raison : ***un arbre qui ouvre tout n'ouvre
     * rien***.
     *
     * **ARC-08g — le Defenseur, 197 → 192.** L'arbre portait **treize** accords,
     * le plus charge de ceux convertis a ce jour ; le gabarit en autorise sept.
     * Un seul des retires survit ailleurs (les Pics de pierre, que le Geomancien
     * ouvre aussi). C'est la meme mesure qu'ARC-08b et ARC-08f, et elle se
     * repete parce que la cause est structurelle : *les arbres herites portent
     * onze accords en moyenne, le gabarit en ecrit sept*.
     */
    private const MEASURED_UNLOCK_FLOOR = 192;

    /**
     * @return list<string> les slugs de sort distincts ouverts par un nœud
     */
    private function unlockSlugs(): array
    {
        $slugs = [];
        foreach ($this->em->getRepository(Skill::class)->findAll() as $skill) {
            $actions = $skill->getActions() ?? [];
            $slug = $actions['materia']['unlock'] ?? null;
            if (\is_string($slug) && $slug !== '') {
                $slugs[$slug] = true;
            }
        }

        return array_keys($slugs);
    }

    /**
     * @return array<string, Item> les materia chargees, par slug de sort
     */
    private function materiaBySpell(): array
    {
        $bySpell = [];
        foreach ($this->em->getRepository(Item::class)->findBy(['type' => Item::TYPE_MATERIA]) as $item) {
            $spell = $item->getSpell();
            self::assertNotNull($spell, sprintf('La materia "%s" ne porte aucun sort.', $item->getSlug()));
            $bySpell[$spell->getSlug()] = $item;
        }

        return $bySpell;
    }

    /**
     * Invariants 1 et 7 : aucun nœud ne ment, et le catalogue est complet —
     * une materia par `unlock` distinct, plus les orphelines de MAT-07.
     */
    public function testCatalogMatchesTheTreesPromises(): void
    {
        $unlocks = $this->unlockSlugs();
        $bySpell = $this->materiaBySpell();

        self::assertGreaterThanOrEqual(
            self::MEASURED_UNLOCK_FLOOR,
            \count($unlocks),
            'Le catalogue a perdu des unlocks sans qu\'une conversion d\'arbre le dise.',
        );

        $missing = array_values(array_diff($unlocks, array_keys($bySpell)));
        self::assertSame([], $missing, sprintf('Des nœuds promettent des materia qui n\'existent pas : %s.', implode(', ', \array_slice($missing, 0, 10))));

        self::assertSame(
            \count($unlocks) + \count(MateriaCatalogFixtures::ORPHAN_SPELLS),
            \count($bySpell),
            'Le catalogue porte exactement une materia par unlock distinct, plus les orphelines declarees.',
        );
    }

    /**
     * Invariant 5 : la derivation tient — slug `m<niveau>-<slug du sort>`,
     * element du sort, jamais redeclare.
     */
    public function testDerivationHolds(): void
    {
        foreach ($this->materiaBySpell() as $spellSlug => $item) {
            $spell = $item->getSpell();
            self::assertSame(
                sprintf('m%d-%s', $spell->getLevel(), $spellSlug),
                $item->getSlug(),
                sprintf('Le slug de la materia du sort "%s" ne suit pas la convention.', $spellSlug),
            );
            self::assertSame(
                $spell->getElement(),
                $item->getElement(),
                sprintf('La materia "%s" redeclare un element different de son sort.', $item->getSlug()),
            );
        }
    }
}
