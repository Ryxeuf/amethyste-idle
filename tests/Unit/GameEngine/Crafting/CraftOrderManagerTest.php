<?php

namespace App\Tests\Unit\GameEngine\Crafting;

use App\Entity\App\Inventory;
use App\Entity\App\Player;
use App\Entity\App\PlayerItem;
use App\Entity\Game\Item;
use App\Entity\Game\Recipe;
use App\Enum\CraftOrderStatus;
use App\GameEngine\Crafting\CraftOrderManager;
use App\GameEngine\Region\PlayerRegionResolver;
use App\Repository\CraftOrderRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

/**
 * ECO-05 — commandes de craft et escrow des deux cotes.
 */
class CraftOrderManagerTest extends TestCase
{
    private EntityManagerInterface&MockObject $em;
    private CraftOrderRepository&MockObject $orderRepository;
    private CraftOrderManager $manager;

    protected function setUp(): void
    {
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->orderRepository = $this->createMock(CraftOrderRepository::class);
        $this->orderRepository->method('countActiveByRequester')->willReturn(0);
        $this->manager = new CraftOrderManager(
            $this->em,
            $this->orderRepository,
            new PlayerRegionResolver(),
            new NullLogger(),
        );
    }

    public function testCreateOrderEscrowsMaterialsAndCommission(): void
    {
        $requester = $this->createPlayer(1, 1_000);
        $materials = $this->createMaterials($requester, ['ore-iron', 'ore-iron']);
        $recipe = $this->createRecipe([['slug' => 'ore-iron', 'quantity' => 2]]);

        $order = $this->manager->createOrder($requester, $recipe, $materials, 300);

        self::assertSame(CraftOrderStatus::Open, $order->getStatus());
        self::assertSame(300, $order->getCommission());
        self::assertSame(700, $requester->getGils(), 'La commission quitte la bourse a la creation.');
        self::assertCount(2, $order->getMaterials());

        foreach ($materials as $material) {
            self::assertNull($material->getInventory(), 'Le materiau quitte l\'inventaire : c\'est ce qui rend l\'escrow reel.');
            self::assertSame($order, $material->getCraftOrder());
        }
    }

    /**
     * Le controle des materiaux vit a la creation, pas a l'execution : un artisan
     * qui prend une commande doit pouvoir la realiser. Decouvrir a la livraison
     * qu'il manque un minerai lui ferait perdre le temps de craft pour une faute
     * qui n'est pas la sienne.
     */
    public function testCreateOrderRefusesMaterialsThatDoNotCoverTheRecipe(): void
    {
        $requester = $this->createPlayer(1, 1_000);
        $materials = $this->createMaterials($requester, ['ore-iron']);
        $recipe = $this->createRecipe([['slug' => 'ore-iron', 'quantity' => 3]]);

        $this->em->expects($this->never())->method('persist');

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Materiaux insuffisants');

        $this->manager->createOrder($requester, $recipe, $materials, 300);
    }

    /**
     * ECO-01 : un objet lie ne circule pas, meme par le canal des commandes.
     */
    public function testCreateOrderRefusesABoundMaterial(): void
    {
        $requester = $this->createPlayer(1, 1_000);
        $materials = $this->createMaterials($requester, ['ore-iron', 'ore-iron']);
        $materials[0]->setBoundToPlayerId(1);
        $recipe = $this->createRecipe([['slug' => 'ore-iron', 'quantity' => 2]]);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('lie a son proprietaire');

        $this->manager->createOrder($requester, $recipe, $materials, 300);
    }

    /**
     * Rien n'est engage si la bourse ne suit pas : les materiaux doivent rester
     * en place, sinon une commission refusee couterait quand meme ses materiaux
     * au joueur.
     */
    public function testCreateOrderLeavesMaterialsInPlaceWhenTheCommissionCannotBePaid(): void
    {
        $requester = $this->createPlayer(1, 10);
        $materials = $this->createMaterials($requester, ['ore-iron', 'ore-iron']);
        $recipe = $this->createRecipe([['slug' => 'ore-iron', 'quantity' => 2]]);

        try {
            $this->manager->createOrder($requester, $recipe, $materials, 300);
            self::fail('La creation aurait du echouer faute de Gils.');
        } catch (\InvalidArgumentException $e) {
            self::assertStringContainsString('Fonds insuffisants', $e->getMessage());
        }

        foreach ($materials as $material) {
            self::assertNotNull($material->getInventory(), 'Aucun materiau ne doit avoir quitte l\'inventaire.');
        }
        self::assertSame(10, $requester->getGils());
    }

    public function testCreateOrderRefusesMaterialsFromAnotherPlayer(): void
    {
        $requester = $this->createPlayer(1, 1_000);
        $stranger = $this->createPlayer(2, 0);
        $materials = $this->createMaterials($stranger, ['ore-iron', 'ore-iron']);
        $recipe = $this->createRecipe([['slug' => 'ore-iron', 'quantity' => 2]]);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('ne provient pas de votre inventaire');

        $this->manager->createOrder($requester, $recipe, $materials, 300);
    }

