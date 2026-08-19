<?php

namespace App\Tests\Unit\GameEngine\Fight;

use App\Entity\App\Player;
use App\Entity\Game\Skill;
use App\GameEngine\Fight\BuildDomainResolver;
use App\GameEngine\Fight\CombatSkillResolver;
use App\GameEngine\Fight\EquipmentSetResolver;
use App\GameEngine\Fight\StanceLeverReader;
use App\GameEngine\Progression\BuildConditionEvaluator;
use App\GameEngine\Progression\CombatLeverDefinitionLoader;
use App\GameEngine\Progression\CombatLeverScale;
use App\GameEngine\Progression\EquipmentPortCatalog;
use App\GameEngine\Progression\SkillLeverReader;
use App\GameEngine\Reputation\PatronageBonusResolver;
use App\GameEngine\Zone\LifeRegenManager;
use App\GameEngine\Zone\ManaRegenManager;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class CombatSkillResolverMateriaTest extends TestCase
{
    private CombatSkillResolver $resolver;

    protected function setUp(): void
    {
        $equipmentSetResolver = $this->createMock(EquipmentSetResolver::class);
        $equipmentSetResolver->method('getSetBonuses')->willReturn([
            'damage' => 0, 'heal' => 0, 'hit' => 0, 'critical' => 0, 'life' => 0, 'protection' => 0,
        ]);
        $buildDomainResolver = $this->createMock(BuildDomainResolver::class);
        $buildDomainResolver->method('isActive')->willReturn(true);

        $this->resolver = new CombatSkillResolver($buildDomainResolver, $equipmentSetResolver, $this->neutralPatronage(), $this->leverReader(), $this->leverScale(), $this->stanceReader(), $this->regen(LifeRegenManager::class), $this->regen(ManaRegenManager::class), $this->alwaysSatisfiedConditions());
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

    /**
     * Un patronage neutre : aucune couleur portee, rien d'amplifie.
     *
     * FAC-01 a ajoute une derniere etape a l'agregation des bonus. Ces tests
     * portent sur les bornes de domaine, pas sur les factions : un patronage
     * qui rendrait les bonus tels quels est la seule facon de les garder
     * lisibles — sans quoi chaque attendu chiffre porterait un pourcentage sans
     * rapport avec ce qu'il verifie.
     */
    private function neutralPatronage(): PatronageBonusResolver
    {
        $patronage = $this->createMock(PatronageBonusResolver::class);
        $patronage->method('amplify')->willReturnCallback(
            static fn (Player $player, array $bonuses): array => $bonuses,
        );

        return $patronage;
    }

    /**
     * Le lecteur de leviers reel, sur la configuration reelle (ARC-03b).
     *
     * Un double rendrait le test aveugle a la seule chose qui compte ici : les
     * leviers suivent la meme borne que les statistiques plates.
     */
    /**
     * Le lecteur de postures, sans combat a lire (ARC-18b).
     *
     * Une posture ne survit pas a la rencontre : sans combat, `heldBy()` rend
     * `[]` et rien de ce fichier ne change.
     */
    /**
     * Un gestionnaire de regeneration muet (ARC-18c).
     *
     * Il ne sert qu'a la conversion, et aucun geste de ce fichier n'en est une.
     * Le doubler evite d'aller chercher un parametre en base pour une question
     * qu'on ne pose pas.
     *
     * @template T of LifeRegenManager|ManaRegenManager
     *
     * @param class-string<T> $class
     *
     * @return T
     */
    private function regen(string $class): LifeRegenManager|ManaRegenManager
    {
        return $this->createMock($class);
    }

    private function stanceReader(): StanceLeverReader
    {
        return new StanceLeverReader($this->leverScale(), $this->createMock(EntityManagerInterface::class));
    }

    private function leverScale(): CombatLeverScale
    {
        return new CombatLeverScale(new CombatLeverDefinitionLoader(\dirname(__DIR__, 4)));
    }

    private function leverReader(): SkillLeverReader
    {
        return new SkillLeverReader($this->leverScale(), new EquipmentPortCatalog(\dirname(__DIR__, 4)));
    }

    /**
     * ARC-16b : les tests de bornage ne parlent pas d'equipement — toute
     * condition de build y est reputee portee.
     */
    private function alwaysSatisfiedConditions(): BuildConditionEvaluator
    {
        $evaluator = $this->createMock(BuildConditionEvaluator::class);
        $evaluator->method('isSatisfied')->willReturn(true);

        return $evaluator;
    }
}
