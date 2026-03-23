<?php

namespace App\Tests\Functional\Controller\Game\Inventory;

use App\Controller\Game\Inventory\EquipItemController;
use App\Controller\Game\Inventory\UnequipItemController;
use App\Controller\Game\Inventory\UseItemController;
use App\Entity\App\Inventory;
use App\Entity\App\Player;
use App\Entity\App\PlayerItem;
use App\Entity\Game\Item;
use App\Entity\Game\Spell;
use App\GameEngine\Fight\SpellApplicator;
use App\GameEngine\Progression\SkillAcquiring;
use App\Helper\GearHelper;
use App\Helper\InventoryHelper;
use App\Helper\ItemHelper;
use App\Helper\PlayerHelper;
use App\Helper\PlayerSkillHelper;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBag;
use Symfony\Component\HttpFoundation\Session\FlashBagAwareSessionInterface;
use Symfony\Component\HttpFoundation\Session\RequestStack;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class InventoryControllerTest extends TestCase
{
    private PlayerHelper&MockObject $playerHelper;
    private GearHelper&MockObject $gearHelper;
    private EntityManagerInterface&MockObject $entityManager;
    private FlashBag $flashBag;

    protected function setUp(): void
    {
        $this->playerHelper = $this->createMock(PlayerHelper::class);
        $this->gearHelper = $this->createMock(GearHelper::class);
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->flashBag = new FlashBag();
    }

    // --- EquipItemController ---

    public function testEquipItemSuccess(): void
    {
        $genericItem = $this->createMock(Item::class);
        $genericItem->method('isGear')->willReturn(true);
        $genericItem->method('getGearLocation')->willReturn('head');

        $playerItem = $this->createMock(PlayerItem::class);
        $playerItem->method('getId')->willReturn(42);
        $playerItem->method('getGenericItem')->willReturn($genericItem);

        $bag = $this->createMock(Inventory::class);
        $bag->method('getItems')->willReturn(new ArrayCollection([$playerItem]));
        $this->playerHelper->method('getBagInventory')->willReturn($bag);

        $this->gearHelper->method('getPlayerItemGearByLocation')->with('head')->willReturn(PlayerItem::GEAR_HEAD);
        $this->gearHelper->method('getEquippedGearByLocation')->with('head')->willReturn(null);

        $playerItem->expects($this->once())->method('setGear')->with(PlayerItem::GEAR_HEAD);
        $this->entityManager->expects($this->once())->method('flush');

        $controller = $this->createEquipController();
        $response = $controller->__invoke(42);

        $this->assertEquals(302, $response->getStatusCode());
    }

    public function testEquipItemNotFoundThrowsException(): void
    {
        $bag = $this->createMock(Inventory::class);
        $bag->method('getItems')->willReturn(new ArrayCollection([]));
        $this->playerHelper->method('getBagInventory')->willReturn($bag);

        $this->expectException(NotFoundHttpException::class);

        $controller = $this->createEquipController();
        $controller->__invoke(999);
    }

    // --- UnequipItemController ---

    public function testUnequipItemSuccess(): void
    {
        $genericItem = $this->createMock(Item::class);
        $genericItem->method('isGear')->willReturn(true);

        $playerItem = $this->createMock(PlayerItem::class);
        $playerItem->method('getId')->willReturn(42);
        $playerItem->method('getGenericItem')->willReturn($genericItem);

        $bag = $this->createMock(Inventory::class);
        $bag->method('getItems')->willReturn(new ArrayCollection([$playerItem]));
        $this->playerHelper->method('getBagInventory')->willReturn($bag);

        $this->gearHelper->method('isEquipped')->with($playerItem)->willReturn(true);

        $playerItem->expects($this->once())->method('setGear')->with(0);
        $this->entityManager->expects($this->once())->method('flush');

        $controller = $this->createUnequipController();
        $response = $controller->__invoke(42);

        $this->assertEquals(302, $response->getStatusCode());
    }

    public function testUnequipItemNotFoundThrowsException(): void
    {
        $bag = $this->createMock(Inventory::class);
        $bag->method('getItems')->willReturn(new ArrayCollection([]));
        $this->playerHelper->method('getBagInventory')->willReturn($bag);

        $this->expectException(NotFoundHttpException::class);

        $controller = $this->createUnequipController();
        $controller->__invoke(999);
    }

    // --- UseItemController ---

    public function testUseConsumableAppliesSpellAndDecrementsUsage(): void
    {
        $spell = $this->createMock(Spell::class);

        $genericItem = $this->createMock(Item::class);
        $genericItem->method('isObject')->willReturn(true);
        $genericItem->method('getName')->willReturn('Potion de soin');

        $nbUsages = 1;
        $playerItem = $this->createMock(PlayerItem::class);
        $playerItem->method('getGenericItem')->willReturn($genericItem);
        $playerItem->method('getNbUsages')->willReturnCallback(function () use (&$nbUsages) {
            return $nbUsages;
        });
        $playerItem->method('setNbUsages')->willReturnCallback(function (int $n) use (&$nbUsages) {
            $nbUsages = $n;
        });

        $player = $this->createMock(Player::class);
        $player->method('getFight')->willReturn(null);
        $player->method('isDead')->willReturn(false);

        $playerItemRepo = $this->createMock(EntityRepository::class);
        $playerItemRepo->method('find')->with(10)->willReturn($playerItem);
        $this->entityManager->method('getRepository')->willReturn($playerItemRepo);

        $this->playerHelper->method('getPlayer')->willReturn($player);

        $inventoryHelper = $this->createMock(InventoryHelper::class);
        $inventoryHelper->method('hasItem')->with($playerItem)->willReturn(true);

        $itemHelper = $this->createMock(ItemHelper::class);
        $itemHelper->method('isUsable')->willReturn(true);
        $itemHelper->method('getItemSpell')->willReturn($spell);
        $itemHelper->method('getItemSpellModifiers')->willReturn([]);

        $spellApplicator = $this->createMock(SpellApplicator::class);
        $spellApplicator->expects($this->once())->method('apply')->with($spell, $player, $player, []);

        $this->entityManager->expects($this->once())->method('remove')->with($playerItem);
        $this->entityManager->expects($this->once())->method('flush');

        $controller = $this->createUseItemController($itemHelper, $inventoryHelper, $spellApplicator);
        $response = $controller->__invoke(10);

        $this->assertEquals(302, $response->getStatusCode());
        $this->assertNotEmpty($this->flashBag->peek('success'));
    }

    // --- Helpers ---

    private function createEquipController(): EquipItemController
    {
        $controller = new EquipItemController(
            $this->playerHelper,
            $this->gearHelper,
            $this->entityManager,
        );
        $controller->setContainer($this->createContainerWithRouter());

        return $controller;
    }

    private function createUnequipController(): UnequipItemController
    {
        $controller = new UnequipItemController(
            $this->playerHelper,
            $this->gearHelper,
            $this->entityManager,
        );
        $controller->setContainer($this->createContainerWithRouter());

        return $controller;
    }

    private function createUseItemController(
        ItemHelper&MockObject $itemHelper,
        InventoryHelper&MockObject $inventoryHelper,
        SpellApplicator&MockObject $spellApplicator,
    ): UseItemController {
        $controller = new UseItemController(
            $this->playerHelper,
            $itemHelper,
            $inventoryHelper,
            $this->entityManager,
            $this->createMock(SkillAcquiring::class),
            $this->createMock(PlayerSkillHelper::class),
            $spellApplicator,
        );
        $controller->setContainer($this->createContainerWithRouter());

        return $controller;
    }

    private function createContainerWithRouter(): ContainerInterface&MockObject
    {
        $authChecker = $this->createMock(AuthorizationCheckerInterface::class);
        $authChecker->method('isGranted')->willReturn(true);

        $router = $this->createMock(UrlGeneratorInterface::class);
        $router->method('generate')->willReturn('/mocked-url');

        $session = $this->createMock(FlashBagAwareSessionInterface::class);
        $session->method('getFlashBag')->willReturn($this->flashBag);

        $requestStack = $this->createMock(\Symfony\Component\HttpFoundation\RequestStack::class);
        $requestStack->method('getSession')->willReturn($session);

        $services = [
            'security.authorization_checker' => $authChecker,
            'router' => $router,
            'request_stack' => $requestStack,
        ];

        $container = $this->createMock(ContainerInterface::class);
        $container->method('has')->willReturnCallback(fn (string $id) => isset($services[$id]));
        $container->method('get')->willReturnCallback(fn (string $id) => $services[$id] ?? null);

        return $container;
    }
}