    public function testCancelReturnsBothMaterialsAndCommission(): void
    {
        $requester = $this->createPlayer(1, 1_000);
        $materials = $this->createMaterials($requester, ['ore-iron', 'ore-iron']);
        $recipe = $this->createRecipe([['slug' => 'ore-iron', 'quantity' => 2]]);
        $order = $this->manager->createOrder($requester, $recipe, $materials, 300);

        $this->manager->cancelOrder($requester, $order);

        self::assertSame(CraftOrderStatus::Cancelled, $order->getStatus());
        self::assertSame(1_000, $requester->getGils(), 'La commission revient integralement.');
        foreach ($materials as $material) {
            self::assertNotNull($material->getInventory());
            self::assertNull($material->getCraftOrder());
        }
    }

    /**
     * Une fois un artisan engage, annuler unilateralement lui ferait perdre le
     * travail deja fourni — le temps de craft est reel.
     */
    public function testCancelIsRefusedOnceAnArtisanHasClaimedTheOrder(): void
    {
        $requester = $this->createPlayer(1, 1_000);
        $materials = $this->createMaterials($requester, ['ore-iron', 'ore-iron']);
        $recipe = $this->createRecipe([['slug' => 'ore-iron', 'quantity' => 2]]);
        $order = $this->manager->createOrder($requester, $recipe, $materials, 300);
        $order->setStatus(CraftOrderStatus::Claimed);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('deja pris cette commande');

        $this->manager->cancelOrder($requester, $order);
    }

    public function testCancelIsRefusedForSomeoneElsesOrder(): void
    {
        $requester = $this->createPlayer(1, 1_000);
        $materials = $this->createMaterials($requester, ['ore-iron', 'ore-iron']);
        $recipe = $this->createRecipe([['slug' => 'ore-iron', 'quantity' => 2]]);
        $order = $this->manager->createOrder($requester, $recipe, $materials, 300);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('vos propres commandes');

        $this->manager->cancelOrder($this->createPlayer(2, 0), $order);
    }

    /**
     * Le plafond n'est pas cosmetique : chaque commande immobilise des materiaux
     * et des Gils, et sans limite un joueur pourrait assecher le marche.
     */
    public function testActiveOrderCapIsEnforced(): void
    {
        $orderRepository = $this->createMock(CraftOrderRepository::class);
        $orderRepository->method('countActiveByRequester')->willReturn(CraftOrderManager::MAX_ACTIVE_ORDERS);
        $manager = new CraftOrderManager($this->em, $orderRepository, new PlayerRegionResolver(), new NullLogger());

        $requester = $this->createPlayer(1, 1_000);
        $materials = $this->createMaterials($requester, ['ore-iron', 'ore-iron']);
        $recipe = $this->createRecipe([['slug' => 'ore-iron', 'quantity' => 2]]);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('commandes en cours');

        $manager->createOrder($requester, $recipe, $materials, 300);
    }

    public function testCommissionMustBePositive(): void
    {
        $requester = $this->createPlayer(1, 1_000);
        $materials = $this->createMaterials($requester, ['ore-iron', 'ore-iron']);
        $recipe = $this->createRecipe([['slug' => 'ore-iron', 'quantity' => 2]]);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('commission');

        $this->manager->createOrder($requester, $recipe, $materials, 0);
    }

    /**
     * @param list<array{slug: string, quantity: int}> $ingredients
     */
    private function createRecipe(array $ingredients): Recipe
    {
        $recipe = new Recipe();
        $recipe->setName('Test');
        $recipe->setSlug('recipe-test');
        $recipe->setCraft('forgeron');
        $recipe->setIngredients($ingredients);

        return $recipe;
    }

    /**
     * @param list<string> $slugs
     *
     * @return list<PlayerItem>
     */
    private function createMaterials(Player $owner, array $slugs): array
    {
        $bag = $owner->getInventories()->first();
        self::assertInstanceOf(Inventory::class, $bag);

        $materials = [];
        foreach ($slugs as $slug) {
            $item = new Item();
            $item->setName($slug);
            $item->setSlug($slug);
            $item->setType(Item::TYPE_RESOURCE);

            $playerItem = new PlayerItem();
            $playerItem->setGenericItem($item);
            $playerItem->setInventory($bag);
            $materials[] = $playerItem;
        }

        return $materials;
    }

    private function createPlayer(int $id, int $gils): Player
    {
        $player = new Player();
        (new \ReflectionProperty(Player::class, 'id'))->setValue($player, $id);
        $player->setGils($gils);

        $bag = new Inventory();
        $bag->setType(Inventory::TYPE_BAG);
        $bag->setSize(20);
        $bag->setPlayer($player);

        (new \ReflectionProperty(Player::class, 'inventories'))->setValue($player, new ArrayCollection([$bag]));

        return $player;
    }
}
