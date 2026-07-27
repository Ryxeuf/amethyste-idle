<?php

namespace App\Tests\Unit\GameEngine\Progression;

use App\Entity\App\Player;
use App\Entity\Game\Skill;
use App\GameEngine\Progression\ActionYieldResolver;
use PHPUnit\Framework\TestCase;

class ActionYieldResolverTest extends TestCase
{
    private ActionYieldResolver $resolver;

    protected function setUp(): void
    {
        $this->resolver = new ActionYieldResolver();
    }

    /**
     * @param array<string, mixed>|null $actions
     */
    private function skillWith(?array $actions): Skill
    {
        $skill = new Skill();
        $skill->setActions($actions);

        return $skill;
    }

    /**
     * @param list<Skill> $skills
     */
    private function playerWith(array $skills): Player
    {
        $player = new Player();
        foreach ($skills as $skill) {
            $player->addSkill($skill);
        }

        return $player;
    }

    public function testNoSkillMeansNoBonus(): void
    {
        $player = $this->playerWith([]);

        $this->assertSame(0, $this->resolver->getBonusPercent($player, ActionYieldResolver::CATEGORY_GATHER));
        $this->assertSame(4, $this->resolver->applyBonus($player, ActionYieldResolver::CATEGORY_GATHER, 4));
    }

    public function testBonusesFromSeveralSkillsAddUp(): void
    {
        $player = $this->playerWith([
            $this->skillWith(['yield' => ['gather_percent' => 10]]),
            $this->skillWith(['yield' => ['gather_percent' => 15]]),
        ]);

        $this->assertSame(25, $this->resolver->getBonusPercent($player, ActionYieldResolver::CATEGORY_GATHER));
    }

    public function testCategoriesAreIndependent(): void
    {
        $player = $this->playerWith([
            $this->skillWith(['yield' => ['gather_percent' => 20, 'chest_percent' => 5]]),
        ]);

        $this->assertSame(20, $this->resolver->getBonusPercent($player, ActionYieldResolver::CATEGORY_GATHER));
        $this->assertSame(5, $this->resolver->getBonusPercent($player, ActionYieldResolver::CATEGORY_CHEST));
    }

    /**
     * Les competences sans bloc `yield` sont l'immense majorite de l'arbre : elles
     * ne doivent ni contribuer ni faire echouer la lecture.
     */
    public function testSkillsWithoutYieldBlockAreIgnored(): void
    {
        $player = $this->playerWith([
            $this->skillWith(null),
            $this->skillWith(['materia' => ['unlock' => 'fire-ball']]),
            $this->skillWith(['yield' => ['gather_percent' => 10]]),
        ]);

        $this->assertSame(10, $this->resolver->getBonusPercent($player, ActionYieldResolver::CATEGORY_GATHER));
    }

    /**
     * Sans plafond, un arbre assez long rendrait le budget quotidien d'energie
     * sans effet : le joueur assidu retrouverait par le rendement le debit que le
     * plafond d'energie lui refuse.
     */
    public function testBonusIsCappedAtOneHundredPercent(): void
    {
        $player = $this->playerWith([
            $this->skillWith(['yield' => ['gather_percent' => 80]]),
            $this->skillWith(['yield' => ['gather_percent' => 80]]),
        ]);

        $this->assertSame(
            ActionYieldResolver::MAX_BONUS_PERCENT,
            $this->resolver->getBonusPercent($player, ActionYieldResolver::CATEGORY_GATHER),
        );
        $this->assertSame(20, $this->resolver->applyBonus($player, ActionYieldResolver::CATEGORY_GATHER, 10));
    }

    /**
     * Une valeur negative signale une donnee fautive : on l'ignore plutot que de
     * retirer du rendement au joueur.
     */
    public function testNegativeDeclarationsAreIgnored(): void
    {
        $player = $this->playerWith([
            $this->skillWith(['yield' => ['gather_percent' => -30]]),
            $this->skillWith(['yield' => ['gather_percent' => 10]]),
        ]);

        $this->assertSame(10, $this->resolver->getBonusPercent($player, ActionYieldResolver::CATEGORY_GATHER));
    }

    /**
     * Arrondi au plus proche : avec un arrondi a l'inferieur, un bonus de 10 %
     * sur un rendement de 1 a 2 unites ne se serait jamais vu et le joueur aurait
     * paye des points de talent pour rien.
     */
    public function testBonusRoundsToNearest(): void
    {
        $player = $this->playerWith([$this->skillWith(['yield' => ['gather_percent' => 25]])]);

        // 2 * 1.25 = 2.5 -> 3
        $this->assertSame(3, $this->resolver->applyBonus($player, ActionYieldResolver::CATEGORY_GATHER, 2));
        // 1 * 1.25 = 1.25 -> 1
        $this->assertSame(1, $this->resolver->applyBonus($player, ActionYieldResolver::CATEGORY_GATHER, 1));
    }

    /**
     * Les arbres de recolte et d'artisanat declarent leurs actions sous forme de
     * liste de descripteurs. N'accepter que la forme map aurait rendu le bonus
     * indeclarable precisement la ou il a un sens.
     */
    public function testListShapedActionsAreRead(): void
    {
        $player = $this->playerWith([
            $this->skillWith([
                ['action' => 'harvest', 'spots' => ['spot-trout-xs']],
                ['action' => 'yield', 'category' => 'gather_percent', 'percent' => 12],
            ]),
        ]);

        $this->assertSame(12, $this->resolver->getBonusPercent($player, ActionYieldResolver::CATEGORY_GATHER));
        $this->assertSame(0, $this->resolver->getBonusPercent($player, ActionYieldResolver::CATEGORY_CHEST));
    }

    public function testBothShapesAddUpAcrossSkills(): void
    {
        $player = $this->playerWith([
            $this->skillWith(['yield' => ['gather_percent' => 10]]),
            $this->skillWith([['action' => 'yield', 'category' => 'gather_percent', 'percent' => 5]]),
        ]);

        $this->assertSame(15, $this->resolver->getBonusPercent($player, ActionYieldResolver::CATEGORY_GATHER));
    }

    /**
     * Une competence de recolte peut porter plusieurs categories : on additionne
     * au lieu de s'arreter au premier descripteur.
     */
    public function testSeveralDescriptorsOnTheSameSkillAddUp(): void
    {
        $player = $this->playerWith([
            $this->skillWith([
                ['action' => 'yield', 'category' => 'gather_percent', 'percent' => 4],
                ['action' => 'yield', 'category' => 'gather_percent', 'percent' => 6],
            ]),
        ]);

        $this->assertSame(10, $this->resolver->getBonusPercent($player, ActionYieldResolver::CATEGORY_GATHER));
    }

    public function testNonPositiveAmountsPassThrough(): void
    {
        $player = $this->playerWith([$this->skillWith(['yield' => ['chest_percent' => 50]])]);

        $this->assertSame(0, $this->resolver->applyBonus($player, ActionYieldResolver::CATEGORY_CHEST, 0));
        $this->assertSame(-5, $this->resolver->applyBonus($player, ActionYieldResolver::CATEGORY_CHEST, -5));
    }
}
