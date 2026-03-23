<?php

namespace App\Tests\Functional\Controller\Game;

use App\Controller\Game\AchievementController;
use App\Entity\App\Player;
use App\Entity\App\PlayerAchievement;
use App\Entity\Game\Achievement;
use App\Helper\PlayerHelper;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Twig\Environment as TwigEnvironment;

class AchievementControllerTest extends TestCase
{
    private PlayerHelper&MockObject $playerHelper;
    private EntityManagerInterface&MockObject $entityManager;
    private AchievementController $controller;

    /** @var array<string, mixed>|null */
    private ?array $capturedTemplateParams = null;

    protected function setUp(): void
    {
        $this->playerHelper = $this->createMock(PlayerHelper::class);
        $this->entityManager = $this->createMock(EntityManagerInterface::class);

        $this->controller = new AchievementController(
            $this->playerHelper,
            $this->entityManager,
        );

        $this->controller->setContainer($this->createContainer());
    }

    public function testIndexRendersWithCategories(): void
    {
        $player = $this->createMock(Player::class);
        $this->playerHelper->method('getPlayer')->willReturn($player);

        $achievement1 = $this->createAchievementMock(1, 'combat');
        $achievement2 = $this->createAchievementMock(2, 'combat');
        $achievement3 = $this->createAchievementMock(3, 'exploration');

        $pa1 = $this->createPlayerAchievementMock($achievement1, completed: true);
        $pa2 = $this->createPlayerAchievementMock($achievement2, completed: false);

        $achievementRepo = $this->createMock(EntityRepository::class);
        $achievementRepo->method('findAll')->willReturn([$achievement1, $achievement2, $achievement3]);

        $playerAchievementRepo = $this->createMock(EntityRepository::class);
        $playerAchievementRepo->method('findBy')->willReturn([$pa1, $pa2]);

        $this->entityManager->method('getRepository')->willReturnCallback(
            fn (string $class) => match ($class) {
                Achievement::class => $achievementRepo,
                PlayerAchievement::class => $playerAchievementRepo,
                default => $this->createMock(EntityRepository::class),
            },
        );

        $response = $this->controller->index();

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertNotNull($this->capturedTemplateParams);
        $this->assertEquals(3, $this->capturedTemplateParams['totalAchievements']);
        $this->assertEquals(1, $this->capturedTemplateParams['completedCount']);
        $this->assertArrayHasKey('combat', $this->capturedTemplateParams['categories']);
        $this->assertArrayHasKey('exploration', $this->capturedTemplateParams['categories']);
        $this->assertCount(2, $this->capturedTemplateParams['categories']['combat']);
        $this->assertCount(1, $this->capturedTemplateParams['categories']['exploration']);
    }

    public function testIndexCountsCompletedAchievements(): void
    {
        $player = $this->createMock(Player::class);
        $this->playerHelper->method('getPlayer')->willReturn($player);

        $a1 = $this->createAchievementMock(1, 'combat');
        $a2 = $this->createAchievementMock(2, 'combat');
        $a3 = $this->createAchievementMock(3, 'quests');

        $pa1 = $this->createPlayerAchievementMock($a1, completed: true);
        $pa2 = $this->createPlayerAchievementMock($a2, completed: true);
        $pa3 = $this->createPlayerAchievementMock($a3, completed: false);

        $achievementRepo = $this->createMock(EntityRepository::class);
        $achievementRepo->method('findAll')->willReturn([$a1, $a2, $a3]);

        $playerAchievementRepo = $this->createMock(EntityRepository::class);
        $playerAchievementRepo->method('findBy')->willReturn([$pa1, $pa2, $pa3]);

        $this->entityManager->method('getRepository')->willReturnCallback(
            fn (string $class) => match ($class) {
                Achievement::class => $achievementRepo,
                PlayerAchievement::class => $playerAchievementRepo,
                default => $this->createMock(EntityRepository::class),
            },
        );

        $response = $this->controller->index();

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals(2, $this->capturedTemplateParams['completedCount']);
        $this->assertEquals(3, $this->capturedTemplateParams['totalAchievements']);
    }

    private function createAchievementMock(int $id, string $category): Achievement&MockObject
    {
        $achievement = $this->createMock(Achievement::class);
        $achievement->method('getId')->willReturn($id);
        $achievement->method('getCategory')->willReturn($category);

        return $achievement;
    }

    private function createPlayerAchievementMock(Achievement&MockObject $achievement, bool $completed): PlayerAchievement&MockObject
    {
        $pa = $this->createMock(PlayerAchievement::class);
        $pa->method('getAchievement')->willReturn($achievement);
        $pa->method('isCompleted')->willReturn($completed);

        return $pa;
    }

    private function createContainer(): ContainerInterface&MockObject
    {
        $authChecker = $this->createMock(AuthorizationCheckerInterface::class);
        $authChecker->method('isGranted')->willReturn(true);

        $twig = $this->createMock(TwigEnvironment::class);
        $twig->method('render')->willReturnCallback(function (string $view, array $params): string {
            $this->capturedTemplateParams = $params;

            return '<html></html>';
        });

        $services = [
            'security.authorization_checker' => $authChecker,
            'twig' => $twig,
        ];

        $container = $this->createMock(ContainerInterface::class);
        $container->method('has')->willReturnCallback(fn (string $id) => isset($services[$id]));
        $container->method('get')->willReturnCallback(fn (string $id) => $services[$id] ?? null);

        return $container;
    }
}
