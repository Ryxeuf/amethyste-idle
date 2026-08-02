<?php

namespace App\Tests\Functional\Controller\Game;

use App\Controller\Game\ShopController;
use App\Entity\App\Inventory;
use App\Entity\App\Player;
use App\Entity\App\PlayerItem;
use App\Entity\App\Pnj;
use App\Entity\App\Zone;
use App\Entity\Game\Item;
use App\GameEngine\GameMaster\GameMasterPolicy;
use App\GameEngine\Guild\RegionBonusProvider;
use App\GameEngine\Renown\PlayerRenownDiscountProvider;
use App\GameEngine\Reputation\HostileConsequenceResolver;
use App\GameEngine\World\GameTimeService;
use App\GameEngine\World\StaticUtcDayCycleFactorProvider;
use App\Helper\PlayerHelper;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class ShopControllerTest extends TestCase
{
    private PlayerHelper&MockObject $playerHelper;
    private EntityManagerInterface&MockObject $entityManager;
    private GameTimeService $gameTimeService;
    private RegionBonusProvider&MockObject $regionBonusProvider;
    private PlayerRenownDiscountProvider $renownDiscountProvider;
    private HostileConsequenceResolver&MockObject $hostileConsequences;
    private ShopController $controller;

    protected function setUp(): void
    {
        $this->playerHelper = $this->createMock(PlayerHelper::class);
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->gameTimeService = new GameTimeService(new StaticUtcDayCycleFactorProvider(1.0));
        $this->regionBonusProvider = $this->createMock(RegionBonusProvider::class);
        $this->renownDiscountProvider = new PlayerRenownDiscountProvider();
        // Par defaut, pas de surcharge (le mock rend 0) : les cas FAC-03 la
        // configurent explicitement.
        $this->hostileConsequences = $this->createMock(HostileConsequenceResolver::class);

        $this->controller = new ShopController(
            $this->playerHelper,
            $this->entityManager,
            $this->gameTimeService,
            $this->regionBonusProvider,
            $this->renownDiscountProvider,
            new GameMasterPolicy(),
            $this->hostileConsequences,
        );

        $authChecker = $this->createMock(AuthorizationCheckerInterface::class);
        $authChecker->method('isGranted')->willReturn(true);

        $container = $this->createMock(ContainerInterface::class);
        $container->method('has')->willReturn(true);
        $container->method('get')->willReturnCallback(
            fn (string $id) => $id === 'security.authorization_checker' ? $authChecker : null,
        );
        $this->controller->setContainer($container);
    }

    public function testBuySuccess(): void
    {
        $pnj = $this->createPnjMock(['iron-sword']);
        $item = $this->createItemMock('iron-sword', 100, 'Épée en fer');
        $this->setupRepositories(pnj: $pnj, item: $item);

        $player = $this->createPlayerMock(500);
        $this->playerHelper->method('getPlayer')->willReturn($player);
        $this->playerHelper->method('getBagInventory')->willReturn($this->createMock(Inventory::class));

        $player->expects($this->once())->method('removeGils')->with(100);
        $this->entityManager->expects($this->once())->method('flush');

        $response = $this->controller->buy(1, $this->createBuyRequest('iron-sword'));

        $this->assertEquals(200, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertTrue($data['success']);
        $this->assertStringContainsString('acheté', $data['message']);
    }

    public function testBuyAppliesRenownDiscount(): void
    {
        $pnj = $this->createPnjMock(['iron-sword']);
        $item = $this->createItemMock('iron-sword', 100, 'Épée en fer');
        $this->setupRepositories(pnj: $pnj, item: $item);

        // Legendaire tier -> 10% renown discount
        $player = $this->createPlayerMock(500, 20000);
        $this->playerHelper->method('getPlayer')->willReturn($player);
        $this->playerHelper->method('getBagInventory')->willReturn($this->createMock(Inventory::class));

        // 100 * (1 - 0.10) = 90
        $player->expects($this->once())->method('removeGils')->with(90);
        $this->entityManager->expects($this->once())->method('flush');

        $response = $this->controller->buy(1, $this->createBuyRequest('iron-sword'));

        $this->assertEquals(200, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertTrue($data['success']);
        $this->assertStringContainsString('renommée', $data['message']);
    }

    /**
     * FAC-03 — Hostile chez les Marchands : la boutique fait payer la rancune.
     * 100 gils deviennent 110 — une surcharge, appliquee apres les remises.
     */
    public function testBuyAppliesHostileSurcharge(): void
    {
        $pnj = $this->createPnjMock(['iron-sword']);
        $item = $this->createItemMock('iron-sword', 100, 'Épée en fer');
        $this->setupRepositories(pnj: $pnj, item: $item);

        $player = $this->createPlayerMock(500);
        $this->playerHelper->method('getPlayer')->willReturn($player);
        $this->playerHelper->method('getBagInventory')->willReturn($this->createMock(Inventory::class));

        $this->hostileConsequences->method('shopSurchargePercent')->with($player)->willReturn(10);

        $player->expects($this->once())->method('removeGils')->with(110);
        $this->entityManager->expects($this->once())->method('flush');

        $response = $this->controller->buy(1, $this->createBuyRequest('iron-sword'));

        $this->assertEquals(200, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertTrue($data['success']);
    }

    /**
     * Le garde-fou de FAC-03 : la surcharge ne ferme jamais la boutique. Le
     * plancher T1 (ECO-02) reste un droit — un Hostile qui a les gils achete,
     * simplement plus cher. Seul le manque de gils refuse, comme pour tous.
     */
    public function testAHostilePlayerCanStillBuyWhenSolvent(): void
    {
        $pnj = $this->createPnjMock(['bronze-pickaxe']);
        $item = $this->createItemMock('bronze-pickaxe', 50, 'Pioche de bronze');
        $this->setupRepositories(pnj: $pnj, item: $item);

        $player = $this->createPlayerMock(55);
        $this->playerHelper->method('getPlayer')->willReturn($player);
        $this->playerHelper->method('getBagInventory')->willReturn($this->createMock(Inventory::class));

        $this->hostileConsequences->method('shopSurchargePercent')->willReturn(10);

        $player->expects($this->once())->method('removeGils')->with(55);

        $response = $this->controller->buy(1, $this->createBuyRequest('bronze-pickaxe'));

        $this->assertEquals(200, $response->getStatusCode(), 'L\'hostilite surcharge le prix, elle ne ferme jamais la boutique.');
    }

    public function testBuyInsufficientGils(): void
    {
        $pnj = $this->createPnjMock(['iron-sword']);
        $item = $this->createItemMock('iron-sword', 1000, 'Épée en fer');
        $this->setupRepositories(pnj: $pnj, item: $item);

        $player = $this->createPlayerMock(50);
        $this->playerHelper->method('getPlayer')->willReturn($player);

        $response = $this->controller->buy(1, $this->createBuyRequest('iron-sword'));

        $this->assertEquals(400, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertStringContainsString('Pas assez de Gils', $data['error']);
    }

    public function testBuyItemNotInShop(): void
    {
        $pnj = $this->createPnjMock(['potion-heal']);
        $this->setupRepositories(pnj: $pnj);

        $response = $this->controller->buy(1, $this->createBuyRequest('legendary-sword'));

        $this->assertEquals(400, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertStringContainsString('pas en vente', $data['error']);
    }

    public function testBuyShopNotFound(): void
    {
        $pnjRepo = $this->createMock(EntityRepository::class);
        $pnjRepo->method('find')->willReturn(null);
        $this->entityManager->method('getRepository')->willReturn($pnjRepo);

        $response = $this->controller->buy(999, $this->createBuyRequest('iron-sword'));

        $this->assertEquals(404, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertStringContainsString('introuvable', $data['error']);
    }

    public function testSellSoulboundItemRejected(): void
    {
        $item = $this->createItemMock('bound-ring', 200, 'Anneau lié');

        $playerItem = $this->createMock(PlayerItem::class);
        $playerItem->method('isBound')->willReturn(true);
        $playerItem->method('getGenericItem')->willReturn($item);

        $playerItemRepo = $this->createMock(EntityRepository::class);
        $playerItemRepo->method('find')->willReturn($playerItem);
        $this->entityManager->method('getRepository')->willReturn($playerItemRepo);

        $player = $this->createPlayerMock(500);
        $this->playerHelper->method('getPlayer')->willReturn($player);

        $request = Request::create('/game/shop/1/sell', 'POST', [], [], [], [], json_encode([
            'playerItemId' => 1,
        ]));
        $response = $this->controller->sell(1, $request);

        $this->assertEquals(400, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertStringContainsString('lié', $data['error']);
    }

    private function createBuyRequest(string $slug, int $quantity = 1): Request
    {
        return Request::create('/game/shop/1/buy', 'POST', [], [], [], [], json_encode([
            'itemSlug' => $slug,
            'quantity' => $quantity,
        ]));
    }

    public function testShopIsUnreachableFromAnotherZone(): void
    {
        // ZON-27 : la boutique d'un PNJ ne s'ouvre que depuis sa zone. Sans ce
        // garde-fou, connaitre l'identifiant suffirait a commercer a distance.
        $pnjZone = $this->createMock(Zone::class);
        $pnjZone->method('getId')->willReturn(1);
        $playerZone = $this->createMock(Zone::class);
        $playerZone->method('getId')->willReturn(2);

        $pnj = $this->createPnjMock([]);
        $pnj->method('getZone')->willReturn($pnjZone);

        $player = $this->createPlayerMock(1000);
        $player->method('getCurrentZone')->willReturn($playerZone);
        $this->playerHelper->method('getPlayer')->willReturn($player);

        $this->setupRepositories($pnj);

        $this->expectException(NotFoundHttpException::class);
        $this->controller->index(1);
    }

    private function createPlayerMock(int $gils, int $renownScore = 0): Player&MockObject
    {
        $currentGils = $gils;
        $player = $this->createMock(Player::class);
        $player->method('getId')->willReturn(1);
        $player->method('getGils')->willReturnCallback(fn () => $currentGils);
        $player->method('removeGils')->willReturnCallback(function (int $amount) use (&$currentGils): bool {
            $currentGils -= $amount;

            return true;
        });
        $player->method('getRenownScore')->willReturn($renownScore);

        return $player;
    }

    private function createPnjMock(array $shopItems): Pnj&MockObject
    {
        $pnj = $this->createMock(Pnj::class);
        $pnj->method('isMerchant')->willReturn(true);
        $pnj->method('getShopItems')->willReturn($shopItems);
        $pnj->method('isShopOpen')->willReturn(true);

        return $pnj;
    }

    private function createItemMock(string $slug, int $price, string $name = 'Item'): Item&MockObject
    {
        $item = $this->createMock(Item::class);
        $item->method('getSlug')->willReturn($slug);
        $item->method('getPrice')->willReturn($price);
        $item->method('getName')->willReturn($name);
        $item->method('isBoundOnPickup')->willReturn(false);

        return $item;
    }

    private function setupRepositories(?Pnj $pnj = null, ?Item $item = null): void
    {
        $pnjRepo = $this->createMock(EntityRepository::class);
        $pnjRepo->method('find')->willReturn($pnj);

        $itemRepo = $this->createMock(EntityRepository::class);
        $itemRepo->method('findOneBy')->willReturn($item);

        $this->entityManager->method('getRepository')->willReturnCallback(
            fn (string $class) => match ($class) {
                Pnj::class => $pnjRepo,
                Item::class => $itemRepo,
                default => $this->createMock(EntityRepository::class),
            },
        );
    }
}
