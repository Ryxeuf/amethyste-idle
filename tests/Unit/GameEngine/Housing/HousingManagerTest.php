<?php

namespace App\Tests\Unit\GameEngine\Housing;

use App\Entity\App\Player;
use App\Entity\App\PlayerHouse;
use App\Entity\App\Zone;
use App\GameEngine\Housing\HousingManager;
use App\Repository\PlayerHouseRepository;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

/**
 * Tache 129 (HOU-01) — acquisition d'une demeure.
 */
final class HousingManagerTest extends TestCase
{
    private EntityManagerInterface&MockObject $em;
    private PlayerHouseRepository&MockObject $houseRepository;
    private HousingManager $manager;

    /** @var list<object> */
    private array $persisted = [];

    protected function setUp(): void
    {
        $this->persisted = [];

        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->em->method('persist')->willReturnCallback(function (object $entity): void {
            $this->persisted[] = $entity;
        });

        $this->houseRepository = $this->createMock(PlayerHouseRepository::class);
        $this->manager = new HousingManager($this->em, $this->houseRepository, new NullLogger());
    }

    public function testBuyingLandCostsTheLandPriceAndBuildsTheHouse(): void
    {
        $zone = $this->residentialZone();
        $player = $this->playerIn($zone, PlayerHouse::LAND_PRICE + 500);

        $house = $this->manager->buyLand($player, $zone, 'Le Repos du Forgeron');

        self::assertSame('Le Repos du Forgeron', $house->getName());
        self::assertSame($zone, $house->getZone());
        self::assertSame($player, $house->getOwner());
        self::assertSame(500, $player->getGils(), 'Le terrain est un gold sink.');
        self::assertContains($house, $this->persisted);
    }

    /**
     * Rien n'est engage si la bourse ne suit pas : sans ce controle, un joueur
     * se retrouverait avec une demeure impayee ou des Gils preleves sans maison.
     */
    public function testBuyingIsRefusedWithoutEnoughGils(): void
    {
        $zone = $this->residentialZone();
        $player = $this->playerIn($zone, PlayerHouse::LAND_PRICE - 1);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Gils');

        try {
            $this->manager->buyLand($player, $zone, 'Trop cher');
        } finally {
            self::assertSame(PlayerHouse::LAND_PRICE - 1, $player->getGils());
            self::assertSame([], $this->persisted);
        }
    }

    /**
     * La position d'un joueur est sa zone (regle #7) : acheter a distance
     * retirerait au lotissement le seul cout qui ne soit pas monetaire.
     */
    public function testBuyingRequiresBeingInTheZone(): void
    {
        $here = $this->residentialZone();
        $elsewhere = $this->zone('village-de-lumiere');
        $player = $this->playerIn($elsewhere, 999_999);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('sur place');

        $this->manager->buyLand($player, $here, 'A distance');
    }

    public function testANonResidentialZoneSellsNothing(): void
    {
        $wilderness = $this->zone('foret-des-murmures');
        $player = $this->playerIn($wilderness, 999_999);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Aucun terrain');

        $this->manager->buyLand($player, $wilderness, 'En pleine foret');
    }

    public function testASecondHouseIsRefused(): void
    {
        $zone = $this->residentialZone();
        $player = $this->playerIn($zone, 999_999);
        $this->houseRepository->method('findForOwner')->willReturn(new PlayerHouse());

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('deja une demeure');

        $this->manager->buyLand($player, $zone, 'La seconde');
    }

    public function testAnEmptyNameIsRefused(): void
    {
        $zone = $this->residentialZone();
        $player = $this->playerIn($zone, 999_999);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('nom');

        $this->manager->buyLand($player, $zone, '   ');
    }

    public function testRenamingSomeoneElsesHouseIsRefused(): void
    {
        $house = new PlayerHouse();
        $house->setOwner($this->playerIn($this->residentialZone(), 0, 1));

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('n\'est pas la votre');

        $this->manager->rename($this->playerIn($this->residentialZone(), 0, 2), $house, 'Vole');
    }

    // ---------------------------------------------------------------------
    // HOU-04 — entretien
    // ---------------------------------------------------------------------

    public function testBuyingAHouseGrantsAFirstFreePeriod(): void
    {
        $zone = $this->residentialZone();
        $player = $this->playerIn($zone, PlayerHouse::LAND_PRICE);

        $house = $this->manager->buyLand($player, $zone, 'Neuve');

        self::assertFalse($house->isInArrears(), 'On ne fait pas payer un loyer le jour de l\'achat.');
        self::assertGreaterThan(new \DateTimeImmutable('+6 days'), $house->getRentDueAt());
    }

    /**
     * L'echeance est reportee a partir de la **precedente** : payer en retard
     * ne doit pas offrir une periode pleine, sinon attendre serait rentable.
     */
    public function testPayingLateDoesNotGrantAFullPeriod(): void
    {
        $player = $this->playerIn($this->residentialZone(), 1_000);
        $house = new PlayerHouse();
        $house->setOwner($player);
        $overdue = new \DateTimeImmutable('-3 days');
        $house->setRentDueAt($overdue);

        $this->manager->payRent($player, $house);

        self::assertSame(1_000 - PlayerHouse::RENT_AMOUNT, $player->getGils());
        self::assertEquals($overdue->modify('+7 days'), $house->getRentDueAt());
        self::assertFalse($house->isInArrears());
    }

