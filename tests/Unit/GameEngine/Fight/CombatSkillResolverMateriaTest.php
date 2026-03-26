<?php

namespace App\Tests\Unit\GameEngine\Fight;

use App\Entity\App\Player;
use App\Entity\Game\Skill;
use App\GameEngine\Fight\CombatSkillResolver;
use App\GameEngine\Fight\EquipmentSetResolver;
use App\GameEngine\Item\EnchantmentManager;
use App\GameEngine\Progression\SynergyCalculator;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class CombatSkillResolverMateriaTest extends TestCase
{
    private CombatSkillResolver $resolver;
    private EntityManagerInterface&MockObject $entityManager;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $synergyCalculator = $this->createMock(SynergyCalculator::class);
        $equipmentSetResolver = $this->createMock(EquipmentSetResolver::class);
        $equipmentSetResolver->method('getSetBonuses')->willReturn([
            'damage' => 0, 'heal' => 0, 'hit' => 0, 'critical' => 0, 'life' => 0, 'protection' => 0,
        ]);
        $enchantmentManager = $this->createMock(EnchantmentManager::class);
        $enchantmentManager->method('getEquippedEnchantmentBonuses')->willReturn([
            'damage' => 0, 'heal' => 0, 'hit' => 0, 'critical' => 0, 'life' => 0, 'protection' => 0,
        ]);
        $this->resolver = new CombatSkillResolver($this->entityManager, $synergyCalculator, $equipmentSetResolver, $enchantmentManager);
    }

    private function createSkillWithMateriaUnlock(string $spellSlug): Skill&MockObject
    {
        $skill = $this->createMock(Skill::class);
        $skill->method('getActions')->willReturn(['materia' => ['unlock' => $spellSlug]]);

        return $skill;
    }

    private function createSkillWithoutActions(): Skill&MockObject
    {
        $skill = $this->createMock(Skill::class);
        $skill->method('getActions')->willReturn(null);

        return $skill;
    }

    private function createSkillWithCombatAction(): Skill&MockObject
    {
        $skill = $this->createMock(Skill::class);
        $skill->method('getActions')->willReturn(['combat' => ['spell_slug' => 'some-spell']]);

        return $skill;
    }

    private function createPlayer(array $skills): Player&MockObject
    {
        $player = $this->createMock(Player::class);
        $player->method('getSkills')->willReturn(new ArrayCollection($skills));

        return $player;
    }

    public function testGetUnlockedMateriaSpellSlugsReturnsEmptyWhenNoSkills(): void
    {
        $player = $this->createPlayer([]);

        $result = $this->resolver->getUnlockedMateriaSpellSlugs($player);

        $this->assertEmpty($result);
    }

    public function testGetUnlockedMateriaSpellSlugsReturnsSlugs(): void
    {
        $skill1 = $this->createSkillWithMateriaUnlock('fire-ball');
        $skill2 = $this->createSkillWithMateriaUnlock('ice-bolt');
        $player = $this->createPlayer([$skill1, $skill2]);

        $result = $this->resolver->getUnlockedMateriaSpellSlugs($player);

        $this->assertCount(2, $result);
        $this->assertContains('fire-ball', $result);
        $this->assertContains('ice-bolt', $result);
    }

    public function testGetUnlockedMateriaSpellSlugsIgnoresNonMateriaSkills(): void
    {
        $materiaSkill = $this->createSkillWithMateriaUnlock('fire-ball');
        $combatSkill = $this->createSkillWithCombatAction();
        $nullSkill = $this->createSkillWithoutActions();
        $player = $this->createPlayer([$materiaSkill, $combatSkill, $nullSkill]);

        $result = $this->resolver->getUnlockedMateriaSpellSlugs($player);

        $this->assertCount(1, $result);
        $this->assertContains('fire-ball', $result);
    }

    public function testGetUnlockedMateriaSpellSlugsDeduplicates(): void
    {
        $skill1 = $this->createSkillWithMateriaUnlock('fire-ball');
        $skill2 = $this->createSkillWithMateriaUnlock('fire-ball');
        $player = $this->createPlayer([$skill1, $skill2]);

        $result = $this->resolver->getUnlockedMateriaSpellSlugs($player);

        $this->assertCount(1, $result);
    }

    public function testHasUnlockedMateriaSpellReturnsTrueWhenUnlocked(): void
    {
        $skill = $this->createSkillWithMateriaUnlock('fire-ball');
        $player = $this->createPlayer([$skill]);

        $this->assertTrue($this->resolver->hasUnlockedMateriaSpell($player, 'fire-ball'));
    }

    public function testHasUnlockedMateriaSpellReturnsFalseWhenNotUnlocked(): void
    {
        $skill = $this->createSkillWithMateriaUnlock('fire-ball');
        $player = $this->createPlayer([$skill]);

        $this->assertFalse($this->resolver->hasUnlockedMateriaSpell($player, 'ice-bolt'));
    }

    public function testHasUnlockedMateriaSpellReturnsFalseWhenNoSkills(): void
    {
        $player = $this->createPlayer([]);

        $this->assertFalse($this->resolver->hasUnlockedMateriaSpell($player, 'fire-ball'));
    }
}
