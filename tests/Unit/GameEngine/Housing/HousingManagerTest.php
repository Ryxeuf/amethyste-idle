<?php

namespace App\Tests\Unit\GameEngine\Housing;

use App\Entity\App\Player;
use App\Entity\App\PlayerHouse;
use App\Entity\App\Zone;
use App\Enum\HouseStyle;
use App\GameEngine\Housing\HousingManager;
use App\Helper\InventoryHelper;
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
    private InventoryHelper&MockObject $inventoryHelper;
    private \App\GameEngine\Housing\ResidentialParcels&MockObject $residentialParcels;

    private \App\GameEngine\Housing\HouseRentRouting&MockObject $rentRouting;
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
        $this->inventoryHelper = $this->createMock(InventoryHelper::class);
        // Sac vide par defaut : la voie marchande reste la voie de reference des
        // tests HOU-05, et seuls ceux d'ECO-30 posent un necessaire.
        $this->inventoryHelper->method('removeItemBySlug')->willReturn(0);

        // FOY-18 : par defaut, aucun rang ne loge — les cas de parcelles le
        // configurent explicitement. Le plancher des Jardins n'en depend pas.
        $this->residentialParcels = $this->createMock(\App\GameEngine\Housing\ResidentialParcels::class);

        // FOY-19 : le routage du loyer est mesure par `HouseRentRoutingTest` —
        // ici on verifie que le manager l'appelle, pas ou il envoie.
        $this->rentRouting = $this->createMock(\App\GameEngine\Housing\HouseRentRouting::class);

        $this->manager = new HousingManager($this->em, $this->houseRepository, new NullLogger(), $this->inventoryHelper, $this->residentialParcels, $this->rentRouting);
    }

    /**
     * Remplace le sac par un sac qui contient un necessaire d'ameublement.
     */
    private function withFurnishingKit(): void
    {
        $inventoryHelper = $this->createMock(InventoryHelper::class);
        $inventoryHelper->method('removeItemBySlug')
            ->with(HousingManager::FURNISHING_KIT_SLUG, 1)
            ->willReturn(1);

        $this->manager = new HousingManager($this->em, $this->houseRepository, new NullLogger(), $inventoryHelper, $this->residentialParcels, $this->rentRouting);
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

    /**
     * FOY-18 : un foyer au rang de Hameau ou plus vend des parcelles — la
     * liste explicite est devenue une regle.
     */
    public function testARankedSettlementSellsParcels(): void
    {
        $hamlet = $this->zone('foret-des-murmures');
        $player = $this->playerIn($hamlet, 999_999);
        $this->residentialParcels->method('isRankResidential')->willReturn(true);
        $this->residentialParcels->method('canOpenParcel')->willReturn(true);

        $house = $this->manager->buyLand($player, $hamlet, 'Sous les murmures');

        self::assertSame($hamlet, $house->getZone());
    }

    /**
     * FOY-18 : la capacite ne gate que l'ouverture — un foyer plein refuse la
     * nouvelle parcelle, et rien d'autre ne se passe (jamais d'expulsion).
     */
    public function testAFullSettlementRefusesANewParcel(): void
    {
        $hamlet = $this->zone('foret-des-murmures');
        $player = $this->playerIn($hamlet, 999_999);
        $this->residentialParcels->method('isRankResidential')->willReturn(true);
        $this->residentialParcels->method('canOpenParcel')->willReturn(false);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Toutes les parcelles');

        $this->manager->buyLand($player, $hamlet, 'Trop tard');
    }

    /**
     * FOY-18 : le plancher des Jardins n'est jamais soumis a la capacite —
     * quoi qu'il arrive aux foyers, on peut toujours se loger la.
     */
    public function testTheGardensFloorIgnoresCapacity(): void
    {
        $gardens = $this->residentialZone();
        $player = $this->playerIn($gardens, 999_999);
        $this->residentialParcels->method('canOpenParcel')->willReturn(false);

        $house = $this->manager->buyLand($player, $gardens, 'Sous la Voute');

        self::assertSame($gardens, $house->getZone());
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
    // HOU-05 — ameublement & devise
    // ---------------------------------------------------------------------

    public function testFurnishingCostsGilsAndChangesTheStyle(): void
    {
        $player = $this->playerIn($this->residentialZone(), 10_000);
        $house = $this->ownedHouse($player);

        $this->manager->furnish($player, $house, HouseStyle::Rustic);

        self::assertSame(HouseStyle::Rustic, $house->getStyle());
        self::assertSame(10_000 - HouseStyle::Rustic->price(), $player->getGils());
    }

    public function testFurnishingWithoutEnoughGilsLeavesTheStyleUntouched(): void
    {
        $player = $this->playerIn($this->residentialZone(), 10);
        $house = $this->ownedHouse($player);

        try {
            $this->manager->furnish($player, $house, HouseStyle::Bourgeois);
            self::fail('L\'ameublement aurait du etre refuse.');
        } catch (\InvalidArgumentException $e) {
            self::assertStringContainsString('Gils', $e->getMessage());
        }

        self::assertSame(HouseStyle::Bare, $house->getStyle());
        self::assertSame(10, $player->getGils());
    }

    /**
     * Le necessaire du charpentier remplace le prix (ECO-30).
     *
     * L'ameublement se payait **uniquement** en Gils : un cosmetique que rien de
     * joueur ne produisait. C'est le seul endroit ou le charpentier touche le
     * housing, et il fallait qu'il le touche — sinon la ligne du bois n'aurait
     * eu que des armes au bout.
     */
    public function testAFurnishingKitReplacesThePriceEntirely(): void
    {
        $this->withFurnishingKit();

        $player = $this->playerIn($this->residentialZone(), 10);
        $house = $this->ownedHouse($player);

        $this->manager->furnish($player, $house, HouseStyle::Bourgeois);

        self::assertSame(HouseStyle::Bourgeois, $house->getStyle());
        self::assertSame(10, $player->getGils(), 'Le necessaire paie, pas la bourse.');
    }

    /**
     * Le necessaire n'est **pas** consomme quand le style est deja installe.
     *
     * Le refus se prononce avant tout prelevement, faute de quoi un double clic
     * couterait un objet de 290 Gils pour rien.
     */
    public function testAFurnishingKitIsNotSpentOnAStyleAlreadyInstalled(): void
    {
        $inventoryHelper = $this->createMock(InventoryHelper::class);
        $inventoryHelper->expects(self::never())->method('removeItemBySlug');
        $this->manager = new HousingManager($this->em, $this->houseRepository, new NullLogger(), $inventoryHelper, $this->residentialParcels, $this->rentRouting);

        $player = $this->playerIn($this->residentialZone(), 10_000);
        $house = $this->ownedHouse($player);
        $house->setStyle(HouseStyle::Rustic);

        $this->expectException(\InvalidArgumentException::class);

        $this->manager->furnish($player, $house, HouseStyle::Rustic);
    }

    public function testBuyingTheStyleAlreadyInstalledIsRefused(): void
    {
        $player = $this->playerIn($this->residentialZone(), 10_000);
        $house = $this->ownedHouse($player);
        $house->setStyle(HouseStyle::Rustic);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('deja meublee ainsi');

        $this->manager->furnish($player, $house, HouseStyle::Rustic);
    }

    public function testFurnishingSomeoneElsesHouseIsRefused(): void
    {
        $house = $this->ownedHouse($this->playerIn($this->residentialZone(), 0, 1));

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('n\'est pas la votre');

        $this->manager->furnish($this->playerIn($this->residentialZone(), 99_999, 2), $house, HouseStyle::Rustic);
    }

    /**
     * Le style se paie, la devise non : on ne fait pas payer un joueur pour
     * ecrire une phrase chez lui.
     */
    public function testTheMottoIsFreeAndTrimmed(): void
    {
        $player = $this->playerIn($this->residentialZone(), 500);
        $house = $this->ownedHouse($player);

        $this->manager->setMotto($player, $house, '  Ici on forge  ');

        self::assertSame('Ici on forge', $house->getMotto());
        self::assertSame(500, $player->getGils());
    }

    public function testAnEmptyMottoClearsIt(): void
    {
        $player = $this->playerIn($this->residentialZone(), 0);
        $house = $this->ownedHouse($player);
        $house->setMotto('Ancienne');

        $this->manager->setMotto($player, $house, '   ');

        self::assertNull($house->getMotto());
    }

    private function ownedHouse(Player $owner): PlayerHouse
    {
        $house = new PlayerHouse();
        $house->setOwner($owner);
        $house->setZone($this->residentialZone());
        $house->setRentDueAt(new \DateTimeImmutable('+7 days'));

        return $house;
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
