<?php

namespace App\Tests\Unit\GameEngine\Progression;

use App\GameEngine\Progression\CombatBranchCatalog;
use App\GameEngine\Progression\CombatBranchDefinitionException;
use App\GameEngine\Progression\SkillCostScale;
use PHPUnit\Framework\TestCase;

/**
 * Les fourches des arbres de combat (ARC-14a).
 *
 * GAME_ARCHETYPES § 6.1 bis. **Deux pyromanciens finis etaient identiques.**
 * Le palier 3 ecrit deux branches et une seule s'apprend — et c'est cette
 * regle, seule, qui reconcilie les deux nombres du canon.
 */
class CombatBranchCatalogTest extends TestCase
{
    /**
     * Les vingt arbres qui n'ont pas encore de fourche.
     *
     * Le canon n'en nomme que **quatre** (§ 9.1 → 9.4) ; inventer les vingt
     * autres serait ecrire du contenu sans l'avoir instruit. La liste est
     * nommee plutot que tue, et elle est un **cliquet** : elle peut retrecir —
     * c'est le travail d'ARC-08 —, jamais grandir.
     *
     * @var list<string>
     */
    private const WAITING_ON_ARC_08 = [
        'Assassin', 'Berserker', 'Chasseur', 'Chevalier', 'Defender', 'Dompteur',
        'Druide', 'Foudromancien', 'Gardien', 'Geomancien', 'Hydromancien',
        'Ingenieur', 'Inquisiteur', 'Maremancien', 'Paladin',
        'Pretre', 'Sorcier', 'Vagabond', 'Artificier',
    ];

    private function catalog(): CombatBranchCatalog
    {
        return new CombatBranchCatalog(\dirname(__DIR__, 4));
    }

    /**
     * Les quatre arbres patrons ont leur fourche, nommee par le canon — plus
     * le Necromancien, premier arbre de controle (ARC-08a).
     *
     * Le controle est la fonction que les quatre patrons ne couvrent pas :
     * ARC-07 a livre l'assaut deux fois, l'entretien et l'encaisse. Sa fourche
     * n'est donc pas « la cinquieme » — c'est celle sans laquelle le simulateur
     * d'ARC-17 ne peut pas generer un build par fonction.
     */
    public function testTheFourPatronTreesHaveTheirFork(): void
    {
        $catalog = $this->catalog();

        self::assertSame(['pyromancy', 'healer', 'soldier', 'archer', 'necromancer'], $catalog->forkedTrees());
        self::assertSame('La Braise', $catalog->labelOf('pyromancy', 'ember'));
        self::assertSame('Le Ressac', $catalog->labelOf('healer', 'undertow'));
        self::assertSame('Le Mur', $catalog->labelOf('soldier', 'wall'));
        self::assertSame('La Volée', $catalog->labelOf('archer', 'volley'));
        self::assertSame('Le Linceul', $catalog->labelOf('necromancer', 'shroud'));
    }

    /**
     * **Chaque branche ouvre son geste.**.
     *
     * C'est ce qui decide si la fourche est un choix ou une decoration :
     * mesure au § 9 bis, deux branches qui ne different que par leurs passifs
     * produisent **le meme combat, au tour pres** (11 contre 11). Avec un
     * accord par branche, elles se separent de deux tours et de deux facons de
     * jouer.
     */
    public function testEveryBranchOpensItsOwnGesture(): void
    {
        $catalog = $this->catalog();
        $accords = [];

        foreach ($catalog->forkedTrees() as $tree) {
            foreach (array_keys($catalog->branchesOf($tree)) as $branch) {
                $accord = $catalog->accordOf($tree, $branch);

                self::assertNotNull($accord, sprintf('%s/%s n\'ouvre aucun geste.', $tree, $branch));
                $accords[] = $accord;
            }
        }

        self::assertSame(
            \count($accords),
            \count(array_unique($accords)),
            'Deux branches partagent un geste : elles ne se separent alors que par leurs passifs.'
        );
    }

