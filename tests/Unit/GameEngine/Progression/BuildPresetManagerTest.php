<?php

namespace App\Tests\Unit\GameEngine\Progression;

use App\Entity\App\BuildPreset;
use App\Entity\App\Fight;
use App\Entity\App\Player;
use App\Entity\Game\Skill;
use App\GameEngine\Progression\BuildPresetManager;
use App\GameEngine\Progression\SkillAcquiring;
use App\GameEngine\Progression\SkillRespecManager;
use App\Helper\PlayerSkillHelper;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class BuildPresetManagerTest extends TestCase
{
    private EntityManagerInterface&MockObject $entityManager;
    private SkillRespecManager&MockObject $respecManager;
    private SkillAcquiring&MockObject $skillAcquiring;
    private PlayerSkillHelper&MockObject $skillHelper;
    private BuildPresetManager $manager;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->respecManager = $this->createMock(SkillRespecManager::class);
        $this->skillAcquiring = $this->createMock(SkillAcquiring::class);
        $this->skillHelper = $this->createMock(PlayerSkillHelper::class);

        $this->manager = new BuildPresetManager(
            $this->entityManager,
            $this->respecManager,
            $this->skillAcquiring,
            $this->skillHelper,
        );
    }

    public function testCanSaveReturnsFalseWithNoSkills(): void
    {
        $player = $this->createPlayer(0);

        $presetRepo = $this->createMock(EntityRepository::class);
        $this->entityManager->method('getRepository')->willReturn($presetRepo);
        $presetRepo->method('count')->willReturn(0);

        $this->assertFalse($this->manager->canSave($player));
    }

    public function testCanSaveReturnsFalseWhenLimitReached(): void
    {
        $player = $this->createPlayer(3);

        $presetRepo = $this->createMock(EntityRepository::class);
        $this->entityManager->method('getRepository')->willReturn($presetRepo);
        $presetRepo->method('count')->willReturn(BuildPresetManager::MAX_PRESETS_PER_PLAYER);

        $this->assertFalse($this->manager->canSave($player));
    }

    public function testCanSaveReturnsTrueWhenHasSkillsAndRoom(): void
    {
        $player = $this->createPlayer(5);

        $presetRepo = $this->createMock(EntityRepository::class);
        $this->entityManager->method('getRepository')->willReturn($presetRepo);
        $presetRepo->method('count')->willReturn(1);

        $this->assertTrue($this->manager->canSave($player));
    }

    public function testSaveCreatesPreset(): void
    {
        $skill = $this->createSkill('fireball', 10);
        $player = $this->createPlayer(0);
        $player->getSkills()->add($skill);

        $presetRepo = $this->createMock(EntityRepository::class);
        $this->entityManager->method('getRepository')->willReturn($presetRepo);
        $presetRepo->method('count')->willReturn(0);

        $this->entityManager->expects($this->once())->method('persist')
            ->with($this->isInstanceOf(BuildPreset::class));
        $this->entityManager->expects($this->once())->method('flush');

        $preset = $this->manager->save($player, 'Mon build');

        $this->assertNotNull($preset);
        $this->assertSame('Mon build', $preset->getName());
        $this->assertSame(['fireball'], $preset->getSkillSlugs());
    }

    public function testSaveReturnsNullForEmptyName(): void
    {
        $player = $this->createPlayer(1);

        $presetRepo = $this->createMock(EntityRepository::class);
        $this->entityManager->method('getRepository')->willReturn($presetRepo);
        $presetRepo->method('count')->willReturn(0);

        $this->assertNull($this->manager->save($player, ''));
        $this->assertNull($this->manager->save($player, '   '));
    }

    public function testLoadFailsForWrongPlayer(): void
    {
        $player1 = $this->createPlayerWithId(1);
        $player2 = $this->createPlayerWithId(2);

        $preset = new BuildPreset();
        $preset->setPlayer($player2);
        $preset->setName('Test');
        $preset->setSkillSlugs(['fireball']);

        $result = $this->manager->load($player1, $preset);

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('ne vous appartient pas', $result['message']);
    }

    public function testLoadFailsInFight(): void
    {
        $fight = $this->createMock(Fight::class);

        $player = $this->createMock(Player::class);
        $player->method('getId')->willReturn(1);
        $player->method('getSkills')->willReturn(new ArrayCollection());
        $player->method('getDomainExperiences')->willReturn(new ArrayCollection());
        $player->method('getFight')->willReturn($fight);

        $preset = new BuildPreset();
        $preset->setPlayer($player);
        $preset->setName('Test');
        $preset->setSkillSlugs(['fireball']);

        $result = $this->manager->load($player, $preset);

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('combat', $result['message']);
    }

    public function testDeleteSucceedsForOwner(): void
    {
        $player = $this->createPlayerWithId(1);

        $preset = new BuildPreset();
        $preset->setPlayer($player);
        $preset->setName('Test');
        $preset->setSkillSlugs([]);

        $this->entityManager->expects($this->once())->method('remove')->with($preset);
        $this->entityManager->expects($this->once())->method('flush');

        $this->assertTrue($this->manager->delete($player, $preset));
    }

    public function testDeleteFailsForNonOwner(): void
    {
        $player1 = $this->createPlayerWithId(1);
        $player2 = $this->createPlayerWithId(2);

        $preset = new BuildPreset();
        $preset->setPlayer($player2);
        $preset->setName('Test');
        $preset->setSkillSlugs([]);

        $this->assertFalse($this->manager->delete($player1, $preset));
    }

    private function createPlayer(int $skillCount): Player
    {
        $player = new Player();
        $player->setName('TestPlayer');
        $player->setMaxLife(100);
        $player->setLife(100);
        $player->setEnergy(100);
        $player->setMaxEnergy(100);
        $player->setClassType('warrior');
        $player->setCoordinates('5.5');
        $player->setLastCoordinates('5.5');
        $player->setGils(1000);

        for ($i = 0; $i < $skillCount; ++$i) {
            $player->addSkill($this->createSkill("skill-$i", 10));
        }

        return $player;
    }

    private function createPlayerWithId(int $id): Player&MockObject
    {
        $player = $this->createMock(Player::class);
        $player->method('getId')->willReturn($id);
        $player->method('getSkills')->willReturn(new ArrayCollection());
        $player->method('getDomainExperiences')->willReturn(new ArrayCollection());
        $player->method('getFight')->willReturn(null);

        return $player;
    }

    private function createSkill(string $slug, int $requiredPoints): Skill
    {
        $skill = new Skill();
        $skill->setSlug($slug);
        $skill->setTitle("Skill $slug");
        $skill->setDescription('Description');
        $skill->setRequiredPoints($requiredPoints);

        return $skill;
    }
}
