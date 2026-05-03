<?php

namespace App\Tests\Functional\Controller\Game;

use App\Controller\Game\MountController;
use App\Entity\App\Player;
use App\Entity\Game\Mount;
use App\Helper\PlayerHelper;
use App\Repository\PlayerMountRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Twig\Environment as TwigEnvironment;

class MountControllerTest extends TestCase
{
    private EntityManagerInterface&MockObject $entityManager;
    private EntityRepository&MockObject $repository;
    private PlayerHelper&MockObject $playerHelper;
    private PlayerMountRepository&MockObject $playerMountRepository;
    private MountController $controller;

    /** @var array<string, mixed>|null */
    private ?array $capturedTemplateParams = null;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->repository = $this->createMock(EntityRepository::class);
        $this->entityManager->method('getRepository')->with(Mount::class)->willReturn($this->repository);

        $this->playerHelper = $this->createMock(PlayerHelper::class);
        $this->playerMountRepository = $this->createMock(PlayerMountRepository::class);

        $this->controller = new MountController(
            $this->entityManager,
            $this->playerHelper,
            $this->playerMountRepository,
        );
        $this->controller->setContainer($this->createContainer());
    }

    public function testIndexRendersEnabledMountsOrderedByLevelAndCost(): void
    {
        $horse = $this->buildMount('horse_brown', 'Cheval brun', Mount::OBTENTION_PURCHASE, 1, 5000);
        $wolf = $this->buildMount('wolf_dire', 'Loup sauvage', Mount::OBTENTION_QUEST, 10, null);

        $this->repository->expects($this->once())
            ->method('findBy')
            ->with(['enabled' => true], ['requiredLevel' => 'ASC', 'gilCost' => 'ASC'])
            ->willReturn([$horse, $wolf]);

        $this->playerHelper->method('getPlayer')->willReturn(null);

        $response = $this->controller->index();

        $this->assertSame(200, $response->getStatusCode());
        $this->assertNotNull($this->capturedTemplateParams);
        $this->assertSame([$horse, $wolf], $this->capturedTemplateParams['mounts']);
        $this->assertSame([], $this->capturedTemplateParams['ownedMountIds']);
        $labels = $this->capturedTemplateParams['obtentionLabels'];
        $this->assertSame('game.mount.obtention.quest', $labels[Mount::OBTENTION_QUEST]);
        $this->assertSame('game.mount.obtention.drop', $labels[Mount::OBTENTION_DROP]);
        $this->assertSame('game.mount.obtention.purchase', $labels[Mount::OBTENTION_PURCHASE]);
        $this->assertSame('game.mount.obtention.achievement', $labels[Mount::OBTENTION_ACHIEVEMENT]);
    }

    public function testIndexRendersEmptyCatalogGracefully(): void
    {
        $this->repository->expects($this->once())
            ->method('findBy')
            ->willReturn([]);

        $this->playerHelper->method('getPlayer')->willReturn(null);

        $response = $this->controller->index();

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame([], $this->capturedTemplateParams['mounts']);
        $this->assertSame([], $this->capturedTemplateParams['ownedMountIds']);
    }

    public function testObtentionLabelsCoverAllEnumValues(): void
    {
        $this->repository->method('findBy')->willReturn([]);
        $this->playerHelper->method('getPlayer')->willReturn(null);

        $this->controller->index();

        $labels = $this->capturedTemplateParams['obtentionLabels'];
        foreach (Mount::getObtentionTypes() as $type) {
            $this->assertArrayHasKey($type, $labels, sprintf('Missing label for obtention type %s', $type));
        }
    }

    public function testIndexExposesOwnedMountIdsForCurrentPlayer(): void
    {
        $horse = $this->buildMount('horse_brown', 'Cheval brun', Mount::OBTENTION_PURCHASE, 1, 5000);
        $wolf = $this->buildMount('wolf_dire', 'Loup sauvage', Mount::OBTENTION_QUEST, 10, null);

        $this->repository->method('findBy')->willReturn([$horse, $wolf]);

        $player = $this->createMock(Player::class);
        $this->playerHelper->method('getPlayer')->willReturn($player);

        $this->playerMountRepository->expects($this->once())
            ->method('findOwnedMountIds')
            ->with($player)
            ->willReturn([42, 99]);

        $this->controller->index();

        $this->assertSame([42, 99], $this->capturedTemplateParams['ownedMountIds']);
    }

    public function testIndexSkipsRepositoryLookupWhenNoPlayer(): void
    {
        $this->repository->method('findBy')->willReturn([]);
        $this->playerHelper->method('getPlayer')->willReturn(null);

        $this->playerMountRepository->expects($this->never())->method('findOwnedMountIds');

        $this->controller->index();

        $this->assertSame([], $this->capturedTemplateParams['ownedMountIds']);
    }

    private function buildMount(string $slug, string $name, string $obtention, int $level, ?int $gilCost): Mount
    {
        $mount = new Mount();
        $mount->setSlug($slug);
        $mount->setName($name);
        $mount->setDescription(sprintf('Description %s', $slug));
        $mount->setObtentionType($obtention);
        $mount->setRequiredLevel($level);
        $mount->setGilCost($gilCost);
        $mount->setEnabled(true);

        return $mount;
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

        $router = $this->createMock(UrlGeneratorInterface::class);
        $router->method('generate')->willReturn('/game/mounts');

        $services = [
            'security.authorization_checker' => $authChecker,
            'twig' => $twig,
            'router' => $router,
        ];

        $container = $this->createMock(ContainerInterface::class);
        $container->method('has')->willReturnCallback(fn (string $id) => isset($services[$id]));
        $container->method('get')->willReturnCallback(fn (string $id) => $services[$id] ?? null);

        return $container;
    }
}
