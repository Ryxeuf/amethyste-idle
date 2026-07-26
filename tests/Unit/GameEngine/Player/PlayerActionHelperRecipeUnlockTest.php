<?php

namespace App\Tests\Unit\GameEngine\Player;

use App\Entity\App\Player;
use App\Entity\Game\Skill;
use App\GameEngine\Player\PlayerActionHelper;
use App\Helper\PlayerHelper;
use Doctrine\Common\Collections\ArrayCollection;
use PHPUnit\Framework\TestCase;

/**
 * ECO-20 — les actions `craft` des arbres de talent sont enfin lues.
 *
 * `getActions()` ne traitait specifiquement que `tool_slot.unlock` (champ
 * `slot`) et `equip.tool` (champ `slugs`) ; toute autre cle lisait `spots`. Une
 * action `craft` porte ses donnees dans `recipes` : elle contribuait donc un
 * tableau **vide**, et les 51 nœuds d'arbre qui debloquent des recettes ne
 * debloquaient rien.
 */
final class PlayerActionHelperRecipeUnlockTest extends TestCase
{
    public function testCraftActionsContributeTheirRecipes(): void
    {
        $helper = $this->helper();
        $player = $this->playerWithSkills([
            [['action' => 'craft', 'recipes' => ['recipe-iron-dagger', 'recipe-short-sword']]],
            [['action' => 'craft', 'recipes' => ['recipe-iron-sword']]],
        ]);

        $slugs = $helper->getUnlockedRecipeSlugs($player);

        sort($slugs);
        self::assertSame(['recipe-iron-dagger', 'recipe-iron-sword', 'recipe-short-sword'], $slugs);
        self::assertTrue($helper->hasUnlockedRecipe('recipe-iron-sword', $player));
        self::assertFalse($helper->hasUnlockedRecipe('recipe-mithril-helm', $player));
    }

    /**
     * Le champ lu depend de la cle : lire `spots` pour une action `craft` etait
     * exactement le defaut corrige.
     */
    public function testACraftActionCarryingSpotsContributesNothing(): void
    {
        $helper = $this->helper();
        $player = $this->playerWithSkills([
            [['action' => 'craft', 'spots' => ['recipe-iron-dagger']]],
        ]);

        self::assertSame([], $helper->getUnlockedRecipeSlugs($player));
    }

    /**
     * Les autres cles d'action ne doivent pas avoir bouge : le meme skill peut
     * ouvrir un emplacement d'outil, autoriser un outil et enseigner un plan.
     */
    public function testOtherActionKeysStillReadTheirOwnField(): void
    {
        $helper = $this->helper();
        $player = $this->playerWithSkills([
            [
                ['action' => 'craft', 'recipes' => ['recipe-iron-dagger']],
                ['action' => 'tool_slot.unlock', 'slot' => 'hammer'],
                ['action' => 'equip.tool', 'slugs' => ['hammer-bronze']],
                ['action' => 'harvest', 'spots' => ['ore-tin']],
            ],
        ]);

        self::assertSame(['recipe-iron-dagger'], $helper->getUnlockedRecipeSlugs($player));
        self::assertSame(['hammer'], $helper->getUnlockedToolSlots($player));
        self::assertSame(['hammer-bronze'], $helper->getEquippableToolSlugs($player));
        self::assertSame(['ore-tin'], $helper->getHarvestSpots($player));
    }

    public function testDuplicateRecipesAcrossSkillsAreCountedOnce(): void
    {
        $helper = $this->helper();
        $player = $this->playerWithSkills([
            [['action' => 'craft', 'recipes' => ['recipe-leather-boots']]],
            [['action' => 'craft', 'recipes' => ['recipe-leather-boots']]],
        ]);

        self::assertSame(['recipe-leather-boots'], $helper->getUnlockedRecipeSlugs($player));
    }

    public function testAPlayerWithoutSkillsUnlocksNothing(): void
    {
        self::assertSame([], $this->helper()->getUnlockedRecipeSlugs($this->playerWithSkills([])));
    }

    private function helper(): PlayerActionHelper
    {
        return new PlayerActionHelper($this->createMock(PlayerHelper::class));
    }

    /**
     * @param list<list<array<string, mixed>>> $actionSets
     */
    private function playerWithSkills(array $actionSets): Player
    {
        $skills = [];
        foreach ($actionSets as $index => $actions) {
            $skill = new Skill();
            $skill->setTitle('Skill ' . $index);
            $skill->setSlug('skill-' . $index);
            $skill->setActions($actions);
            $skills[] = $skill;
        }

        $player = new Player();
        (new \ReflectionProperty(Player::class, 'skills'))->setValue($player, new ArrayCollection($skills));

        return $player;
    }
}
