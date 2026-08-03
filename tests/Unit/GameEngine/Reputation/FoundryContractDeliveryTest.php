<?php

namespace App\Tests\Unit\GameEngine\Reputation;

use App\Entity\App\FoundryContract;
use App\Entity\App\FoundryContractFulfillment;
use App\Entity\App\Inventory;
use App\Entity\App\Player;
use App\Entity\App\PlayerFaction;
use App\Entity\App\PlayerItem;
use App\Entity\App\Zone;
use App\Entity\Game\Faction;
use App\Entity\Game\Item;
use App\GameEngine\Reputation\FoundryContractCatalog;
use App\GameEngine\Reputation\FoundryContractException;
use App\GameEngine\Reputation\FoundryContractManager;
use App\GameEngine\Reputation\HostileConsequenceResolver;
use App\GameEngine\Reputation\ReputationManager;
use App\Helper\InventoryHelper;
use App\Helper\PlayerHelper;
use App\Repository\AuctionTransactionRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Honorer le contrat — qui peut, ou, et contre quoi (FAC-05).
 *
 * Le refus n'est jamais muet : chaque blocage rend une cle (palier Ami,
 * rancune de la maison, deja honore, mauvais guichet, volume manquant). La
 * livraison paie en mixte — gils + essence — et ne paie jamais en partie.
 */
class FoundryContractDeliveryTest extends TestCase
{
    private EntityManagerInterface&MockObject $entityManager;
    private ReputationManager&MockObject $reputationManager;
    private HostileConsequenceResolver&MockObject $hostileConsequences;
    private InventoryHelper&MockObject $inventoryHelper;
    private PlayerHelper&MockObject $playerHelper;
    private FoundryContract $contract;
    private Faction $fonderie;
    private ?FoundryContractFulfillment $fulfillment = null;
    /** @var list<object> */
    private array $persisted = [];
    private int $bagCount = 0;

    protected function setUp(): void
    {
        $this->contract = (new FoundryContract())->setWeekKey('2026-W31')->setItemSlug('ore-iron')
            ->setVolume(40)->setGilsPerUnit(6)->setEssence(5)->setReferencePrice(10);
        $this->fonderie = (new Faction())->setSlug('fonderie')->setName('La Fonderie');
        $this->fulfillment = null;
        $this->persisted = [];
        $this->bagCount = 0;

        $contractRepository = $this->createMock(EntityRepository::class);
        $contractRepository->method('findOneBy')->willReturnCallback(fn (): FoundryContract => $this->contract);

        $fulfillmentRepository = $this->createMock(EntityRepository::class);
        $fulfillmentRepository->method('findOneBy')->willReturnCallback(fn (): ?FoundryContractFulfillment => $this->fulfillment);

        $factionRepository = $this->createMock(EntityRepository::class);
        $factionRepository->method('findOneBy')->willReturnCallback(fn (): Faction => $this->fonderie);

        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->entityManager->method('getRepository')->willReturnCallback(
            fn (string $class): EntityRepository => match ($class) {
                FoundryContract::class => $contractRepository,
                FoundryContractFulfillment::class => $fulfillmentRepository,
                Faction::class => $factionRepository,
                default => $this->createMock(EntityRepository::class),
            },
        );
        $this->entityManager->method('persist')->willReturnCallback(function (object $entity): void {
            $this->persisted[] = $entity;
        });

        $this->reputationManager = $this->createMock(ReputationManager::class);
        $this->hostileConsequences = $this->createMock(HostileConsequenceResolver::class);
        $this->inventoryHelper = $this->createMock(InventoryHelper::class);

        $this->playerHelper = $this->createMock(PlayerHelper::class);
        $bag = $this->createMock(Inventory::class);
        $bag->method('getItems')->willReturnCallback(function (): ArrayCollection {
            $items = [];
            for ($i = 0; $i < $this->bagCount; ++$i) {
                $generic = $this->createMock(Item::class);
                $generic->method('getSlug')->willReturn('ore-iron');
                $item = $this->createMock(PlayerItem::class);
                $item->method('getGenericItem')->willReturn($generic);
                $items[] = $item;
            }

            return new ArrayCollection($items);
        });
        $this->playerHelper->method('getBagInventory')->willReturn($bag);
    }

