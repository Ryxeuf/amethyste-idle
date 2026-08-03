<?php

namespace App\Tests\Unit\GameEngine\Reputation;

use App\Entity\App\Player;
use App\Entity\App\PlayerFaction;
use App\Entity\App\Pnj;
use App\Entity\App\Settlement;
use App\Entity\App\SmugglingContract;
use App\Entity\App\Zone;
use App\Entity\Game\Faction;
use App\Enum\SettlementType;
use App\GameEngine\Reputation\ReputationManager;
use App\GameEngine\Reputation\ShadowsApproach;
use App\GameEngine\Reputation\ShadowsMarketCatalog;
use App\GameEngine\Reputation\ShadowsMarketException;
use App\GameEngine\Reputation\ShadowsSmuggling;
use App\GameEngine\World\GameTimeService;
use App\Repository\SettlementRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * La contrebande des Ruelles (FAC-08) : un ballot d'un guichet a l'autre, de
 * nuit — et la fouille aux portes d'un Bastion qui confisque LE CONTRAT,
 * jamais l'inventaire. Se faire prendre decote les Chevaliers, immediatement
 * et fortement.
 */
class ShadowsSmugglingTest extends TestCase
{
    private EntityManagerInterface&MockObject $entityManager;
    private GameTimeService&MockObject $gameTimeService;
    private SettlementRepository&MockObject $settlementRepository;
    private ReputationManager&MockObject $reputationManager;
    private ShadowsApproach&MockObject $approach;

    private ?SmugglingContract $activeContract = null;

    /** @var list<SmugglingContract> */
    private array $weekContracts = [];