    /**
     * Deux branches, jamais trois — et jamais une.
     *
     * Meme regle que les metiers : le choix doit se raconter en une phrase.
     * Une seule branche ne serait pas une fourche ; trois n'en seraient plus
     * une non plus.
     */
    public function testAForkHasExactlyTwoBranches(): void
    {
        $catalog = $this->catalog();

        foreach ($catalog->forkedTrees() as $tree) {
            self::assertCount(CombatBranchCatalog::BRANCHES_PER_TREE, $catalog->branchesOf($tree), $tree);
        }
    }

    /**
     * Un eventail est refuse au chargement.
     */
    public function testThreeBranchesAreRefused(): void
    {
        $this->expectException(CombatBranchDefinitionException::class);

        $this->catalog()->normalize(['trees' => ['x' => ['label' => 'X', 'branches' => [
            'a' => ['label' => 'A', 'description' => 'a', 'accord' => 'A'],
            'b' => ['label' => 'B', 'description' => 'b', 'accord' => 'B'],
            'c' => ['label' => 'C', 'description' => 'c', 'accord' => 'C'],
        ]]]]);
    }

    /**
     * Une branche sans accord est refusee au chargement.
     *
     * Le defaut serait **invisible en donnee et fatal en jeu** : la fourche
     * existerait, et ne changerait rien.
     */
    public function testABranchWithoutAnAccordIsRefused(): void
    {
        $this->expectException(CombatBranchDefinitionException::class);

        $this->catalog()->normalize(['trees' => ['x' => ['label' => 'X', 'branches' => [
            'a' => ['label' => 'A', 'description' => 'a'],
            'b' => ['label' => 'B', 'description' => 'b', 'accord' => 'B'],
        ]]]]);
    }

    /**
     * L'arithmetique que la fourche rend possible, et qui la justifie.
     *
     * **C'est la raison d'etre du jalon, et elle est chiffrable.** Le gabarit
     * ecrit 18 nœuds ; sans fourche, les six nœuds du palier 3 se paient tous
     * et l'arbre coute **540** points. Avec la fourche, on en apprend trois, et
     * l'arbre retombe exactement sur les **390** que `SkillCostScale` fixe.
     *
     * C'est ce qui fait de ce jalon un **prerequis d'ARC-07** et non sa suite :
     * ecrire les arbres patrons au gabarit avant la fourche les ferait tous
     * echouer au seul invariant de cout qui existe.
     */
    public function testTheForkIsWhatMakesTheTemplateCostAddUp(): void
    {
        // Le gabarit du § 6.1, palier par palier : 2 accords d'entree gratuits,
        // 4 nœuds a 10, 4 a 25, **6** a 50 (deux branches de trois), 1 a 100.
        $written = 2 * 0 + 4 * 10 + 4 * 25 + 6 * 50 + 100;
        self::assertSame(540, $written, 'Le gabarit ecrit bien 540 points.');

        // Ce qu'un personnage paie : une seule branche, donc trois nœuds sur six.
        $learnable = 2 * 0 + 4 * 10 + 4 * 25 + 3 * 50 + 100;

        self::assertSame(
            SkillCostScale::COMPLETE_TREE,
            $learnable,
            'Sans la fourche, un arbre au gabarit ne tombe pas sur les 390 points du canon.'
        );
    }

    /**
     * Les vingt arbres sans fourche sont nommes, pas oublies.
     *
     * Cliquet : la liste peut retrecir, jamais grandir.
     */
    public function testTheTreesStillWaitingAreNamed(): void
    {
        self::assertCount(19, self::WAITING_ON_ARC_08);
        self::assertCount(
            24,
            array_merge(self::WAITING_ON_ARC_08, $this->catalog()->forkedTrees()),
            'La grille des 24 arbres de combat a change.'
        );
    }
}
