<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller\Game\Inventory;

use App\Controller\Game\Inventory\EquipmentController;
use App\Entity\App\Inventory;
use App\Entity\App\Player;
use App\GameEngine\Fight\EquipmentSetResolver;
use App\GameEngine\Player\PlayerActionHelper;
use App\GameEngine\Player\PlayerEffectiveStatsCalculator;
use App\Helper\GearHelper;
use App\Helper\PlayerHelper;
use App\Helper\PlayerItemHelper;
use App\Service\Avatar\PlayerAvatarPayloadBuilder;
use Doctrine\Common\Collections\ArrayCollection;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Twig\Environment as TwigEnvironment;

class EquipmentControllerTest extends TestCase
{
    private PlayerHelper&MockObject $playerHelper;
    private GearHelper&MockObject $gearHelper;
    private EquipmentSetResolver&MockObject $equipmentSetResolver;
    private PlayerEffectiveStatsCalculator&MockObject $statsCalculator;
    private PlayerActionHelper&MockObject $playerActionHelper;
    private PlayerItemHelper&MockObject $playerItemHelper;
    private PlayerAvatarPayloadBuilder&MockObject $avatarPayloadBuilder;
    private EquipmentController $controller;

    /** @var array<string, mixed>|null */
    private ?array $capturedTemplateParams = null;

    protected function setUp(): void
    {
        $this->playerHelper = $this->createMock(PlayerHelper::class);
        $this->gearHelper = $this->createMock(GearHelper::class);
        $this->equipmentSetResolver = $this->createMock(EquipmentSetResolver::class);
        $this->statsCalculator = $this->createMock(PlayerEffectiveStatsCalculator::class);
        $this->playerActionHelper = $this->createMock(PlayerActionHelper::class);
        $this->playerItemHelper = $this->createMock(PlayerItemHelper::class);
        $this->avatarPayloadBuilder = $this->createMock(PlayerAvatarPayloadBuilder::class);

        $this->controller = new EquipmentController(
            $this->playerHelper,
            $this->gearHelper,
            $this->equipmentSetResolver,
            $this->statsCalculator,
            $this->playerActionHelper,
            $this->playerItemHelper,
            $this->avatarPayloadBuilder,
        );

        $this->controller->setContainer($this->createContainer());
    }

    public function testInvokePassesAvatarPayloadToTemplate(): void
    {
        $player = $this->createMock(Player::class);
        $this->setupCommonExpectations($player);

        $payload = [
            'renderMode' => 'avatar',
            'avatarHash' => 'abc123',
            'avatar' => [
                'baseSheet' => '/assets/styles/images/avatar/body/human_m_light.png',
                'layers' => [],
            ],
        ];
        $this->avatarPayloadBuilder->expects($this->once())
            ->method('build')
            ->with($player)
            ->willReturn($payload);

        $response = ($this->controller)();

        $this->assertSame(200, $response->getStatusCode());
        $this->assertNotNull($this->capturedTemplateParams);
        $this->assertSame($payload, $this->capturedTemplateParams['avatarPayload']);
    }

    public function testInvokePassesNullPayloadWhenPlayerHasNoAvatar(): void
    {
        $player = $this->createMock(Player::class);
        $this->setupCommonExpectations($player);

        $this->avatarPayloadBuilder->method('build')->with($player)->willReturn(null);

        $response = ($this->controller)();

        $this->assertSame(200, $response->getStatusCode());
        $this->assertNull($this->capturedTemplateParams['avatarPayload'] ?? 'missing');
    }

    private function setupCommonExpectations(Player $player): void
    {
        $bag = $this->createMock(Inventory::class);
        $bag->method('getItems')->willReturn(new ArrayCollection([]));
        $this->playerHelper->method('getBagInventory')->willReturn($bag);
        $this->playerHelper->method('getPlayer')->willReturn($player);

        $this->gearHelper->method('getEquippedGearByLocation')->willReturn(null);
        $this->gearHelper->method('getEquippedToolByType')->willReturn(null);

        $this->equipmentSetResolver->method('getActiveSets')->willReturn([]);
        $this->equipmentSetResolver->method('getSetBonuses')->willReturn([
            'protection' => 0,
            'hit' => 0,
            'damage' => 0,
        ]);

        $this->statsCalculator->method('getInventorySheetStats')->willReturn([
            'hit' => 1,
            'hitBonus' => 0,
            'protection' => 0,
            'speed' => 5,
            'life' => 10,
            'maxLife' => 10,
            'maxLifeBonus' => 0,
            'energy' => 5,
            'maxEnergy' => 5,
        ]);

        $player->method('getUnlockedToolSlots')->willReturn([]);
        $this->playerActionHelper->method('getUnlockedToolSlots')->willReturn([]);
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
        $router->method('generate')->willReturn('/game');

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
