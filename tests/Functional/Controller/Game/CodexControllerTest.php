<?php

namespace App\Tests\Functional\Controller\Game;

use App\Controller\Game\CodexController;
use App\Entity\App\Player;
use App\Entity\Game\CodexEntry;
use App\Helper\PlayerHelper;
use App\Repository\CodexEntryRepository;
use App\Repository\PlayerCodexEntryRepository;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Twig\Environment as TwigEnvironment;

class CodexControllerTest extends TestCase
{
    private PlayerHelper&MockObject $playerHelper;
    private CodexEntryRepository&MockObject $codexEntryRepository;
    private PlayerCodexEntryRepository&MockObject $playerCodexEntryRepository;
    private CodexController $controller;

    /** @var array<string, mixed>|null */
    private ?array $capturedTemplateParams = null;

    protected function setUp(): void
    {
        $this->playerHelper = $this->createMock(PlayerHelper::class);
        $this->codexEntryRepository = $this->createMock(CodexEntryRepository::class);
        $this->playerCodexEntryRepository = $this->createMock(PlayerCodexEntryRepository::class);

        $this->controller = new CodexController(
            $this->playerHelper,
            $this->codexEntryRepository,
            $this->playerCodexEntryRepository,
        );

        $this->controller->setContainer($this->createContainer());
    }

    public function testIndexGroupsByCategoryAndCountsUnlocked(): void
    {
        $player = $this->createMock(Player::class);
        $this->playerHelper->method('getPlayer')->willReturn($player);

        $region1 = $this->createEntryMock(1, CodexEntry::CATEGORY_REGION);
        $region2 = $this->createEntryMock(2, CodexEntry::CATEGORY_REGION);
        $bestiary = $this->createEntryMock(3, CodexEntry::CATEGORY_BESTIARY_LORE);

        $this->codexEntryRepository->method('findAllOrdered')->willReturn([$region1, $region2, $bestiary]);
        // Le joueur a debloque region1 et bestiary (2 sur 3).
        $this->playerCodexEntryRepository->method('unlockedEntryIds')->willReturn([1, 3]);

        $response = $this->controller->index();

        self::assertSame(200, $response->getStatusCode());
        self::assertNotNull($this->capturedTemplateParams);
        self::assertSame(3, $this->capturedTemplateParams['totalCount']);
        self::assertSame(2, $this->capturedTemplateParams['unlockedCount']);
        self::assertSame([1, 3], $this->capturedTemplateParams['unlockedIds']);

        $categories = $this->capturedTemplateParams['categories'];
        self::assertArrayHasKey(CodexEntry::CATEGORY_REGION, $categories);
        self::assertArrayHasKey(CodexEntry::CATEGORY_BESTIARY_LORE, $categories);
        self::assertCount(2, $categories[CodexEntry::CATEGORY_REGION]);
        self::assertCount(1, $categories[CodexEntry::CATEGORY_BESTIARY_LORE]);
    }

    public function testIndexWithNoUnlocks(): void
    {
        $player = $this->createMock(Player::class);
        $this->playerHelper->method('getPlayer')->willReturn($player);

        $this->codexEntryRepository->method('findAllOrdered')->willReturn([
            $this->createEntryMock(1, CodexEntry::CATEGORY_REGION),
        ]);
        $this->playerCodexEntryRepository->method('unlockedEntryIds')->willReturn([]);

        $response = $this->controller->index();

        self::assertSame(200, $response->getStatusCode());
        self::assertSame(1, $this->capturedTemplateParams['totalCount']);
        self::assertSame(0, $this->capturedTemplateParams['unlockedCount']);
    }

    private function createEntryMock(int $id, string $category): CodexEntry&MockObject
    {
        $entry = $this->createMock(CodexEntry::class);
        $entry->method('getId')->willReturn($id);
        $entry->method('getCategory')->willReturn($category);

        return $entry;
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
