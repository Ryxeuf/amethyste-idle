<?php

namespace App\Tests\Functional\Controller\Game;

use App\Controller\Game\PlayerProfileController;
use App\Entity\App\Inventory;
use App\Entity\App\Player;
use App\Entity\App\PlayerAchievement;
use App\Entity\App\PlayerBestiary;
use App\GameEngine\Renown\PlayerReportManager;
use App\Helper\PlayerHelper;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Twig\Environment as TwigEnvironment;

class PlayerProfileControllerTest extends TestCase
{
    private PlayerHelper&MockObject $playerHelper;
    private EntityManagerInterface&MockObject $entityManager;
    private PlayerReportManager&MockObject $reportManager;
    private PlayerProfileController $controller;

    /** @var array<string, mixed>|null */
    private ?array $capturedTemplateParams = null;

    protected function setUp(): void
    {
        $this->playerHelper = $this->createMock(PlayerHelper::class);
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->reportManager = $this->createMock(PlayerReportManager::class);

        $this->controller = new PlayerProfileController(
            $this->playerHelper,
            $this->entityManager,
            $this->reportManager,
        );

        $this->controller->setContainer($this->createContainer());
    }

    public function testShowRendersProfileWithEquipmentAndFeatured(): void
    {
        $targetPlayer = $this->createPlayerMock(42);
        $currentPlayer = $this->createPlayerMock(42);

        $this->playerHelper->method('getPlayer')->willReturn($currentPlayer);

        $pa1 = $this->createMock(PlayerAchievement::class);
        $pa1->method('isCompleted')->willReturn(true);
        $pa1->method('isFeatured')->willReturn(true);

        $pa2 = $this->createMock(PlayerAchievement::class);
        $pa2->method('isCompleted')->willReturn(true);
        $pa2->method('isFeatured')->willReturn(false);

        $pa3 = $this->createMock(PlayerAchievement::class);
        $pa3->method('isCompleted')->willReturn(false);
        $pa3->method('isFeatured')->willReturn(false);

        $playerRepo = $this->createMock(EntityRepository::class);
        $playerRepo->method('find')->with(42)->willReturn($targetPlayer);

        $achievementRepo = $this->createMock(EntityRepository::class);
        $achievementRepo->method('findBy')->willReturn([$pa1, $pa2, $pa3]);

        $this->entityManager->method('getRepository')->willReturnCallback(
            fn (string $class) => match ($class) {
                Player::class => $playerRepo,
                PlayerAchievement::class => $achievementRepo,
                default => $this->createMock(EntityRepository::class),
            },
        );

        $response = $this->controller->show(42);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertNotNull($this->capturedTemplateParams);
        $this->assertTrue($this->capturedTemplateParams['isOwnProfile']);
        $this->assertCount(2, $this->capturedTemplateParams['completedAchievements']);
        $this->assertCount(1, $this->capturedTemplateParams['featuredAchievements']);
        $this->assertArrayHasKey('equippedItems', $this->capturedTemplateParams);
    }

    public function testShowOtherPlayerProfile(): void
    {
        $targetPlayer = $this->createPlayerMock(99);
        $currentPlayer = $this->createPlayerMock(1);

        $this->playerHelper->method('getPlayer')->willReturn($currentPlayer);

        $playerRepo = $this->createMock(EntityRepository::class);
        $playerRepo->method('find')->with(99)->willReturn($targetPlayer);

        $achievementRepo = $this->createMock(EntityRepository::class);
        $achievementRepo->method('findBy')->willReturn([]);

        $this->entityManager->method('getRepository')->willReturnCallback(
            fn (string $class) => match ($class) {
                Player::class => $playerRepo,
                PlayerAchievement::class => $achievementRepo,
                default => $this->createMock(EntityRepository::class),
            },
        );

        $response = $this->controller->show(99);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertFalse($this->capturedTemplateParams['isOwnProfile']);
    }

    public function testToggleFeaturedSuccess(): void
    {
        $currentPlayer = $this->createMock(Player::class);
        $currentPlayer->method('getId')->willReturn(1);
        $this->playerHelper->method('getPlayer')->willReturn($currentPlayer);

        $pa = $this->createMock(PlayerAchievement::class);
        $pa->method('getPlayer')->willReturn($currentPlayer);
        $pa->method('isCompleted')->willReturn(true);
        $pa->method('isFeatured')->willReturn(false);
        $pa->expects($this->once())->method('setFeatured')->with(true);

        $achievementRepo = $this->createMock(EntityRepository::class);
        $achievementRepo->method('find')->with(10)->willReturn($pa);
        $achievementRepo->method('count')->willReturn(2);

        $this->entityManager->method('getRepository')->willReturnCallback(
            fn (string $class) => match ($class) {
                PlayerAchievement::class => $achievementRepo,
                default => $this->createMock(EntityRepository::class),
            },
        );
        $this->entityManager->expects($this->once())->method('flush');

        $response = $this->controller->toggleFeatured(10);

        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testToggleFeaturedLimitReached(): void
    {
        $currentPlayer = $this->createMock(Player::class);
        $currentPlayer->method('getId')->willReturn(1);
        $this->playerHelper->method('getPlayer')->willReturn($currentPlayer);

        $pa = $this->createMock(PlayerAchievement::class);
        $pa->method('getPlayer')->willReturn($currentPlayer);
        $pa->method('isCompleted')->willReturn(true);
        $pa->method('isFeatured')->willReturn(false);

        $achievementRepo = $this->createMock(EntityRepository::class);
        $achievementRepo->method('find')->with(10)->willReturn($pa);
        $achievementRepo->method('count')->willReturn(5);

        $this->entityManager->method('getRepository')->willReturnCallback(
            fn (string $class) => match ($class) {
                PlayerAchievement::class => $achievementRepo,
                default => $this->createMock(EntityRepository::class),
            },
        );

        $response = $this->controller->toggleFeatured(10);

        $this->assertEquals(400, $response->getStatusCode());
    }

    private function createPlayerMock(int $id): Player&MockObject
    {
        $player = $this->createMock(Player::class);
        $player->method('getId')->willReturn($id);
        $player->method('getDomainExperiences')->willReturn(new ArrayCollection());
        $player->method('getSkills')->willReturn(new ArrayCollection());

        $bestiary = $this->createMock(PlayerBestiary::class);
        $bestiary->method('getKillCount')->willReturn(10);
        $player->method('getBestiaryEntries')->willReturn(new ArrayCollection([$bestiary]));

        $inventory = $this->createMock(Inventory::class);
        $inventory->method('getType')->willReturn(Inventory::TYPE_BAG);
        $inventory->method('getItems')->willReturn(new ArrayCollection());
        $player->method('getInventories')->willReturn(new ArrayCollection([$inventory]));

        return $player;
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