    private ?PlayerFaction $shadowsLine = null;
    private ?Faction $chevaliers = null;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->gameTimeService = $this->createMock(GameTimeService::class);
        $this->gameTimeService->method('getPhase')->willReturn(GameTimeService::PHASE_NIGHT);
        $this->settlementRepository = $this->createMock(SettlementRepository::class);
        $this->reputationManager = $this->createMock(ReputationManager::class);
        $this->approach = $this->createMock(ShadowsApproach::class);
        $this->approach->method('hasMet')->willReturn(true);
        $this->chevaliers = (new Faction())->setSlug('chevaliers')->setName('Ordre des Chevaliers');
    }

    private function service(int $fixedRoll = 1): ShadowsSmuggling
    {
        $shadows = (new Faction())->setSlug('ombres')->setName('Confrérie des Ruelles');

        $factionRepository = $this->createMock(EntityRepository::class);
        $factionRepository->method('findOneBy')->willReturnCallback(
            fn (array $criteria): ?Faction => match ($criteria['slug'] ?? null) {
                'ombres' => $shadows,
                'chevaliers' => $this->chevaliers,
                default => null,
            },
        );

        $contractRepository = $this->createMock(EntityRepository::class);
        $contractRepository->method('findOneBy')->willReturnCallback(fn (): ?SmugglingContract => $this->activeContract);
        $contractRepository->method('findBy')->willReturnCallback(fn (): array => $this->weekContracts);

        $lineRepository = $this->createMock(EntityRepository::class);
        $lineRepository->method('findOneBy')->willReturnCallback(fn (): ?PlayerFaction => $this->shadowsLine);

        $pnjRepository = $this->createMock(EntityRepository::class);
        $pnjRepository->method('findOneBy')->willReturnCallback(
            function (array $criteria): ?Pnj {
                $zone = (new Zone())->setSlug('mines-profondes')->setName('Mines profondes');

                return $this->buildCounter((string) $criteria['slug'], $zone);
            },
        );

        $this->entityManager->method('getRepository')->willReturnCallback(
            fn (string $class): EntityRepository => match ($class) {
                Faction::class => $factionRepository,
                SmugglingContract::class => $contractRepository,
                PlayerFaction::class => $lineRepository,
                Pnj::class => $pnjRepository,
                default => $this->createMock(EntityRepository::class),
            },
        );

        return new class($this->entityManager, new ShadowsMarketCatalog(\dirname(__DIR__, 4)), $this->approach, $this->gameTimeService, $this->settlementRepository, $this->reputationManager, $fixedRoll) extends ShadowsSmuggling {
            public function __construct(
                EntityManagerInterface $em,
                ShadowsMarketCatalog $catalog,
                ShadowsApproach $approach,
                GameTimeService $time,
                SettlementRepository $settlements,
                ReputationManager $reputation,
                private readonly int $fixedRoll,
            ) {
                parent::__construct($em, $catalog, $approach, $time, $settlements, $reputation);
            }

            protected function roll(int $max): int
            {
                return $this->fixedRoll;
            }
        };
    }

    private function buildCounter(string $slug, Zone $zone): Pnj
    {
        $pnj = $this->createMock(Pnj::class);
        $pnj->method('getSlug')->willReturn($slug);
        $pnj->method('getZone')->willReturn($zone);

        return $pnj;
    }

    private function friendOfTheAlleys(Player $player): void
    {
        $line = new PlayerFaction();
        $line->setPlayer($player);
        $line->setFaction((new Faction())->setSlug('ombres')->setName('ombres'));
        $line->setReputation(2000);
        $this->shadowsLine = $line;
    }

    public function testAcceptingABundleTargetsTheOtherCounter(): void
    {
        $player = new Player();
        $this->friendOfTheAlleys($player);
        $village = (new Zone())->setSlug('village-de-lumiere')->setName('Le Fanal');
        $tancrede = $this->buildCounter('village-veilleur-tancrede', $village);

        $contract = $this->service()->accept($player, $tancrede);

        self::assertSame('village-de-lumiere', $contract->getOriginZoneSlug());
        self::assertSame('mines-profondes', $contract->getDestinationZoneSlug(), 'La contrebande relie les deux guichets de la Confrerie.');
        self::assertTrue($contract->isInTransit());
        self::assertGreaterThan(0, $contract->getRewardGils());
        self::assertNotSame('', $contract->getCargoLabel());
    }

    public function testDaylightRefusesTheBundle(): void
    {
        $gameTime = $this->createMock(GameTimeService::class);
        $gameTime->method('getPhase')->willReturn(GameTimeService::PHASE_DAY);
        $this->gameTimeService = $gameTime;

        $player = new Player();
        $this->friendOfTheAlleys($player);
        $tancrede = $this->buildCounter('village-veilleur-tancrede', (new Zone())->setSlug('village-de-lumiere'));

        try {
            $this->service()->accept($player, $tancrede);
            self::fail('Une cargaison de nuit ne part pas en plein jour.');
        } catch (ShadowsMarketException $e) {
            self::assertSame('game.shadows.smuggling.error.daylight', $e->getMessage());
        }
    }

    public function testAStrangerGetsTheNeutralRefusal(): void
    {
        $player = new Player();
        $tancrede = $this->buildCounter('village-veilleur-tancrede', (new Zone())->setSlug('village-de-lumiere'));

        self::assertSame('game.shadows.smuggling.error.tier', $this->service()->acceptBlocker($player, $tancrede), 'Rencontre faite mais pas Ami : la Confrerie ne confie rien.');
    }

    public function testOneBundleAtATime(): void
    {
        $player = new Player();
        $this->friendOfTheAlleys($player);
        $this->activeContract = (new SmugglingContract())->setStatus(SmugglingContract::STATUS_IN_TRANSIT);
        $tancrede = $this->buildCounter('village-veilleur-tancrede', (new Zone())->setSlug('village-de-lumiere'));

        self::assertSame('game.shadows.smuggling.error.active', $this->service()->acceptBlocker($player, $tancrede), 'Moins de capacite : un seul ballot, c\'est la definition du canal.');
    }

    public function testTheWeeklyCapCloses(): void
    {
        $player = new Player();
        $this->friendOfTheAlleys($player);
        $this->weekContracts = [new SmugglingContract(), new SmugglingContract(), new SmugglingContract()];
        $tancrede = $this->buildCounter('village-veilleur-tancrede', (new Zone())->setSlug('village-de-lumiere'));

        self::assertSame('game.shadows.smuggling.error.cap', $this->service()->acceptBlocker($player, $tancrede));
    }

    public function testDeliveryPaysTheFrozenReward(): void
    {
        $mines = (new Zone())->setSlug('mines-profondes')->setName('Mines profondes');
        $player = new Player();
        $player->setGils(0);
        $player->setCurrentZone($mines);
        $this->friendOfTheAlleys($player);

        $contract = (new SmugglingContract())
            ->setPlayer($player)
            ->setDestinationZoneSlug('mines-profondes')
            ->setRewardGils(120)
            ->setCargoLabel('un ballot');
        $this->activeContract = $contract;

        $kolm = $this->buildCounter('mines-porteur-de-lanterne-kolm', $mines);

        $gils = $this->service()->deliver($player, $kolm);

        self::assertSame(120, $gils);
        self::assertSame(120, $player->getGils());
        self::assertSame(SmugglingContract::STATUS_DELIVERED, $contract->getStatus());
    }

    public function testDeliveryRefusesTheWrongCounter(): void
    {
        $village = (new Zone())->setSlug('village-de-lumiere')->setName('Le Fanal');
        $player = new Player();
        $player->setCurrentZone($village);
        $this->friendOfTheAlleys($player);
        $this->activeContract = (new SmugglingContract())
            ->setDestinationZoneSlug('mines-profondes')
            ->setCargoLabel('un ballot');

        $tancrede = $this->buildCounter('village-veilleur-tancrede', $village);

        self::assertSame('game.shadows.smuggling.error.elsewhere', $this->service()->deliverBlocker($player, $tancrede), 'Le ballot se livre a l\'autre guichet, pas a celui du depart.');
    }

    /**
     * La fouille aux portes : Bastion + ballot + tirage sous la chance = la
     * cargaison du contrat est confisquee, les Chevaliers decotent — et
     * l'inventaire n'est jamais touche (aucun objet n'existe : la cargaison
     * vit dans le contrat, la borne du canon tient par construction).
     */
    public function testTheGateSearchConfiscatesTheContractOnly(): void
    {
        $player = new Player();
        $contract = (new SmugglingContract())->setCargoLabel('un ballot');
        $this->activeContract = $contract;

        $bastion = (new Zone())->setSlug('foret-des-murmures')->setName('Foret');
        $settlement = $this->createMock(Settlement::class);
        $settlement->method('getType')->willReturn(SettlementType::Bastion);
        $this->settlementRepository->method('findOneByZone')->willReturn($settlement);

        $this->reputationManager->expects(self::once())
            ->method('addReputation')
            ->with($player, $this->chevaliers, -200);

        $confiscated = $this->service(1)->inspectAtGates($player, $bastion);

        self::assertSame($contract, $confiscated);
        self::assertSame(SmugglingContract::STATUS_CONFISCATED, $contract->getStatus());
    }

    public function testAQuietGateLetsTheBundleThrough(): void
    {
        $player = new Player();
        $this->activeContract = (new SmugglingContract())->setCargoLabel('un ballot');

        $bastion = (new Zone())->setSlug('foret-des-murmures');
        $settlement = $this->createMock(Settlement::class);
        $settlement->method('getType')->willReturn(SettlementType::Bastion);
        $this->settlementRepository->method('findOneByZone')->willReturn($settlement);

        $this->reputationManager->expects(self::never())->method('addReputation');

        self::assertNull($this->service(99)->inspectAtGates($player, $bastion), 'Un tirage au-dessus de la chance passe sans encombre.');
        self::assertTrue($this->activeContract->isInTransit());
    }

    /**
     * Sans foyer Bastion, aucune fouille : le crochet est inerte tant que les
     * joueurs n'ont pas sedimenté la Guerre quelque part — et un foyer
     * marchand ne fouille pas pour l'Ordre.
     */
    public function testOnlyABastionSearches(): void
    {
        $player = new Player();
        $this->activeContract = (new SmugglingContract())->setCargoLabel('un ballot');

        $trading = $this->createMock(Settlement::class);
        $trading->method('getType')->willReturn(SettlementType::Trading);
        $this->settlementRepository->method('findOneByZone')->willReturn($trading);

        self::assertNull($this->service(1)->inspectAtGates($player, (new Zone())->setSlug('foret-des-murmures')));
        self::assertTrue($this->activeContract->isInTransit());
    }

    public function testNoContractMeansNoSearch(): void
    {
        $this->reputationManager->expects(self::never())->method('addReputation');

        self::assertNull($this->service(1)->inspectAtGates(new Player(), (new Zone())->setSlug('foret-des-murmures')));
    }
}