    public function testPayingRentWithoutEnoughGilsIsRefused(): void
    {
        $player = $this->playerIn($this->residentialZone(), PlayerHouse::RENT_AMOUNT - 1);
        $house = new PlayerHouse();
        $house->setOwner($player);
        $house->setRentDueAt(new \DateTimeImmutable('-1 day'));

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Gils pour le loyer');

        $this->manager->payRent($player, $house);
    }

    public function testPayingSomeoneElsesRentIsRefused(): void
    {
        $house = new PlayerHouse();
        $house->setOwner($this->playerIn($this->residentialZone(), 0, 1));
        $house->setRentDueAt(new \DateTimeImmutable('+1 day'));

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('n\'est pas la votre');

        $this->manager->payRent($this->playerIn($this->residentialZone(), 10_000, 2), $house);
    }

    /**
     * Un joueur solvable ne doit pas perdre l'usage de sa demeure pour avoir
     * oublie un bouton : le prelevement est automatique.
     */
    public function testDueRentIsCollectedAutomaticallyFromASolventOwner(): void
    {
        $owner = $this->playerIn($this->residentialZone(), 5_000);
        $house = new PlayerHouse();
        $house->setOwner($owner);
        $house->setRentDueAt(new \DateTimeImmutable('-1 hour'));
        $this->houseRepository->method('findWithRentDue')->willReturn([$house]);

        $report = $this->manager->collectDueRents();

        self::assertSame(['charged' => 1, 'unpaid' => 0], $report);
        self::assertSame(5_000 - PlayerHouse::RENT_AMOUNT, $owner->getGils());
        self::assertFalse($house->isInArrears());
    }

    /**
     * Insolvable : la demeure **dort**, rien n'est confisque ni detruit.
     */
    public function testAnInsolventOwnerKeepsEverythingButFallsIntoArrears(): void
    {
        $owner = $this->playerIn($this->residentialZone(), 10);
        $house = new PlayerHouse();
        $house->setOwner($owner);
        $house->setName('Le Repos');
        $house->setRentDueAt(new \DateTimeImmutable('-1 hour'));
        $this->houseRepository->method('findWithRentDue')->willReturn([$house]);

        $report = $this->manager->collectDueRents();

        self::assertSame(['charged' => 0, 'unpaid' => 1], $report);
        self::assertSame(10, $owner->getGils(), 'On ne preleve pas ce qui n\'existe pas.');
        self::assertSame('Le Repos', $house->getName(), 'La demeure n\'est ni saisie ni renommee.');
        self::assertTrue($house->isInArrears());
    }

    // ---------------------------------------------------------------------
    // HOU-03 — visites
    // ---------------------------------------------------------------------

    /**
     * La position d'un joueur est sa zone (regle #7) : une visite consultable
     * de n'importe ou ferait du voisinage un annuaire, la ou c'est un lieu.
     */
    public function testVisitingRequiresBeingInTheHousesZone(): void
    {
        $quarter = $this->residentialZone();
        $house = new PlayerHouse();
        $house->setOwner($this->playerIn($quarter, 0, 1));
        $house->setZone($quarter);

        $visitor = $this->playerIn($this->zone('village-de-lumiere'), 0, 2);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('son quartier');

        $this->manager->assertCanVisit($visitor, $house);
    }

    public function testANeighbourInTheSameZoneMayVisit(): void
    {
        $quarter = $this->residentialZone();
        $house = new PlayerHouse();
        $house->setOwner($this->playerIn($quarter, 0, 1));
        $house->setZone($quarter);

        $this->expectNotToPerformAssertions();

        $this->manager->assertCanVisit($this->playerIn($quarter, 0, 2), $house);
    }

    /**
     * Visiter la sienne est la meme vue : l'interdire obligerait l'appelant a
     * traiter un cas particulier sans aucun gain.
     */
    public function testOneMayVisitOnesOwnHouse(): void
    {
        $quarter = $this->residentialZone();
        $owner = $this->playerIn($quarter, 0, 1);
        $house = new PlayerHouse();
        $house->setOwner($owner);
        $house->setZone($quarter);

        $this->expectNotToPerformAssertions();

        $this->manager->assertCanVisit($owner, $house);
    }

    private function residentialZone(): Zone
    {
        return $this->zone(HousingManager::RESIDENTIAL_ZONE_SLUGS[0]);
    }

    private function zone(string $slug): Zone
    {
        $zone = new Zone();
        $zone->setSlug($slug);
        $zone->setName($slug);
        (new \ReflectionProperty(Zone::class, 'id'))->setValue($zone, crc32($slug));

        return $zone;
    }

    private function playerIn(Zone $zone, int $gils, int $id = 1): Player
    {
        $player = new Player();
        (new \ReflectionProperty(Player::class, 'id'))->setValue($player, $id);
        $player->setGils($gils);
        $player->setCurrentZone($zone);

        return $player;
    }
}
