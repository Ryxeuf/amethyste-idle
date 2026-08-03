<?php

namespace App\Tests\Unit\GameEngine\Reputation;

use App\Entity\App\FoundryContract;
use App\Entity\Game\Item;
use App\GameEngine\Reputation\FoundryContractCatalog;
use App\GameEngine\Reputation\FoundryContractManager;
use App\GameEngine\Reputation\HostileConsequenceResolver;
use App\GameEngine\Reputation\ReputationManager;
use App\GameEngine\Retention\WeekKey;
use App\Helper\InventoryHelper;
use App\Helper\PlayerHelper;
use App\Repository\AuctionTransactionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Le tirage du contrat de la Fonderie (FAC-05).
 *
 * Trois proprietes : le tirage est deterministe et suit la cle de semaine de
 * RET-01 ; rejouer la rotation n'est jamais un reroll ; et le garde-fou de
 * prix se verifie **au tirage** — le prix unitaire est ramene strictement
 * sous la reference du marche, mediane HV ou prix d'item quand le marche est
 * muet, et la reference est figee sur la ligne.
 */
class FoundryContractRotationTest extends TestCase
{
    private const MONDAY = '2026-07-27 00:08:00';

    private EntityManagerInterface&MockObject $entityManager;
    private AuctionTransactionRepository&MockObject $transactionRepository;
    private FoundryContractCatalog $catalog;
    /** @var list<object> */
    private array $persisted = [];
    private ?FoundryContract $existing = null;
    private ?Item $item = null;

    protected function setUp(): void
    {
        $this->catalog = new FoundryContractCatalog(\dirname(__DIR__, 4));
        $this->transactionRepository = $this->createMock(AuctionTransactionRepository::class);
        $this->persisted = [];
        $this->existing = null;
        $this->item = null;

        $contractRepository = $this->createMock(EntityRepository::class);
        $contractRepository->method('findOneBy')->willReturnCallback(fn (): ?FoundryContract => $this->existing);

        $itemRepository = $this->createMock(EntityRepository::class);
        $itemRepository->method('findOneBy')->willReturnCallback(fn (): ?Item => $this->item);

        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->entityManager->method('getRepository')->willReturnCallback(
            fn (string $class): EntityRepository => $class === FoundryContract::class ? $contractRepository : $itemRepository,
        );
        $this->entityManager->method('persist')->willReturnCallback(function (object $entity): void {
            $this->persisted[] = $entity;
        });
    }

    private function manager(): FoundryContractManager
    {
        return new FoundryContractManager(
            $this->entityManager,
            $this->catalog,
            $this->createMock(ReputationManager::class),
            $this->createMock(HostileConsequenceResolver::class),
            $this->transactionRepository,
            $this->createMock(InventoryHelper::class),
            $this->createMock(PlayerHelper::class),
        );
    }

    /**
     * @return array{item: string, volume: int, gils_per_unit: int, essence: int}
     */
    private function expectedTemplate(string $weekKey): array
    {
        $pool = $this->catalog->contracts();

        return $pool[abs(crc32($weekKey)) % \count($pool)];
    }

    public function testTheDrawIsDeterministicAndFollowsTheWeekKey(): void
    {
        $now = new \DateTimeImmutable(self::MONDAY);
        $expected = $this->expectedTemplate(WeekKey::of($now));
        $this->transactionRepository->method('medianUnitPriceForSlug')->willReturn(1000);

        $contract = $this->manager()->rotate($now);

        self::assertSame(WeekKey::of($now), $contract->getWeekKey(), 'La cle de semaine est celle de RET-01 — le point de rotation unique.');
        self::assertSame($expected['item'], $contract->getItemSlug());
        self::assertSame($expected['volume'], $contract->getVolume());
        self::assertSame($expected['essence'], $contract->getEssence());
        self::assertCount(1, $this->persisted);
    }

    /**
     * Rejouer la rotation n'est jamais un reroll : la ligne existante est la
     * verite, rien de neuf n'est ecrit.
     */
    public function testReplayingTheRotationIsNeverAReroll(): void
    {
        $this->existing = (new FoundryContract())->setWeekKey('2026-W31')->setItemSlug('ore-iron')
            ->setVolume(40)->setGilsPerUnit(6)->setEssence(5)->setReferencePrice(10);

        $contract = $this->manager()->rotate(new \DateTimeImmutable(self::MONDAY));

        self::assertSame($this->existing, $contract);
        self::assertSame([], $this->persisted, 'Une rotation rejouee ne doit rien ecrire.');
    }

    /**
     * Le garde-fou du plan : prix contractuel < mediane HV, verifie au
     * tirage. Une mediane basse rogne le prix declare ; la reference est
     * figee sur la ligne, verifiable apres coup.
     */
    public function testThePriceIsClampedStrictlyUnderTheMarketMedian(): void
    {
        $now = new \DateTimeImmutable(self::MONDAY);
        $expected = $this->expectedTemplate(WeekKey::of($now));
        $this->transactionRepository->method('medianUnitPriceForSlug')
            ->with($expected['item'], self::anything())
            ->willReturn(2);

        $contract = $this->manager()->rotate($now);

        self::assertSame(1, $contract->getGilsPerUnit(), 'Le prix doit passer strictement sous la mediane.');
        self::assertSame(2, $contract->getReferencePrice(), 'La reference lue au tirage est figee sur la ligne.');
        self::assertLessThan($contract->getReferencePrice(), $contract->getGilsPerUnit());
    }

    /**
     * Marche muet : la reference de repli est le prix d'item — le meme repli
     * assume que le plancher du cristal, une donnee plutot qu'un hasard.
     */
    public function testASilentMarketFallsBackToTheItemPrice(): void
    {
        $now = new \DateTimeImmutable(self::MONDAY);
        $this->transactionRepository->method('medianUnitPriceForSlug')->willReturn(null);

        $this->item = $this->createMock(Item::class);
        $this->item->method('getPrice')->willReturn(3);

        $contract = $this->manager()->rotate($now);

        self::assertSame(3, $contract->getReferencePrice());
        self::assertLessThan(3, $contract->getGilsPerUnit(), 'Meme sur un marche muet, le contrat paie sous la reference.');
    }
}
