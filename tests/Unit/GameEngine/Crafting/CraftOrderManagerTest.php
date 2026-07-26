<?php

namespace App\Tests\Unit\GameEngine\Crafting;

use App\Entity\App\CraftOrder;
use App\Entity\App\Guild;
use App\Entity\App\Inventory;
use App\Entity\App\Player;
use App\Entity\App\PlayerItem;
use App\Entity\App\Region;
use App\Entity\Game\Item;
use App\Entity\Game\Recipe;
use App\Entity\User;
use App\Enum\CraftOrderStatus;
use App\GameEngine\Auction\AuctionAntiExploit;
use App\GameEngine\Crafting\CraftingManager;
use App\GameEngine\Crafting\CraftOrderManager;
use App\GameEngine\Generator\PlayerItemGenerator;
use App\GameEngine\Guild\GuildManager;
use App\GameEngine\Guild\TownControlManager;
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
    private CraftingManager&MockObject $craftingManager;
    private AuctionAntiExploit&MockObject $antiExploit;
    private TownControlManager&MockObject $townControl;
    private GuildManager&MockObject $guildManager;
    private PlayerItemGenerator&MockObject $itemGenerator;
    private CraftOrderManager $manager;

    /** @var list<object> objets passes a EntityManager::remove() */
    private array $removed = [];

    /** @var list<object> objets passes a EntityManager::persist() */
    private array $persisted = [];

    protected function setUp(): void
    {
        $this->removed = [];
        $this->persisted = [];

        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->em->method('remove')->willReturnCallback(function (object $entity): void {
            $this->removed[] = $entity;
        });
        $this->em->method('persist')->willReturnCallback(function (object $entity): void {
            $this->persisted[] = $entity;
        });

        $this->orderRepository = $this->createMock(CraftOrderRepository::class);
        $this->orderRepository->method('countActiveByRequester')->willReturn(0);
        $this->craftingManager = $this->createMock(CraftingManager::class);
        $this->craftingManager->method('getCraftingLevel')->willReturn(99);
        $this->antiExploit = $this->createMock(AuctionAntiExploit::class);
        $this->townControl = $this->createMock(TownControlManager::class);
        $this->guildManager = $this->createMock(GuildManager::class);

        // Le generateur irait chercher l'Item en base : ce qui compte ici est
        // l'inventaire d'arrivee, pas la fabrication du PlayerItem.
        $this->itemGenerator = $this->createMock(PlayerItemGenerator::class);
        $this->itemGenerator->method('generateFromItemId')->willReturnCallback(function (): PlayerItem {
            $item = new Item();
            $item->setName('Epee de fer');
            $item->setSlug('iron_sword');
            $item->setType(Item::TYPE_GEAR_PIECE);

            $playerItem = new PlayerItem();
            $playerItem->setGenericItem($item);

            return $playerItem;
        });

        $this->manager = new CraftOrderManager(
            $this->em,
            $this->orderRepository,
            new PlayerRegionResolver(),
            $this->craftingManager,
            $this->antiExploit,
            $this->townControl,
            $this->guildManager,
            $this->itemGenerator,
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
        $manager = new CraftOrderManager($this->em, $orderRepository, new PlayerRegionResolver(), $this->craftingManager, $this->antiExploit, $this->townControl, $this->guildManager, $this->itemGenerator, new NullLogger());

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
     * ECO-06 : l'artisan doit savoir faire. Le controle reprend exactement les
     * regles de l'ecran d'artisanat — pouvoir prendre une commande qu'on ne
     * saurait pas realiser a son etabli n'aurait aucun sens.
     */
    public function testClaimIsRefusedWhenTheCraftingLevelIsTooLow(): void
    {
        $craftingManager = $this->createMock(CraftingManager::class);
        $craftingManager->method('getCraftingLevel')->willReturn(2);
        $manager = new CraftOrderManager($this->em, $this->orderRepository, new PlayerRegionResolver(), $craftingManager, $this->antiExploit, $this->townControl, $this->guildManager, $this->itemGenerator, new NullLogger());

        $order = $this->openOrder($this->createPlayer(1, 1_000), 5);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Niveau de forgeron insuffisant');

        $manager->claimOrder($this->createPlayer(2, 0), $order);
    }

    public function testClaimSucceedsAndReservesTheOrder(): void
    {
        $order = $this->openOrder($this->createPlayer(1, 1_000));
        $crafter = $this->createPlayer(2, 0);

        $this->manager->claimOrder($crafter, $order);

        self::assertSame(CraftOrderStatus::Claimed, $order->getStatus());
        self::assertSame($crafter, $order->getCrafter());
        self::assertNotNull($order->getClaimedAt());
    }

    /**
     * Le verrou anti-double-prise : une commande deja prise n'est plus prenable.
     */
    public function testAnAlreadyClaimedOrderCannotBeClaimedAgain(): void
    {
        $order = $this->openOrder($this->createPlayer(1, 1_000));
        $this->manager->claimOrder($this->createPlayer(2, 0), $order);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('deja ete prise en charge');

        $this->manager->claimOrder($this->createPlayer(3, 0), $order);
    }

    public function testClaimingOnesOwnOrderIsRefused(): void
    {
        $requester = $this->createPlayer(1, 1_000);
        $order = $this->openOrder($requester);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('votre propre commande');

        $this->manager->claimOrder($requester, $order);
    }

    /**
     * ECO-16a : se commander a soi-meme du stuff lie contournerait tout
     * l'interet du canal.
     */
    public function testClaimIsRefusedBetweenTwoCharactersOfTheSameAccount(): void
    {
        $this->antiExploit->method('isSameAccount')->willReturn(true);

        $order = $this->openOrder($this->createPlayer(1, 1_000, 42));

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('un autre de vos personnages');

        $this->manager->claimOrder($this->createPlayer(2, 0, 42), $order);
    }

    /**
     * ECO-16b : la suspension ferme les canaux d'echange, celui-ci compris.
     */
    public function testASuspendedCrafterCannotClaim(): void
    {
        $order = $this->openOrder($this->createPlayer(1, 1_000));
        $crafter = $this->createPlayer(2, 0);
        $crafter->setTradeSuspendedUntil(new \DateTimeImmutable('+2 days'));

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('acces au marche est suspendu');

        $this->manager->claimOrder($crafter, $order);
    }

    public function testAnExpiredOrderCannotBeClaimed(): void
    {
        $order = $this->openOrder($this->createPlayer(1, 1_000));
        $order->setExpiresAt(new \DateTimeImmutable('-1 hour'));

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('expire');

        $this->manager->claimOrder($this->createPlayer(2, 0), $order);
    }

    // ---------------------------------------------------------------------
    // ECO-07 — execution, time-gating et repartition de la commission
    // ---------------------------------------------------------------------

    /**
     * Le `craftingTime` de la recette n'etait applique nulle part avant ECO-07 :
     * il etait affiche au joueur (« Temps : 5s ») et le craft restait instantane.
     * Sur ce canal, l'attente est ce qui distingue une commande d'un achat.
     */
    public function testClaimingStartsTheWorkClockFromTheRecipeCraftingTime(): void
    {
        $order = $this->openOrder($this->createPlayer(1, 1_000));
        $order->getRecipe()->setCraftingTime(120);

        $this->manager->claimOrder($this->createPlayer(2, 0), $order);

        self::assertNotNull($order->getReadyAt());
        self::assertFalse($order->isReady(), 'Le travail vient de commencer.');
        self::assertGreaterThan(100, $order->getRemainingWorkSeconds());
    }

    public function testAnOrderCannotBeDeliveredBeforeTheWorkIsDone(): void
    {
        $crafter = $this->createPlayer(2, 0);
        $order = $this->claimedOrder($this->createPlayer(1, 1_000), $crafter, craftingTime: 3_600);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('travail n\'est pas termine');

        $this->manager->fulfillOrder($crafter, $order);
    }

    public function testOnlyTheCrafterWhoClaimedCanDeliver(): void
    {
        $order = $this->claimedOrder($this->createPlayer(1, 1_000), $this->createPlayer(2, 0));

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('un autre artisan');

        $this->manager->fulfillOrder($this->createPlayer(3, 0), $order);
    }

    public function testAnOrderNobodyClaimedCannotBeDelivered(): void
    {
        $order = $this->openOrder($this->createPlayer(1, 1_000));

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('pas en cours de realisation');

        $this->manager->fulfillOrder($this->createPlayer(2, 0), $order);
    }

    /**
     * Le cas nominal, et les trois mouvements qui doivent avoir lieu ensemble :
     * l'escrow est **consomme**, le resultat va au commanditaire, la commission
     * va a l'artisan.
     */
    public function testDeliveryConsumesEscrowPaysTheCrafterAndGivesTheItemToTheRequester(): void
    {
        $requester = $this->createPlayer(1, 1_000);
        $crafter = $this->createPlayer(2, 50);
        $order = $this->claimedOrder($requester, $crafter);
        $materials = $order->getMaterials()->toArray();

        $settlement = $this->manager->fulfillOrder($crafter, $order);

        self::assertSame(CraftOrderStatus::Fulfilled, $order->getStatus());
        self::assertNotNull($order->getFulfilledAt());

        // Sans region, pas de taxe : l'artisan touche la commission entiere.
        self::assertSame(300, $settlement->sellerRevenue);
        self::assertSame(350, $crafter->getGils());
        self::assertSame(700, $requester->getGils(), 'Le commanditaire avait deja paye au depot.');

        foreach ($materials as $material) {
            self::assertContains($material, $this->removed, 'Les materiaux en escrow sont transformes, donc detruits.');
        }

        $delivered = array_filter($this->persisted, static fn (object $e) => $e instanceof PlayerItem);
        self::assertCount(1, $delivered);
        $item = array_values($delivered)[0];
        self::assertInstanceOf(PlayerItem::class, $item);
        self::assertSame(
            $requester->getInventories()->first(),
            $item->getInventory(),
            'L\'objet ne transite jamais par le sac de l\'artisan.'
        );
    }

    public function testTheRegionTaxOnTheCommissionGoesToTheControllingGuild(): void
    {
        $requester = $this->createPlayer(1, 1_000);
        $crafter = $this->createPlayer(2, 0);
        $order = $this->claimedOrder($requester, $crafter);

        $order->setRegion($this->createRegion());

        $guild = new Guild();
        $guild->setName('Les Forgerons');
        $this->townControl->method('getControllingGuild')->willReturn($guild);
        $this->guildManager->method('getPlayerGuild')->willReturn(null);

        $treasuryBefore = $guild->getGilsTreasury();

        $settlement = $this->manager->fulfillOrder($crafter, $order);

        self::assertSame(30, $settlement->taxAmount);
        self::assertSame(270, $crafter->getGils(), 'L\'artisan touche la commission moins la taxe.');
        self::assertSame($treasuryBefore + 30, $guild->getGilsTreasury());
        self::assertSame(0, $settlement->burnedAmount);
    }

    /**
     * Le gold sink du canal : sans guilde pour la recevoir, la taxe **sort du
     * jeu**. Elle ne revient ni a l'artisan ni au commanditaire — sinon la
     * commande deviendrait le canal ou l'on echappe a la taxe de l'hotel des
     * ventes.
     */
    public function testTheRegionTaxIsBurnedWhenNoGuildControlsTheRegion(): void
    {
        $requester = $this->createPlayer(1, 1_000);
        $crafter = $this->createPlayer(2, 0);
        $order = $this->claimedOrder($requester, $crafter);

        $order->setRegion($this->createRegion());

        $this->townControl->method('getControllingGuild')->willReturn(null);

        $settlement = $this->manager->fulfillOrder($crafter, $order);

        self::assertSame(30, $settlement->burnedAmount);
        self::assertSame(0, $settlement->treasuryAmount);
        self::assertSame(270, $crafter->getGils());
        self::assertSame(700, $requester->getGils(), 'La taxe brulee ne revient pas au commanditaire.');
    }

    /**
     * Une commande prise en charge avant ECO-07 n'a pas d'echeance de travail.
     * La bloquer indefiniment punirait un artisan pour une migration.
     */
    public function testAnOrderWithoutAWorkClockIsDeliverable(): void
    {
        $crafter = $this->createPlayer(2, 0);
        $order = $this->claimedOrder($this->createPlayer(1, 1_000), $crafter);
        $order->setReadyAt(null);

        self::assertTrue($order->isReady());
        $this->manager->fulfillOrder($crafter, $order);

        self::assertSame(CraftOrderStatus::Fulfilled, $order->getStatus());
    }

    /**
     * Le journal du chemin « taxe brulee » lit le slug de la region : une
     * region de test sans slug ferait echouer le test sur une donnee absente
     * plutot que sur la regle testee.
     */
    private function createRegion(string $taxRate = '0.1000'): Region
    {
        $region = new Region();
        $region->setName('Plaines');
        $region->setSlug('plaines');
        $region->setTaxRate($taxRate);

        return $region;
    }

    /**
     * Commande ouverte puis prise en charge, travail deja termine.
     */
    private function claimedOrder(Player $requester, Player $crafter, int $craftingTime = 5): CraftOrder
    {
        $order = $this->openOrder($requester);
        $order->getRecipe()->setCraftingTime($craftingTime);

        $result = new Item();
        $result->setName('Epee de fer');
        $result->setSlug('iron_sword');
        $result->setType(Item::TYPE_GEAR_PIECE);
        (new \ReflectionProperty(Item::class, 'id'))->setValue($result, 4_242);
        $order->getRecipe()->setResult($result);

        $this->manager->claimOrder($crafter, $order);

        if (5 === $craftingTime) {
            // Le temps d'atelier est reel ; on le rembobine plutot que d'attendre.
            $order->setReadyAt(new \DateTimeImmutable('-1 minute'));
        }

        return $order;
    }

    private function openOrder(Player $requester, int $requiredLevel = 1): CraftOrder
    {
        $materials = $this->createMaterials($requester, ['ore-iron', 'ore-iron']);
        $recipe = $this->createRecipe([['slug' => 'ore-iron', 'quantity' => 2]]);
        $recipe->setRequiredLevel($requiredLevel);

        return $this->manager->createOrder($requester, $recipe, $materials, 300);
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

    private function createPlayer(int $id, int $gils, ?int $userId = null): Player
    {
        $player = new Player();
        (new \ReflectionProperty(Player::class, 'id'))->setValue($player, $id);
        $player->setGils($gils);

        $user = new User();
        (new \ReflectionProperty(User::class, 'id'))->setValue($user, $userId ?? $id);
        $player->setUser($user);

        $bag = new Inventory();
        $bag->setType(Inventory::TYPE_BAG);
        $bag->setSize(20);
        $bag->setPlayer($player);

        (new \ReflectionProperty(Player::class, 'inventories'))->setValue($player, new ArrayCollection([$bag]));

        return $player;
    }
}
