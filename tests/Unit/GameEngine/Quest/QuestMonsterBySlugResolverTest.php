<?php

declare(strict_types=1);

namespace App\Tests\Unit\GameEngine\Quest;

use App\Entity\App\PlayerDailyQuest;
use App\Entity\App\PlayerQuest;
use App\Entity\Game\Monster;
use App\Entity\Game\Quest;
use App\GameEngine\Quest\QuestMonsterBySlugResolver;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use PHPUnit\Framework\TestCase;

final class QuestMonsterBySlugResolverTest extends TestCase
{
    public function testResolveCollectsSlugsFromAllSourcesAndBuildsMap(): void
    {
        $activeQuest = $this->createMock(PlayerQuest::class);
        $activeQuest->method('getTracking')->willReturn([
            'monsters' => [
                ['slug' => 'zombie', 'name' => 'Zombie', 'count' => 0, 'necessary' => 2],
            ],
        ]);

        $dailyQuest = $this->createMock(PlayerDailyQuest::class);
        $dailyQuest->method('getTracking')->willReturn([
            'monsters' => [
                ['slug' => 'skeleton', 'name' => 'Squelette', 'count' => 0, 'necessary' => 3],
            ],
        ]);

        $availableQuest = $this->createMock(Quest::class);
        $availableQuest->method('getRequirements')->willReturn([
            'monsters' => [
                ['slug' => 'goblin', 'name' => 'Gobelin', 'count' => 5],
            ],
        ]);

        $availableDailyQuest = $this->createMock(Quest::class);
        $availableDailyQuest->method('getRequirements')->willReturn([
            'monsters' => [
                ['slug' => 'zombie', 'name' => 'Zombie', 'count' => 1],
            ],
        ]);

        $zombie = $this->monster('zombie');
        $skeleton = $this->monster('skeleton');
        $goblin = $this->monster('goblin');

        $repo = $this->createMock(EntityRepository::class);
        $repo->expects($this->once())
            ->method('findBy')
            ->with($this->callback(function (array $criteria): bool {
                $this->assertArrayHasKey('slug', $criteria);
                $slugs = $criteria['slug'];
                sort($slugs);
                $this->assertSame(['goblin', 'skeleton', 'zombie'], $slugs);

                return true;
            }))
            ->willReturn([$zombie, $skeleton, $goblin]);

        $resolver = new QuestMonsterBySlugResolver($this->entityManagerReturning($repo));

        $map = $resolver->resolve([$activeQuest], [$dailyQuest], [$availableQuest], [$availableDailyQuest]);

        $this->assertSame($zombie, $map['zombie']);
        $this->assertSame($skeleton, $map['skeleton']);
        $this->assertSame($goblin, $map['goblin']);
    }

    public function testResolveReturnsEmptyWhenNoMonstersReferenced(): void
    {
        $repo = $this->createMock(EntityRepository::class);
        $repo->expects($this->never())->method('findBy');

        $resolver = new QuestMonsterBySlugResolver($this->entityManagerReturning($repo));

        $this->assertSame([], $resolver->resolve([], [], [], []));
    }

    public function testResolveIgnoresInvalidOrMissingSlugs(): void
    {
        $activeQuest = $this->createMock(PlayerQuest::class);
        $activeQuest->method('getTracking')->willReturn([
            'monsters' => [
                ['name' => 'Sans slug', 'count' => 0, 'necessary' => 1],
                ['slug' => '', 'name' => 'Empty', 'count' => 0, 'necessary' => 1],
                ['slug' => 42, 'name' => 'Not a string', 'count' => 0, 'necessary' => 1],
                ['slug' => 'valid', 'name' => 'Valid', 'count' => 0, 'necessary' => 1],
            ],
        ]);

        $availableQuest = $this->createMock(Quest::class);
        $availableQuest->method('getRequirements')->willReturn([
            'monsters' => 'not an array',
        ]);

        $valid = $this->monster('valid');
        $repo = $this->createMock(EntityRepository::class);
        $repo->expects($this->once())
            ->method('findBy')
            ->with(['slug' => ['valid']])
            ->willReturn([$valid]);

        $resolver = new QuestMonsterBySlugResolver($this->entityManagerReturning($repo));

        $map = $resolver->resolve([$activeQuest], [], [$availableQuest], []);

        $this->assertSame(['valid' => $valid], $map);
    }

    public function testResolveDeduplicatesSlugsAcrossSources(): void
    {
        $activeQuest = $this->createMock(PlayerQuest::class);
        $activeQuest->method('getTracking')->willReturn([
            'monsters' => [
                ['slug' => 'zombie', 'name' => 'Zombie', 'count' => 0, 'necessary' => 1],
                ['slug' => 'zombie', 'name' => 'Zombie', 'count' => 0, 'necessary' => 1],
            ],
        ]);
        $availableQuest = $this->createMock(Quest::class);
        $availableQuest->method('getRequirements')->willReturn([
            'monsters' => [
                ['slug' => 'zombie', 'name' => 'Zombie', 'count' => 2],
            ],
        ]);

        $zombie = $this->monster('zombie');
        $repo = $this->createMock(EntityRepository::class);
        $repo->expects($this->once())
            ->method('findBy')
            ->with(['slug' => ['zombie']])
            ->willReturn([$zombie]);

        $resolver = new QuestMonsterBySlugResolver($this->entityManagerReturning($repo));

        $map = $resolver->resolve([$activeQuest], [], [$availableQuest], []);

        $this->assertSame(['zombie' => $zombie], $map);
    }

    public function testResolveHandlesMissingMonstersKey(): void
    {
        $activeQuest = $this->createMock(PlayerQuest::class);
        $activeQuest->method('getTracking')->willReturn([
            'collect' => [['slug' => 'mushroom', 'count' => 0, 'necessary' => 3]],
        ]);

        $repo = $this->createMock(EntityRepository::class);
        $repo->expects($this->never())->method('findBy');

        $resolver = new QuestMonsterBySlugResolver($this->entityManagerReturning($repo));

        $this->assertSame([], $resolver->resolve([$activeQuest], [], [], []));
    }

    private function monster(string $slug): Monster
    {
        $monster = $this->createMock(Monster::class);
        $monster->method('getSlug')->willReturn($slug);

        return $monster;
    }

    private function entityManagerReturning(EntityRepository $repo): EntityManagerInterface
    {
        $em = $this->createMock(EntityManagerInterface::class);
        $em->method('getRepository')->with(Monster::class)->willReturn($repo);

        return $em;
    }
}