    private function manager(): FoundryContractManager
    {
        return new FoundryContractManager(
            $this->entityManager,
            new FoundryContractCatalog(\dirname(__DIR__, 4)),
            $this->reputationManager,
            $this->hostileConsequences,
            $this->createMock(AuctionTransactionRepository::class),
            $this->inventoryHelper,
            $this->playerHelper,
        );
    }

    private function eligiblePlayerAtTheMines(int $reputation = 2000): Player
    {
        $player = new Player();
        $player->setCurrentZone((new Zone())->setSlug('mines-profondes')->setName('Mines Profondes'));

        $line = new PlayerFaction();
        $line->setPlayer($player);
        $line->setFaction($this->fonderie);
        $line->setReputation($reputation);
        $this->reputationManager->method('getPlayerFaction')->willReturn($line);

        return $player;
    }

    public function testAFullDeliveryPaysGilsAndEssence(): void
    {
        $player = $this->eligiblePlayerAtTheMines();
        $this->bagCount = 40;
        $this->inventoryHelper->method('removeItemBySlug')->with('ore-iron', 40)->willReturn(40);

        $result = $this->manager()->deliver($player);

        self::assertSame(['gils' => 240, 'essence' => 5], $result, 'Le paiement est mixte : volume x prix unitaire, plus l\'essence.');
        self::assertSame(240, $player->getGils());
        self::assertSame(5, $player->getEssence());
        self::assertCount(1, $this->persisted);
        self::assertInstanceOf(FoundryContractFulfillment::class, $this->persisted[0]);
    }

    /**
     * « Ami — contrats d'approvisionnement » (GAME_WORLD § 12.2) : en deca,
     * l'affiche se lit mais ne se signe pas.
     */
    public function testBelowFriendlyTheContractIsClosed(): void
    {
        $player = $this->eligiblePlayerAtTheMines(reputation: 1999);
        $this->bagCount = 40;

        self::assertSame('game.foundry.contract.error.tier', $this->manager()->blocker($player, $this->contract));
    }

    public function testAHostileOfTheHouseIsRefused(): void
    {
        $player = $this->eligiblePlayerAtTheMines();
        $this->bagCount = 40;
        $this->hostileConsequences->method('isHostileToward')->with(self::anything(), 'fonderie')->willReturn(true);

        self::assertSame('game.foundry.contract.error.hostile', $this->manager()->blocker($player, $this->contract));
    }

    public function testAContractIsHonouredOncePerWeek(): void
    {
        $player = $this->eligiblePlayerAtTheMines();
        $this->bagCount = 40;
        $this->fulfillment = new FoundryContractFulfillment();

        self::assertSame('game.foundry.contract.error.delivered', $this->manager()->blocker($player, $this->contract));
    }

    /**
     * Le contrat se remet au comptoir des Mines — comme la commission se
     * livre au foyer : il faut y aller.
     */
    public function testTheContractIsHandedInAtTheMines(): void
    {
        $player = $this->eligiblePlayerAtTheMines();
        $player->setCurrentZone((new Zone())->setSlug('village-de-lumiere')->setName('Le Fanal'));
        $this->bagCount = 40;

        self::assertSame('game.foundry.contract.error.elsewhere', $this->manager()->blocker($player, $this->contract));
    }

    public function testAShortBagBlocksBeforeAnythingIsConsumed(): void
    {
        $player = $this->eligiblePlayerAtTheMines();
        $this->bagCount = 39;

        $this->inventoryHelper->expects($this->never())->method('removeItemBySlug');
        $this->expectException(FoundryContractException::class);

        $this->manager()->deliver($player);
    }

    /**
     * La course entre deux requetes : le compte passait, le retrait rend
     * moins. On ne paie jamais une livraison partielle.
     */
    public function testAPartialRemovalIsNeverPaid(): void
    {
        $player = $this->eligiblePlayerAtTheMines();
        $this->bagCount = 40;
        $this->inventoryHelper->method('removeItemBySlug')->willReturn(12);

        $this->expectException(FoundryContractException::class);

        try {
            $this->manager()->deliver($player);
        } finally {
            self::assertSame(0, $player->getGils(), 'Une livraison partielle ne paie rien.');
            self::assertSame(0, $player->getEssence());
        }
    }
}
