<?php

namespace App\Tests\Unit\GameEngine\Crafting;

use App\Entity\App\CrafterReputation;
use App\Entity\App\CraftOrder;
use App\Entity\App\Guild;
use App\Entity\App\Inventory;
use App\Entity\App\Player;
use App\Entity\App\PlayerItem;
use App\Entity\App\Region;
use App\Entity\Game\Item;
use App\Entity\Game\Recipe;
use App\Entity\User;
use App\Enum\BindType;
use App\Enum\CraftOrderStatus;
use App\Enum\Purity;
use App\GameEngine\Auction\AuctionAntiExploit;
use App\GameEngine\Crafting\CrafterReputationManager;
use App\GameEngine\Crafting\CraftingManager;
use App\GameEngine\Crafting\CraftOrderManager;
use App\GameEngine\Crafting\QualityCalculator;
use App\GameEngine\Economy\PurityChain;
use App\GameEngine\GameMaster\GameMasterPolicy;
use App\GameEngine\Generator\PlayerItemGenerator;
use App\GameEngine\Guild\GuildManager;
use App\GameEngine\Guild\TownControlManager;
use App\GameEngine\Region\PlayerRegionResolver;
use App\Repository\CrafterReputationRepository;
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
    private CrafterReputationRepository&MockObject $reputationRepository;
    private CrafterReputationManager $reputationManager;
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
        // ECO-20 : le gardien « plan appris » a sa propre suite de tests ; ici
        // on veut isoler les regles du canal des commandes.
        $this->craftingManager->method('isRecipeUnlocked')->willReturn(true);
        // Tirage neutralise : la regle testee est le seuil, pas le hasard.
        $this->craftingManager->method('computeQuality')->willReturn(QualityCalculator::QUALITY_RARE);
        $this->antiExploit = $this->createMock(AuctionAntiExploit::class);
        $this->reputationRepository = $this->createMock(CrafterReputationRepository::class);
        // Le vrai manager : la regle de points est ce qu'on veut verifier.
        $this->reputationManager = new CrafterReputationManager($this->em, $this->reputationRepository);
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
            $this->reputationManager,
            $this->townControl,
            $this->guildManager,
            $this->itemGenerator,
            new NullLogger(),
            $this->purityChain(),
            new GameMasterPolicy(),
        );
    }

    /**
     * ECO-26 : ces tests portent sur l'escrow et la reputation, pas sur la
     * purete. Des lots sans bande rendent `null`, ce qui est l'etat normal de
     * l'immense majorite des commandes.
     */
    private function purityChain(): PurityChain
    {
        $chain = $this->createMock(PurityChain::class);
        $chain->method('weakestOf')->willReturn(null);

        return $chain;
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
     * ECO-23 : exiger une bande donne au prospecteur un **client**, pas seulement
     * un marche. Sans cette exigence, la bande n'aurait de valeur qu'a la revente
     * et le savoir du prospecteur resterait speculatif.
     */
    public function testAnOrderCanDemandAMinimumPurityBand(): void
    {
        $requester = $this->createPlayer(1, 1_000);
        $materials = $this->createMaterials($requester, ['ore-iron', 'ore-iron']);
        foreach ($materials as $material) {
            $material->setPurity(Purity::Pur);
        }
        $recipe = $this->createRecipe([['slug' => 'ore-iron', 'quantity' => 2]]);

        $order = $this->manager->createOrder($requester, $recipe, $materials, 300, minPurity: Purity::Clair);

        self::assertSame(Purity::Clair, $order->getMinPurity());
    }

    /**
     * Le refus arrive **avant** l'escrow, quand il ne coute encore rien : le
     * verifier plus tard laisserait un client immobiliser sa matiere et sa
     * commission dans une commande qu'aucun artisan ne pourrait honorer.
     */
    public function testMaterialUnderTheDemandedBandIsRefusedBeforeAnythingIsLocked(): void
    {
        $requester = $this->createPlayer(1, 1_000);
        $materials = $this->createMaterials($requester, ['ore-iron', 'ore-iron']);
        $materials[0]->setPurity(Purity::Parfait);
        $materials[1]->setPurity(Purity::Trouble);
        $recipe = $this->createRecipe([['slug' => 'ore-iron', 'quantity' => 2]]);

        try {
            $this->manager->createOrder($requester, $recipe, $materials, 300, minPurity: Purity::Pur);
            self::fail('Une matiere sous la bande demandee doit etre refusee.');
        } catch (\InvalidArgumentException $e) {
            self::assertStringContainsString('pur', $e->getMessage());
        }

        self::assertSame(1_000, $requester->getGils(), 'La commission ne doit pas avoir quitte la bourse.');
        foreach ($materials as $material) {
            self::assertNotNull($material->getInventory(), 'Le materiau doit rester dans l\'inventaire.');
        }
    }

    /**
     * Une matiere **hors perimetre** n'a pas de bande, donc ne peut satisfaire
     * aucune exigence. La laisser passer offrirait un contournement a qui
     * fournirait des herbes.
     */
    public function testAMaterialWithoutABandCannotSatisfyADemand(): void
    {
        $requester = $this->createPlayer(1, 1_000);
        $materials = $this->createMaterials($requester, ['herb-sage', 'herb-sage']);
        $recipe = $this->createRecipe([['slug' => 'herb-sage', 'quantity' => 2]]);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/sans bande/');

        $this->manager->createOrder($requester, $recipe, $materials, 300, minPurity: Purity::Clair);
    }

    /**
     * Le defaut reste **aucune exigence** : la plupart des commandes ne
     * demandent rien de particulier, et une exigence implicite fermerait le
     * plancher T1 aux debutants.
     */
    public function testWithoutADemandAnyMaterialPasses(): void
    {
        $requester = $this->createPlayer(1, 1_000);
        $materials = $this->createMaterials($requester, ['herb-sage', 'herb-sage']);
        $recipe = $this->createRecipe([['slug' => 'herb-sage', 'quantity' => 2]]);

        $order = $this->manager->createOrder($requester, $recipe, $materials, 300);

        self::assertNull($order->getMinPurity());
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
        $manager = new CraftOrderManager($this->em, $orderRepository, new PlayerRegionResolver(), $this->craftingManager, $this->antiExploit, $this->reputationManager, $this->townControl, $this->guildManager, $this->itemGenerator, new NullLogger(), $this->purityChain(), new GameMasterPolicy());

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
        $manager = new CraftOrderManager($this->em, $this->orderRepository, new PlayerRegionResolver(), $craftingManager, $this->antiExploit, $this->reputationManager, $this->townControl, $this->guildManager, $this->itemGenerator, new NullLogger(), $this->purityChain(), new GameMasterPolicy());

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

    // ---------------------------------------------------------------------
    // ECO-07b — commande directe
    // ---------------------------------------------------------------------

    public function testADirectOrderIsReservedToItsTargetCrafter(): void
    {
        $requester = $this->createPlayer(1, 1_000);
        $target = $this->createPlayer(2, 0);
        $order = $this->openOrder($requester, targetCrafter: $target);

        self::assertTrue($order->isDirect());
        self::assertSame($target, $order->getTargetCrafter());

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('adressee a un artisan en particulier');

        $this->manager->claimOrder($this->createPlayer(3, 0), $order);
    }

    public function testTheTargetCrafterCanClaimTheOrderAddressedToThem(): void
    {
        $target = $this->createPlayer(2, 0);
        $order = $this->openOrder($this->createPlayer(1, 1_000), targetCrafter: $target);

        $this->manager->claimOrder($target, $order);

        self::assertSame(CraftOrderStatus::Claimed, $order->getStatus());
        self::assertSame($target, $order->getCrafter());
    }

    /**
     * Les refus de la prise en charge s'appliquent **au depot**. Sans cela, le
     * commanditaire immobiliserait son escrow pour une commande que l'artisan
     * vise ne pourra jamais prendre, jusqu'a l'expiration.
     */
    public function testAnOrderCannotBeAddressedToOneself(): void
    {
        $requester = $this->createPlayer(1, 1_000);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('a vous-meme');

        $this->openOrder($requester, targetCrafter: $requester);
    }

    public function testAnOrderCannotBeAddressedToAnotherCharacterOfTheSameAccount(): void
    {
        $this->antiExploit->method('isSameAccount')->willReturn(true);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('un autre de vos personnages');

        $this->openOrder($this->createPlayer(1, 1_000), targetCrafter: $this->createPlayer(2, 0, 42));
    }

    public function testAnOrderCannotBeAddressedToASuspendedCrafter(): void
    {
        $target = $this->createPlayer(2, 0);
        $target->setTradeSuspendedUntil(new \DateTimeImmutable('+2 days'));

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('ne peut pas recevoir de commande');

        $this->openOrder($this->createPlayer(1, 1_000), targetCrafter: $target);
    }

    /**
     * Un depot refuse ne doit rien couter : ni materiaux, ni Gils.
     */
    public function testARefusedDirectOrderLeavesTheEscrowUntouched(): void
    {
        $requester = $this->createPlayer(1, 1_000);
        $materials = $this->createMaterials($requester, ['ore-iron', 'ore-iron']);
        $recipe = $this->createRecipe([['slug' => 'ore-iron', 'quantity' => 2]]);

        try {
            $this->manager->createOrder($requester, $recipe, $materials, 300, targetCrafter: $requester);
            self::fail('Le depot aurait du echouer.');
        } catch (\InvalidArgumentException) {
            // attendu
        }

        self::assertSame(1_000, $requester->getGils(), 'La commission n\'a pas ete prelevee.');
        foreach ($materials as $material) {
            self::assertNotNull($material->getInventory());
        }
    }

    // ---------------------------------------------------------------------
    // ECO-08a — bind-on-pickup via commande
    // ---------------------------------------------------------------------

    /**
     * Le coeur de la Piste C : l'objet nait lie **au commanditaire**, pas a
     * l'artisan qui l'a fabrique. C'est ce qui fait de la commande le seul canal
     * par lequel on obtient du stuff qu'on ne pourrait jamais acheter.
     */
    public function testABoundResultIsBoundToTheRequesterNotTheCrafter(): void
    {
        $requester = $this->createPlayer(1, 1_000);
        $crafter = $this->createPlayer(2, 0);
        $order = $this->claimedOrder($requester, $crafter, bindType: BindType::BindOnPickup);

        $this->manager->fulfillOrder($crafter, $order);

        $delivered = $this->deliveredItem();
        self::assertTrue($delivered->isBound());
        self::assertSame($requester->getId(), $delivered->getBoundToPlayerId());
        self::assertNotSame($crafter->getId(), $delivered->getBoundToPlayerId());
        self::assertFalse($delivered->isExchangeable(), 'Un objet lie ne repart pas sur un canal d\'echange.');
    }

    /**
     * La liaison ne s'applique qu'aux objets qui la portent : le reste de la
     * production reste echangeable, sinon la commande assecherait l'hotel des
     * ventes au lieu de le completer.
     */
    public function testAnUnboundResultStaysExchangeable(): void
    {
        $crafter = $this->createPlayer(2, 0);
        $order = $this->claimedOrder($this->createPlayer(1, 1_000), $crafter);

        $this->manager->fulfillOrder($crafter, $order);

        $delivered = $this->deliveredItem();
        self::assertFalse($delivered->isBound());
        self::assertTrue($delivered->isExchangeable());
    }

    private function deliveredItem(): PlayerItem
    {
        $items = array_values(array_filter($this->persisted, static fn (object $e) => $e instanceof PlayerItem));
        self::assertCount(1, $items);
        self::assertInstanceOf(PlayerItem::class, $items[0]);

        return $items[0];
    }

    // ---------------------------------------------------------------------
    // ECO-08b — reputation d'artisan
    // ---------------------------------------------------------------------

    /**
     * L'objet part chez le client et n'y revient jamais : ce que l'artisan
     * capitalise, c'est sa reputation (GAME_PRINCIPLES §4.5).
     */
    public function testDeliveringAnOrderBuildsTheCraftersReputation(): void
    {
        $crafter = $this->createPlayer(2, 0);
        $order = $this->claimedOrder($this->createPlayer(1, 1_000), $crafter);
        $order->getRecipe()->setRequiredLevel(5);

        $this->manager->fulfillOrder($crafter, $order);

        $reputation = $this->recordedReputation();
        self::assertSame('forgeron', $reputation->getCraft());
        self::assertSame($crafter, $reputation->getPlayer());
        self::assertSame(1, $reputation->getDeliveries());
        self::assertSame(10, $reputation->getPoints(), '5 * POINTS_PER_RECIPE_LEVEL');
    }

    /**
     * Sans ponderation par palier, la strategie optimale serait d'enchainer les
     * commandes les plus triviales et le classement remonterait les artisans les
     * plus disponibles plutot que les plus competents.
     */
    public function testReputationPointsScaleWithTheRecipeTier(): void
    {
        $novice = $this->createPlayer(2, 0);
        $trivial = $this->claimedOrder($this->createPlayer(1, 1_000), $novice);
        $trivial->getRecipe()->setRequiredLevel(1);
        $this->manager->fulfillOrder($novice, $trivial);
        $trivialPoints = $this->recordedReputation()->getPoints();

        $this->persisted = [];

        $master = $this->createPlayer(4, 0);
        $masterwork = $this->claimedOrder($this->createPlayer(3, 1_000), $master);
        $masterwork->getRecipe()->setRequiredLevel(10);
        $this->manager->fulfillOrder($master, $masterwork);

        self::assertGreaterThan($trivialPoints, $this->recordedReputation()->getPoints());
    }

    /**
     * La reputation deja acquise dans le metier est **completee**, pas recreee :
     * une seconde livraison ne doit pas repartir de zero.
     */
    public function testASecondDeliveryAccumulatesOnTheExistingReputation(): void
    {
        $crafter = $this->createPlayer(2, 0);

        $existing = new CrafterReputation();
        $existing->setPlayer($crafter);
        $existing->setCraft('forgeron');
        $existing->recordDelivery(30);
        $this->reputationRepository->method('findOneForPlayerAndCraft')->willReturn($existing);

        $order = $this->claimedOrder($this->createPlayer(1, 1_000), $crafter);
        $order->getRecipe()->setRequiredLevel(5);

        $this->manager->fulfillOrder($crafter, $order);

        self::assertSame(2, $existing->getDeliveries());
        self::assertSame(40, $existing->getPoints());
        self::assertNotContains($existing, $this->persisted, 'Une reputation existante n\'est pas re-persistee.');
    }

    public function testReputationTitlesFollowThePointLadder(): void
    {
        $reputation = new CrafterReputation();
        self::assertSame('Novice', $reputation->getTitle());

        $reputation->recordDelivery(10);
        self::assertSame('Apprenti', $reputation->getTitle());

        $reputation->recordDelivery(40);
        self::assertSame('Artisan', $reputation->getTitle());

        $reputation->recordDelivery(150);
        self::assertSame('Artisan confirme', $reputation->getTitle());

        $reputation->recordDelivery(300);
        self::assertSame('Maitre', $reputation->getTitle());
    }

    private function recordedReputation(): CrafterReputation
    {
        $found = array_values(array_filter($this->persisted, static fn (object $e) => $e instanceof CrafterReputation));
        self::assertCount(1, $found);
        self::assertInstanceOf(CrafterReputation::class, $found[0]);

        return $found[0];
    }

    // ---------------------------------------------------------------------
    // ECO-09 — expiration, non-livraison et plafonds anti-farm
    // ---------------------------------------------------------------------

    /**
     * Le defaut que ce jalon corrige : `findExpirable()` et `releaseEscrow()`
     * existaient depuis ECO-05 sans que rien ne les appelle, donc une commande
     * que personne ne prenait immobilisait materiaux et Gils indefiniment.
     */
    public function testExpiringAnUntakenOrderReturnsEverythingToTheRequester(): void
    {
        $requester = $this->createPlayer(1, 1_000);
        $order = $this->openOrder($requester);
        $order->setExpiresAt(new \DateTimeImmutable('-1 hour'));
        $materials = $order->getMaterials()->toArray();

        $this->orderRepository->method('findExpirable')->willReturn([$order]);

        $report = $this->manager->expireOrders();

        self::assertSame(['released' => 1, 'penalised' => 0], $report);
        self::assertSame(CraftOrderStatus::Expired, $order->getStatus());
        self::assertSame(1_000, $requester->getGils(), 'La commission revient integralement.');
        foreach ($materials as $material) {
            self::assertNotNull($material->getInventory(), 'Les materiaux retournent au sac.');
            self::assertNull($material->getCraftOrder());
        }
    }

    /**
     * Un artisan qui s'engage puis ne livre pas a bloque le tableau pour les
     * autres. Sans contrepartie, accaparer les commandes serait gratuit et le
     * classement d'ECO-08b deviendrait manipulable par la seule inaction.
     */
    public function testAClaimedOrderThatExpiresCostsTheCrafterReputation(): void
    {
        $requester = $this->createPlayer(1, 1_000);
        $crafter = $this->createPlayer(2, 0);
        $order = $this->claimedOrder($requester, $crafter);
        $order->getRecipe()->setRequiredLevel(5);
        $order->setExpiresAt(new \DateTimeImmutable('-1 hour'));

        $reputation = new CrafterReputation();
        $reputation->setPlayer($crafter);
        $reputation->setCraft('forgeron');
        $reputation->recordDelivery(100);

        $this->reputationRepository->method('findOneForPlayerAndCraft')->willReturn($reputation);
        $this->orderRepository->method('findExpirable')->willReturn([$order]);

        $report = $this->manager->expireOrders();

        self::assertSame(['released' => 1, 'penalised' => 1], $report);
        // 5 * POINTS_PER_RECIPE_LEVEL * FAILURE_MULTIPLIER = 20
        self::assertSame(80, $reputation->getPoints());
        self::assertSame(1, $reputation->getDeliveries(), 'Les livraisons passees ne sont pas effacees.');
        self::assertSame(1_000, $requester->getGils(), 'Le commanditaire n\'a commis aucune faute.');
    }

    public function testReputationNeverGoesBelowZero(): void
    {
        $reputation = new CrafterReputation();
        $reputation->recordDelivery(4);

        $reputation->recordFailure(100);

        self::assertSame(0, $reputation->getPoints());
        self::assertSame('Novice', $reputation->getTitle());
    }

    /**
     * La prise en charge devient l'origine du delai : un artisan qui prend une
     * commande a sa 71e heure aurait sinon une heure pour livrer, et serait
     * sanctionne pour un delai qu'il n'a pas choisi.
     */
    public function testClaimingPushesTheDeadlineToTheDeliveryWindow(): void
    {
        $order = $this->openOrder($this->createPlayer(1, 1_000));
        $order->setExpiresAt(new \DateTimeImmutable('+30 minutes'));

        $this->manager->claimOrder($this->createPlayer(2, 0), $order);

        self::assertGreaterThan(
            new \DateTimeImmutable('+23 hours'),
            $order->getExpiresAt(),
            'L\'echeance devient celle de la livraison, comptee depuis la prise en charge.'
        );
    }

    /**
     * Le plafond mord a la prise en charge et non a la livraison : refuser au
     * dernier moment aurait laisse l'artisan travailler pour rien.
     */
    public function testTheCraftOrderPairCapBlocksTheClaim(): void
    {
        $this->antiExploit->method('isCraftOrderPairCapReached')->willReturn(true);

        $order = $this->openOrder($this->createPlayer(1, 1_000));

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('trop travaille pour ce commanditaire');

        $this->manager->claimOrder($this->createPlayer(2, 0), $order);
    }

    // ---------------------------------------------------------------------
    // ECO-20 — la qualite de craft survit au craft, donc minQuality s'applique
    // ---------------------------------------------------------------------

    /**
     * `QualityCalculator` calculait une qualite depuis toujours, `craft()` la
     * placait dans son message de retour, et **rien ne la conservait**.
     */
    public function testTheDeliveredItemCarriesTheQualityItWasMadeWith(): void
    {
        $crafter = $this->createPlayer(2, 0);
        $order = $this->claimedOrder($this->createPlayer(1, 1_000), $crafter);

        $this->manager->fulfillOrder($crafter, $order);

        self::assertSame(QualityCalculator::QUALITY_RARE, $this->deliveredItem()->getCraftQuality());
    }

    /**
     * Une piece en dessous du seuil est **retravaillee**, pas refusee : refuser
     * piegerait la commande, et l'artisan vend precisement du temps.
     */
    public function testAPieceBelowTheRequestedQualityIsReworkedInsteadOfDelivered(): void
    {
        $requester = $this->createPlayer(1, 1_000);
        $crafter = $this->createPlayer(2, 0);
        $order = $this->claimedOrder($requester, $crafter);
        $order->setMinQuality(QualityCalculator::QUALITY_LEGENDARY);
        $materials = $order->getMaterials()->toArray();

        $settlement = $this->manager->fulfillOrder($crafter, $order);

        self::assertNull($settlement, 'Aucune vente : la commande reste en cours.');
        self::assertSame(CraftOrderStatus::Claimed, $order->getStatus());
        self::assertFalse($order->isReady(), 'Le retravail relance l\'horloge d\'atelier.');
        self::assertSame(0, $crafter->getGils(), 'Rien n\'est encaisse sur une piece non livree.');

        foreach ($materials as $material) {
            self::assertNotContains($material, $this->removed, 'L\'escrow n\'est consomme qu\'a la livraison.');
        }
        self::assertSame([], array_filter($this->persisted, static fn (object $e) => $e instanceof PlayerItem));
    }

    public function testAPieceAtOrAboveTheRequestedQualityIsDelivered(): void
    {
        $crafter = $this->createPlayer(2, 0);
        $order = $this->claimedOrder($this->createPlayer(1, 1_000), $crafter);
        $order->setMinQuality(QualityCalculator::QUALITY_UNCOMMON);

        $settlement = $this->manager->fulfillOrder($crafter, $order);

        self::assertNotNull($settlement);
        self::assertSame(CraftOrderStatus::Fulfilled, $order->getStatus());
    }

    /**
     * Un seuil inconnu de l'echelle est traite comme absent : une donnee erronee
     * ne doit pas rendre une commande impossible a honorer.
     */
    public function testAnUnknownQualityThresholdDoesNotTrapTheOrder(): void
    {
        $crafter = $this->createPlayer(2, 0);
        $order = $this->claimedOrder($this->createPlayer(1, 1_000), $crafter);
        $order->setMinQuality('mythique-inexistant');

        self::assertNotNull($this->manager->fulfillOrder($crafter, $order));
    }

    /**
     * ECO-20 : le « plan possede » existe enfin comme gardien. ECO-06 avait du
     * s'aligner sur le niveau de metier seul, faute de quoi s'appuyer.
     */
    public function testClaimIsRefusedWhenTheCrafterHasNotLearnedTheRecipe(): void
    {
        $craftingManager = $this->createMock(CraftingManager::class);
        $craftingManager->method('getCraftingLevel')->willReturn(99);
        $craftingManager->method('isRecipeUnlocked')->willReturn(false);

        $manager = new CraftOrderManager($this->em, $this->orderRepository, new PlayerRegionResolver(), $craftingManager, $this->antiExploit, $this->reputationManager, $this->townControl, $this->guildManager, $this->itemGenerator, new NullLogger(), $this->purityChain(), new GameMasterPolicy());

        $order = $this->openOrder($this->createPlayer(1, 1_000));

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('pas appris cette recette');

        $manager->claimOrder($this->createPlayer(2, 0), $order);
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
    private function claimedOrder(Player $requester, Player $crafter, int $craftingTime = 5, BindType $bindType = BindType::None): CraftOrder
    {
        $order = $this->openOrder($requester);
        $order->getRecipe()->setCraftingTime($craftingTime);

        $result = new Item();
        $result->setName('Epee de fer');
        $result->setSlug('iron_sword');
        $result->setType(Item::TYPE_GEAR_PIECE);
        $result->setBindType($bindType);
        (new \ReflectionProperty(Item::class, 'id'))->setValue($result, 4_242);
        $order->getRecipe()->setResult($result);

        $this->manager->claimOrder($crafter, $order);

        if (5 === $craftingTime) {
            // Le temps d'atelier est reel ; on le rembobine plutot que d'attendre.
            $order->setReadyAt(new \DateTimeImmutable('-1 minute'));
        }

        return $order;
    }

    private function openOrder(Player $requester, int $requiredLevel = 1, ?Player $targetCrafter = null): CraftOrder
    {
        $materials = $this->createMaterials($requester, ['ore-iron', 'ore-iron']);
        $recipe = $this->createRecipe([['slug' => 'ore-iron', 'quantity' => 2]]);
        $recipe->setRequiredLevel($requiredLevel);

        return $this->manager->createOrder($requester, $recipe, $materials, 300, targetCrafter: $targetCrafter);
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

    // =====================================================================
    // ECO-28 — les commandes de service : travailler un objet lie
    // =====================================================================

    /**
     * ECO-28 : l'escrow d'un service est triple — la piece du client, ses
     * amethystites Pures, sa commission. La piece part par sa propre place
     * (`targetItem`), jamais dans les materiaux : eux se consomment, elle
     * revient toujours. Et elle peut etre **liee** — c'est le canal fait
     * pour ca.
     */
    public function testCreateServiceOrderEscrowsPieceCrystalsAndCommission(): void
    {
        $requester = $this->createPlayer(1, 1_000);
        $piece = $this->createServicePiece($requester, declaredSlots: 2, boundTo: 1);
        $this->createServiceCrystals($requester, 2, Purity::Pur);

        $order = $this->manager->createServiceOrder($requester, $piece, 200);

        self::assertTrue($order->isService());
        self::assertSame(CraftOrder::SERVICE_SOCKET, $order->getServiceKind());
        self::assertSame($piece, $order->getTargetItem());
        self::assertNull($piece->getInventory(), 'La piece quitte le sac : c\'est ce qui rend l\'escrow reel.');
        self::assertSame(1, $piece->getBoundToPlayerId(), 'La liaison n\'est pas touchee par l\'escrow.');
        self::assertCount(2, $order->getMaterials(), 'Les deux amethystites partent en escrow de materiaux.');
        self::assertSame(800, $requester->getGils());
        self::assertSame(Purity::Pur, $order->getMinPurity());
    }

    /**
     * ECO-23 : le refus de bande arrive avant l'escrow. Des amethystites sous
     * « pur » ne conviennent pas, et rien ne doit avoir bouge.
     */
    public function testAServiceRefusesCrystalsUnderTheBandBeforeAnythingIsLocked(): void
    {
        $requester = $this->createPlayer(1, 1_000);
        $piece = $this->createServicePiece($requester, declaredSlots: 2);
        $this->createServiceCrystals($requester, 2, Purity::Clair);

        try {
            $this->manager->createServiceOrder($requester, $piece, 200);
            self::fail('Une bande sous « pur » doit etre refusee.');
        } catch (\InvalidArgumentException $e) {
            self::assertStringContainsString('pur', $e->getMessage());
        }

        self::assertSame(1_000, $requester->getGils(), 'La commission ne doit pas avoir quitte la bourse.');
        self::assertNotNull($piece->getInventory(), 'La piece doit rester dans le sac.');
    }

    public function testAWornPieceCannotBeSentToService(): void
    {
        $requester = $this->createPlayer(1, 1_000);
        $piece = $this->createServicePiece($requester, declaredSlots: 2);
        $piece->setGear(1);
        $this->createServiceCrystals($requester, 2, Purity::Pur);

        $this->expectException(\InvalidArgumentException::class);

        $this->manager->createServiceOrder($requester, $piece, 200);
    }

    public function testAPieceWithAllItsSocketsIsRefused(): void
    {
        $requester = $this->createPlayer(1, 1_000);
        $piece = $this->createServicePiece($requester, declaredSlots: 1, openSlots: 1);
        $this->createServiceCrystals($requester, 2, Purity::Pur);

        $this->expectException(\InvalidArgumentException::class);

        $this->manager->createServiceOrder($requester, $piece, 200);
    }

    /**
     * ECO-28, le cœur : la livraison ouvre un emplacement, consomme les
     * cristaux, et rend la piece **au commanditaire** — liaison intacte.
     * C'est la premiere mecanique du jeu qui cree un `Slot` hors fixtures.
     */
    public function testServiceDeliveryOpensASlotAndReturnsTheBoundPiece(): void
    {
        $requester = $this->createPlayer(1, 1_000);
        $crafter = $this->createPlayer(2, 0);
        $piece = $this->createServicePiece($requester, declaredSlots: 2, boundTo: 1);
        $this->createServiceCrystals($requester, 2, Purity::Pur);

        $order = $this->manager->createServiceOrder($requester, $piece, 200);
        $order->setCrafter($crafter);
        $order->setStatus(CraftOrderStatus::Claimed);
        $order->setReadyAt(new \DateTimeImmutable('-1 minute'));

        $settlement = $this->manager->fulfillOrder($crafter, $order);

        self::assertNotNull($settlement);
        self::assertSame(CraftOrderStatus::Fulfilled, $order->getStatus());
        self::assertCount(1, $piece->getSlots(), 'Le sertissage ouvre un emplacement.');
        self::assertNotNull($piece->getInventory(), 'La piece revient chez son proprietaire.');
        self::assertSame($requester, $piece->getInventory()->getPlayer());
        self::assertSame(1, $piece->getBoundToPlayerId(), 'La liaison n\'est jamais violee : elle appartient toujours au client.');
        self::assertCount(2, $this->removed, 'Les deux amethystites sont consommees.');
        self::assertSame(200, $crafter->getGils(), 'La commission va a l\'artisan (region sans taxe).');
    }

    /**
     * Restitution garantie (memes invariants qu'ECO-09) : l'annulation rend
     * la piece intacte, les cristaux et la commission.
     */
    public function testCancellingAServiceReturnsThePieceIntact(): void
    {
        $requester = $this->createPlayer(1, 1_000);
        $piece = $this->createServicePiece($requester, declaredSlots: 2, boundTo: 1);
        $crystals = $this->createServiceCrystals($requester, 2, Purity::Pur);

        $order = $this->manager->createServiceOrder($requester, $piece, 200);
        $this->manager->cancelOrder($requester, $order);

        self::assertSame(CraftOrderStatus::Cancelled, $order->getStatus());
        self::assertNotNull($piece->getInventory(), 'La piece revient dans le sac.');
        self::assertSame(1, $piece->getBoundToPlayerId());
        self::assertCount(0, $piece->getSlots(), 'Une annulation ne sertit rien : la piece revient intacte.');
        self::assertSame(1_000, $requester->getGils(), 'La commission est rendue.');
        foreach ($crystals as $crystal) {
            self::assertNotNull($crystal->getInventory(), 'Les amethystites sont rendues, pas consommees.');
        }
    }

    /**
     * L'expiration suit le meme chemin de restitution que l'annulation : la
     * piece revient, liaison intacte, quel que soit le chemin de sortie.
     */
    public function testAnExpiredServiceReturnsThePiece(): void
    {
        $requester = $this->createPlayer(1, 1_000);
        $piece = $this->createServicePiece($requester, declaredSlots: 2, boundTo: 1);
        $this->createServiceCrystals($requester, 2, Purity::Pur);

        $order = $this->manager->createServiceOrder($requester, $piece, 200);
        $this->orderRepository->method('findExpirable')->willReturn([$order]);

        $result = $this->manager->expireOrders();

        self::assertSame(1, $result['released']);
        self::assertSame(CraftOrderStatus::Expired, $order->getStatus());
        self::assertNotNull($piece->getInventory());
        self::assertSame(1, $piece->getBoundToPlayerId());
    }

    /**
     * @return \App\Entity\App\PlayerItem une piece d'equipement du sac
     */
    private function createServicePiece(Player $owner, int $declaredSlots, int $openSlots = 0, ?int $boundTo = null): PlayerItem
    {
        $bag = $owner->getInventories()->first();
        self::assertInstanceOf(Inventory::class, $bag);

        $item = new Item();
        $item->setName('Plastron de fer');
        $item->setSlug('iron-chestplate');
        $item->setType(Item::TYPE_GEAR_PIECE);
        $item->setMateriaSlots($declaredSlots);

        $piece = new PlayerItem();
        (new \ReflectionProperty(PlayerItem::class, 'id'))->setValue($piece, 101);
        $piece->setGenericItem($item);
        $piece->setInventory($bag);
        $piece->setGear(0);
        $piece->setBoundToPlayerId($boundTo);
        for ($i = 0; $i < $openSlots; ++$i) {
            $piece->getSlots()->add(new \App\Entity\App\Slot());
        }

        return $piece;
    }

    /**
     * @return list<PlayerItem>
     */
    private function createServiceCrystals(Player $owner, int $count, Purity $band): array
    {
        $crystals = $this->createMaterials($owner, array_fill(0, $count, 'ore-amethyst-crystal'));
        foreach ($crystals as $crystal) {
            $crystal->setPurity($band);
        }

        return $crystals;
    }
}
