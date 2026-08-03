<?php

namespace App\Tests\Unit\GameEngine\Reputation;

use App\Entity\App\Player;
use App\Entity\App\PlayerFaction;
use App\Entity\App\PlayerWeeklyFenceSale;
use App\Entity\App\Pnj;
use App\Entity\Game\Faction;
use App\Entity\Game\Item;
use App\GameEngine\Reputation\ShadowsMarket;
use App\GameEngine\Reputation\ShadowsMarketCatalog;
use App\GameEngine\World\GameTimeService;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Le receleur, et ses trois garde-fous en action (FAC-06).
 *
 * Le prix du marche gris est le prix d'item moins la coupe de la Confrerie ;
 * il ne s'offre qu'au guichet d'un agent, aux heures de sa couverture, a un
 * Ami, sous le plafond de lots — et son refus ne ferme jamais la vente : le
 * repli est le rachat commun.
 */
class ShadowsMarketTest extends TestCase
{
    private GameTimeService&MockObject $gameTimeService;
    private ?PlayerFaction $line = null;
    private ?PlayerWeeklyFenceSale $weekRow = null;
    private Faction $ombres;
    /** @var list<object> */
    private array $persisted = [];

    protected function setUp(): void
    {
        $this->gameTimeService = $this->createMock(GameTimeService::class);
        $this->gameTimeService->method('getHour')->willReturn(23);
        $this->ombres = (new Faction())->setSlug('ombres')->setName('Confrérie des Ruelles');
        $this->line = null;
        $this->weekRow = null;
        $this->persisted = [];
    }

    private function market(): ShadowsMarket
    {
        $factionRepository = $this->createMock(EntityRepository::class);
        $factionRepository->method('findOneBy')->willReturnCallback(fn (): ?Faction => $this->ombres);

        $lineRepository = $this->createMock(EntityRepository::class);
        $lineRepository->method('findOneBy')->willReturnCallback(fn (): ?PlayerFaction => $this->line);

        $weekRepository = $this->createMock(EntityRepository::class);
        $weekRepository->method('findOneBy')->willReturnCallback(fn (): ?PlayerWeeklyFenceSale => $this->weekRow);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->method('getRepository')->willReturnCallback(
            fn (string $class): EntityRepository => match ($class) {
                Faction::class => $factionRepository,
                PlayerFaction::class => $lineRepository,
                default => $weekRepository,
            },
        );
        $entityManager->method('persist')->willReturnCallback(function (object $entity): void {
            $this->persisted[] = $entity;
            if ($entity instanceof PlayerWeeklyFenceSale) {
                $this->weekRow = $entity;
            }
        });

        return new ShadowsMarket(
            $entityManager,
            new ShadowsMarketCatalog(\dirname(__DIR__, 4)),
            $this->gameTimeService,
        );
    }

    private function friendPlayer(): Player
    {
        $player = new Player();
        $this->line = (new PlayerFaction())->setPlayer($player)->setFaction($this->ombres);
        $this->line->setReputation(2000);

        return $player;
    }

    private function counter(): Pnj
    {
        $pnj = $this->createMock(Pnj::class);
        $pnj->method('getSlug')->willReturn('village-veilleur-tancrede');
        $pnj->method('isShopOpen')->willReturn(true);

        return $pnj;
    }

    private function item(int $price = 100): Item
    {
        $item = $this->createMock(Item::class);
        $item->method('getPrice')->willReturn($price);

        return $item;
    }

    public function testAFriendSellsAtTheFencePriceAtNight(): void
    {
        $market = $this->market();
        $player = $this->friendPlayer();

        $price = $market->fencePriceFor($this->counter(), $this->item(100), $player, true);

        self::assertSame(85, $price, 'Le receleur paie le prix moins la coupe (15 %) — hors taxe, mieux que le rachat commun, sous le HV.');
    }

    public function testTheFenceNeedsAFriend(): void
    {
        $market = $this->market();
        $player = new Player();
        $this->line = (new PlayerFaction())->setPlayer($player)->setFaction($this->ombres);
        $this->line->setReputation(1999);

        self::assertNull($market->fencePriceFor($this->counter(), $this->item(), $player, true), 'La Confrerie ne travaille pas avec des inconnus : Ami requis.');
    }

    public function testTheWeeklyLotCapCloses(): void
    {
        $market = $this->market();
        $player = $this->friendPlayer();

        $this->weekRow = new PlayerWeeklyFenceSale();
        for ($i = 0; $i < (new ShadowsMarketCatalog(\dirname(__DIR__, 4)))->weeklyLotCap(); ++$i) {
            $this->weekRow->incrementLots();
        }

        self::assertNull($market->fencePriceFor($this->counter(), $this->item(), $player, true), 'La Confrerie n\'aime pas les gros volumes : au plafond, le guichet se ferme pour la semaine.');
    }

    public function testAClosedCoverIsNoFence(): void
    {
        $market = $this->market();
        $player = $this->friendPlayer();

        $pnj = $this->createMock(Pnj::class);
        $pnj->method('getSlug')->willReturn('village-veilleur-tancrede');
        $pnj->method('isShopOpen')->willReturn(false);

        self::assertNull($market->fencePriceFor($pnj, $this->item(), $player, true), 'Pas de receleur en plein jour : le guichet suit la couverture.');
    }

    public function testAnOrdinaryMerchantIsNoFence(): void
    {
        $market = $this->market();
        $player = $this->friendPlayer();

        $pnj = $this->createMock(Pnj::class);
        $pnj->method('getSlug')->willReturn('mines-cantiniere-brida');
        $pnj->method('isShopOpen')->willReturn(true);

        self::assertNull($market->fencePriceFor($pnj, $this->item(), $player, true));
        self::assertNull($market->fencePriceFor(null, $this->item(), $player, true));
    }

    public function testABoundItemNeverPassesUnderTheCounter(): void
    {
        $market = $this->market();
        $player = $this->friendPlayer();

        self::assertNull($market->fencePriceFor($this->counter(), $this->item(), $player, false), 'Le receleur vole le systeme, jamais la regle de liaison.');
    }

    public function testRecordingLotsCountsTheWeek(): void
    {
        $market = $this->market();
        $player = $this->friendPlayer();

        $market->recordFenceSale($player);
        $market->recordFenceSale($player);

        self::assertSame(2, $market->lotsThisWeek($player));
    }
}
