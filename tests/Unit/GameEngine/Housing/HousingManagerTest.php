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
