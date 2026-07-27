<?php

namespace App\Tests\Unit\GameEngine\Economy;

use App\Entity\App\Player;
use App\Entity\App\PlayerHouse;
use App\Entity\App\PlayerShop;
use App\Entity\App\Zone;
use App\GameEngine\Economy\RentBacklogResetter;
use App\GameEngine\Shop\ShopRentService;
use App\Repository\PlayerHouseRepository;
use App\Repository\PlayerShopRepository;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Remise a zero de l'arriere de loyers (tache 134, jalon F.0).
 *
 * L'arriere n'est pas une dette contractee : c'est l'effet d'une tache qui n'a
 * jamais tourne, faute de consommateur du calendrier. On l'efface avant de
 * brancher le worker, sinon chaque proprietaire se voit prelever une semaine de
 * loyer par jour jusqu'a rattrapage.
 */
class RentBacklogResetterTest extends TestCase
{
    private EntityManagerInterface&MockObject $entityManager;
    private PlayerHouseRepository&MockObject $houseRepository;
    private PlayerShopRepository&MockObject $shopRepository;
    private RentBacklogResetter $resetter;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->houseRepository = $this->createMock(PlayerHouseRepository::class);
        $this->shopRepository = $this->createMock(PlayerShopRepository::class);

        $this->resetter = new RentBacklogResetter(
            $this->entityManager,
            $this->houseRepository,
            $this->shopRepository,
        );
    }

    private function zone(): Zone
    {
        return (new Zone())->setSlug('quartier-des-jardins')->setName('Quartier');
    }

    private function house(string $dueAt): PlayerHouse
    {
        $house = new PlayerHouse();
        $house->setOwner(new Player());
        $house->setZone($this->zone());
        $house->setRentDueAt(new \DateTimeImmutable($dueAt));

        return $house;
    }

    private function shop(string $dueAt): PlayerShop
    {
        $shop = new PlayerShop();
        $shop->setOwner(new Player());
        $shop->setZone($this->zone());
        $shop->setName('La bonne enclume');
        $shop->setRentDueAt(new \DateTimeImmutable($dueAt));

        return $shop;
    }

    /**
     * @param list<PlayerHouse> $houses
     * @param list<PlayerShop>  $shops
     */
    private function given(array $houses, array $shops): void
    {
        $this->houseRepository->method('findWithRentDue')->willReturn($houses);
        $this->shopRepository->method('findWithRentDue')->willReturn($shops);
    }

    /**
     * Une base saine ne demande aucune ecriture.
     */
    public function testAnEmptyBacklogWritesNothing(): void
    {
        $this->given([], []);
        $this->entityManager->expects($this->once())->method('flush');

        $report = $this->resetter->reset(new \DateTimeImmutable('2026-07-27 00:00:00'));

        $this->assertTrue($report->isEmpty());
        $this->assertSame(0, $report->dailyChargesAvoided());
    }

    /**
     * Chaque echeance echue repart de maintenant, pas de la precedente.
     *
     * C'est tout l'objet du jalon : `extendRent()` ajoute sept jours a
     * l'echeance **precedente**, ce qui laisse une echeance passee encore dans
     * le passe. Ici on repart du present, donc une seule passe suffit.
     */
    public function testEveryOverdueDateRestartsFromNow(): void
    {
        $now = new \DateTimeImmutable('2026-07-27 00:00:00');
        $house = $this->house('2026-01-01 00:00:00');
        $shop = $this->shop('2025-12-01 00:00:00');
        $this->given([$house], [$shop]);

        $this->resetter->reset($now);

        $this->assertEquals(
            $now->modify(sprintf('+%d days', PlayerHouse::RENT_PERIOD_DAYS)),
            $house->getRentDueAt(),
        );
        $this->assertEquals(
            $now->modify(sprintf('+%d days', ShopRentService::RENT_PERIOD_DAYS)),
            $shop->getRentDueAt(),
        );
    }

    /**
     * Le rapport chiffre ce qu'on evite.
     *
     * Six mois de retard sur une periode de sept jours font 26 periodes, donc
     * 26 jours de prelevements quotidiens consecutifs si on ne fait rien.
     */
    public function testTheReportCountsTheBurstThatWasAvoided(): void
    {
        $now = new \DateTimeImmutable('2026-07-27 00:00:00');
        $this->given(
            [$this->house('2026-01-24 00:00:00'), $this->house('2026-07-20 00:00:00')],
            [$this->shop('2026-07-13 00:00:00')],
        );

        $report = $this->resetter->inspect($now);

        $this->assertSame(2, $report->houseCount);
        $this->assertSame(1, $report->shopCount);
        // 2026-01-24 -> 2026-07-27 = 184 jours = 26 periodes de 7 jours.
        $this->assertSame(26, $report->worstHousePeriods);
        // 2026-07-13 -> 2026-07-27 = 14 jours = 2 periodes.
        $this->assertSame(2, $report->worstShopPeriods);
        $this->assertSame(26, $report->dailyChargesAvoided());
    }

    /**
     * `inspect()` n'ecrit rien.
     *
     * La commande l'appelle en mode simulation : si elle poussait les
     * echeances, `--dry-run` mentirait.
     */
    public function testInspectNeverWrites(): void
    {
        $now = new \DateTimeImmutable('2026-07-27 00:00:00');
        $house = $this->house('2026-01-01 00:00:00');
        $this->given([$house], []);
        $this->entityManager->expects($this->never())->method('flush');

        $this->resetter->inspect($now);

        $this->assertEquals(new \DateTimeImmutable('2026-01-01 00:00:00'), $house->getRentDueAt());
    }

    /**
     * Une echeance a venir n'est pas touchee.
     *
     * Le depot ne renvoie que ce qui est echu ; ce test fige l'hypothese, pour
     * qu'un elargissement de la requete ne passe pas inapercu.
     */
    public function testAFutureDueDateIsLeftAlone(): void
    {
        $now = new \DateTimeImmutable('2026-07-27 00:00:00');
        $this->given([], []);

        $report = $this->resetter->reset($now);

        $this->assertSame(0, $report->houseCount);
        $this->assertSame(0, $report->shopCount);
    }
}
