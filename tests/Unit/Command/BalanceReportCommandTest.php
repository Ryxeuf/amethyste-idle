<?php

namespace App\Tests\Unit\Command;

use App\Command\BalanceReportCommand;
use App\Entity\Game\Domain;
use App\Entity\Game\Item;
use App\Entity\Game\Monster;
use App\Entity\Game\MonsterItem;
use App\Entity\Game\Skill;
use App\Entity\Game\Spell;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

class BalanceReportCommandTest extends TestCase
{
    private EntityManagerInterface&MockObject $entityManager;
    private CommandTester $tester;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);

        $command = new BalanceReportCommand($this->entityManager);
        $app = new Application();
        $app->add($command);
        $this->tester = new CommandTester($app->find('app:balance:report'));
    }

    public function testCommandSucceedsWithNoAlerts(): void
    {
        $this->stubRepositories(
            monsters: [$this->createMonster('Slime', 5, 50, 10)],
            items: [],
            monsterItems: [],
            domains: [],
            spells: [],
        );

        $this->tester->execute([]);

        $this->assertSame(0, $this->tester->getStatusCode());
        $this->assertStringContainsString('Slime', $this->tester->getDisplay());
    }

    public function testStrictModeReturnsFailureOnAlerts(): void
    {
        // Monster with 0 HP triggers an alert
        $this->stubRepositories(
            monsters: [$this->createMonster('BrokenMob', 5, 0, 10)],
            items: [],
            monsterItems: [],
            domains: [],
            spells: [],
        );

        $this->tester->execute(['--strict' => true]);

        $this->assertSame(1, $this->tester->getStatusCode());
        $this->assertStringContainsString('BrokenMob', $this->tester->getDisplay());
        $this->assertStringContainsString('0 HP', $this->tester->getDisplay());
    }

    public function testStrictModeReturnsSuccessWhenNoAlerts(): void
    {
        $this->stubRepositories(
            monsters: [$this->createMonster('Slime', 5, 50, 10)],
            items: [],
            monsterItems: [],
            domains: [],
            spells: [],
        );

        $this->tester->execute(['--strict' => true]);

        $this->assertSame(0, $this->tester->getStatusCode());
    }

    public function testWithoutStrictModeSucceedsEvenWithAlerts(): void
    {
        // Monster with 0 HP triggers an alert, but without --strict it succeeds
        $this->stubRepositories(
            monsters: [$this->createMonster('BrokenMob', 5, 0, 10)],
            items: [],
            monsterItems: [],
            domains: [],
            spells: [],
        );

        $this->tester->execute([]);

        $this->assertSame(0, $this->tester->getStatusCode());
        $this->assertStringContainsString('0 HP', $this->tester->getDisplay());
    }

    public function testSectionFilterOnlyShowsAlerts(): void
    {
        $this->stubRepositories(
            monsters: [$this->createMonster('BrokenMob', 5, 0, 10)],
            items: [],
            monsterItems: [],
            domains: [],
            spells: [],
        );

        $this->tester->execute(['--section' => 'alerts', '--strict' => true]);

        $this->assertSame(1, $this->tester->getStatusCode());
        $display = $this->tester->getDisplay();
        $this->assertStringContainsString('Alertes', $display);
        $this->assertStringContainsString('BrokenMob', $display);
    }

    private function createMonster(string $name, int $level, int $life, int $damage): Monster
    {
        $attack = new Spell();
        $attack->setName($name . ' Attack');
        $attack->setDamage($damage);

        $monster = new Monster();
        $monster->setName($name);
        $monster->setLevel($level);
        $monster->setLife($life);
        $monster->setAttack($attack);

        return $monster;
    }

    /**
     * @param Monster[]     $monsters
     * @param Item[]        $items
     * @param MonsterItem[] $monsterItems
     * @param Domain[]      $domains
     * @param Spell[]       $spells
     */
    private function stubRepositories(
        array $monsters,
        array $items,
        array $monsterItems,
        array $domains,
        array $spells,
    ): void {
        $repoMap = [
            Monster::class => $monsters,
            Item::class => $items,
            MonsterItem::class => $monsterItems,
            Domain::class => $domains,
            Spell::class => $spells,
        ];

        $this->entityManager->method('getRepository')
            ->willReturnCallback(function (string $class) use ($repoMap) {
                $repo = $this->createMock(EntityRepository::class);
                $repo->method('findAll')->willReturn($repoMap[$class] ?? []);

                return $repo;
            });
    }
}
