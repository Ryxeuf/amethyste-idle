<?php

namespace App\Tests\Unit\GameEngine\Reputation;

use App\Entity\App\Player;
use App\Entity\App\PlayerFaction;
use App\Entity\App\PlayerItem;
use App\Entity\App\Pnj;
use App\Entity\Game\Faction;
use App\Entity\Game\Item;
use App\GameEngine\Reputation\CounterfeitService;
use App\GameEngine\Reputation\ReputationManager;
use App\GameEngine\Reputation\ShadowsApproach;
use App\GameEngine\Reputation\ShadowsMarketCatalog;
use App\GameEngine\Reputation\ShadowsMarketException;
use App\GameEngine\Reputation\ShadowsPlacement;
use App\GameEngine\World\GameTimeService;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Le placement des Ruelles (FAC-08) : ecouler un faux via le contact — bien
 * paye (strictement mieux que le receleur), a ses risques : saisie, amende,
 * et la decote Chevaliers. Dans les deux issues, l'objet quitte le monde des
 * joueurs — le placement est le SEUL debouche d'une contrefacon.
 */
class ShadowsPlacementTest extends TestCase
{
    private EntityManagerInterface&MockObject $entityManager;
    private GameTimeService&MockObject $gameTimeService;
    private CounterfeitService&MockObject $counterfeitService;
    private ReputationManager&MockObject $reputationManager;
    private ShadowsApproach&MockObject $approach;
    private ?PlayerFaction $shadowsLine = null;
    private ?Faction $chevaliers = null;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->gameTimeService = $this->createMock(GameTimeService::class);
        $this->gameTimeService->method('getPhase')->willReturn(GameTimeService::PHASE_NIGHT);
        $this->counterfeitService = $this->createMock(CounterfeitService::class);
        $this->reputationManager = $this->createMock(ReputationManager::class);
        $this->approach = $this->createMock(ShadowsApproach::class);
        $this->approach->method('hasMet')->willReturn(true);
        $this->chevaliers = (new Faction())->setSlug('chevaliers')->setName('Ordre des Chevaliers');
    }

    private function service(int $fixedRoll): ShadowsPlacement
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

        $lineRepository = $this->createMock(EntityRepository::class);
        $lineRepository->method('findOneBy')->willReturnCallback(fn (): ?PlayerFaction => $this->shadowsLine);

        $this->entityManager->method('getRepository')->willReturnCallback(
            fn (string $class): EntityRepository => Faction::class === $class ? $factionRepository : $lineRepository,
        );

        return new class($this->entityManager, new ShadowsMarketCatalog(\dirname(__DIR__, 4)), $this->approach, $this->counterfeitService, $this->gameTimeService, $this->reputationManager, $fixedRoll) extends ShadowsPlacement {
            public function __construct(
                EntityManagerInterface $em,
                ShadowsMarketCatalog $catalog,
                ShadowsApproach $approach,
                CounterfeitService $counterfeits,
                GameTimeService $time,
                ReputationManager $reputation,
                private readonly int $fixedRoll,
            ) {
                parent::__construct($em, $catalog, $approach, $counterfeits, $time, $reputation);
            }

            protected function roll(int $max): int
            {
                return $this->fixedRoll;
            }
        };
    }

    private function counter(): Pnj
    {
        $pnj = $this->createMock(Pnj::class);
        $pnj->method('getSlug')->willReturn('village-veilleur-tancrede');

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

    private function seenCounterfeit(int $price): PlayerItem
    {
        $generic = new Item();
        $generic->setName('Matéria feu I');
        $generic->setSlug('m1-fire-ball');
        $generic->setType(Item::TYPE_MATERIA);
        $generic->setPrice($price);

        $item = new PlayerItem();
        $item->setGenericItem($generic);
        $item->setCounterfeit(true);
        $item->setCounterfeitIdentified(true);
        $this->counterfeitService->method('eyeSees')->willReturn(true);

        return $item;
    }

    /**
     * Ecoule : la prime bat strictement le receleur (120 % contre 85 % du
     * prix), et l'objet quitte le monde.
     */
    public function testAPlacedCounterfeitPaysBetterThanTheFence(): void
    {
        $player = new Player();
        $player->setGils(0);
        $this->friendOfTheAlleys($player);
        $item = $this->seenCounterfeit(100);

        $this->entityManager->expects(self::once())->method('remove')->with($item);

        $result = $this->service(99)->place($player, $this->counter(), $item);

        self::assertFalse($result['caught']);
        self::assertSame(120, $result['gils'], '120 % du prix — le receleur en donne 85.');
        self::assertSame(120, $player->getGils());
    }

    /**
     * Saisi : confiscation, amende (jamais au-dela de la bourse), et la
     * decote Chevaliers — immediate et forte.
     */
    public function testACaughtPlacementCostsFineAndStanding(): void
    {
        $player = new Player();
        $player->setGils(40);
        $this->friendOfTheAlleys($player);
        $item = $this->seenCounterfeit(100);

        $this->entityManager->expects(self::once())->method('remove')->with($item);
        $this->reputationManager->expects(self::once())
            ->method('addReputation')
            ->with($player, $this->chevaliers, -200);

        $result = $this->service(1)->place($player, $this->counter(), $item);

        self::assertTrue($result['caught']);
        self::assertSame(-40, $result['gils'], 'L\'amende prend ce qu\'il y a, jamais plus que la bourse.');
        self::assertSame(0, $player->getGils());
    }

    public function testDaylightRefuses(): void
    {
        $gameTime = $this->createMock(GameTimeService::class);
        $gameTime->method('getPhase')->willReturn(GameTimeService::PHASE_DAY);
        $this->gameTimeService = $gameTime;

        $player = new Player();
        $this->friendOfTheAlleys($player);

        $this->expectException(ShadowsMarketException::class);

        $this->service(99)->place($player, $this->counter(), $this->seenCounterfeit(100));
    }

    /**
     * Une authentique, ou un faux que le joueur ne voit pas : le refus est le
     * meme — il ne revele rien.
     */
    public function testAnUnseenItemIsRefusedQuietly(): void
    {
        $player = new Player();
        $this->friendOfTheAlleys($player);

        $generic = new Item();
        $generic->setName('Matéria eau I');
        $generic->setSlug('m1-heal');
        $generic->setType(Item::TYPE_MATERIA);
        $generic->setPrice(100);
        $item = new PlayerItem();
        $item->setGenericItem($generic);
        $this->counterfeitService->method('eyeSees')->willReturn(false);

        try {
            $this->service(99)->place($player, $this->counter(), $item);
            self::fail('On ne place que ce qu\'on voit.');
        } catch (ShadowsMarketException $e) {
            self::assertSame('game.shadows.placement.error.item', $e->getMessage());
        }
    }

    public function testTheContactIgnoresStrangers(): void
    {
        $player = new Player();

        try {
            $this->service(99)->place($player, $this->counter(), $this->seenCounterfeit(100));
            self::fail('Le contact ne travaille pas avec des inconnus.');
        } catch (ShadowsMarketException $e) {
            self::assertSame('game.shadows.placement.error.tier', $e->getMessage());
        }
    }
}
