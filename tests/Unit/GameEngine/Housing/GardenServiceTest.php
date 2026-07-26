<?php

namespace App\Tests\Unit\GameEngine\Housing;

use App\Entity\App\GardenPlot;
use App\Entity\App\Inventory;
use App\Entity\App\Player;
use App\Entity\App\PlayerHouse;
use App\Entity\App\PlayerItem;
use App\Entity\Game\Item;
use App\GameEngine\Generator\PlayerItemGenerator;
use App\GameEngine\Housing\GardenService;
use App\Helper\InventoryHelper;
use App\Repository\GardenPlotRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

/**
 * Tache 129 (HOU-02) — le jardin.
 *
 * Pilier PBBG du housing : une production qui avance sans energie et sans
 * presence. Le jeu n'ayant pas d'objet « graine », on seme la plante elle-meme
 * et la parcelle en rend plusieurs.
 */
final class GardenServiceTest extends TestCase
{
    private EntityManagerInterface&MockObject $em;
    private InventoryHelper&MockObject $inventoryHelper;
    private GardenService $service;

    /** @var list<object> */
    private array $removed = [];

    /** @var list<PlayerItem> */
    private array $added = [];

    protected function setUp(): void
    {
        $this->removed = [];
        $this->added = [];

        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->em->method('remove')->willReturnCallback(function (object $e): void {
            $this->removed[] = $e;
        });

        $this->inventoryHelper = $this->createMock(InventoryHelper::class);
        $this->inventoryHelper->method('addItem')->willReturnCallback(function (PlayerItem $item): void {
            $this->added[] = $item;
        });

        $generator = $this->createMock(PlayerItemGenerator::class);
        $generator->method('generateFromItemId')->willReturnCallback(fn (): PlayerItem => new PlayerItem());

        $this->service = new GardenService(
            $this->em,
            $this->createMock(GardenPlotRepository::class),
            $this->inventoryHelper,
            $generator,
            new NullLogger(),
        );
    }

    public function testOnlyPlantSluggedItemsCanBeSown(): void
    {
        self::assertTrue($this->service->isPlantable($this->item('plant-sage')));
        self::assertFalse($this->service->isPlantable($this->item('ore-iron')));
    }

    /**
     * L'unite semee est consommee immediatement : sans cela un joueur pourrait
     * planter puis revendre la plante, et le jardin produirait a partir de rien.
     */
    public function testPlantingConsumesOneUnitFromTheBag(): void
    {
        $crop = $this->item('plant-sage');
        $player = $this->playerWith([$crop, $crop]);
        $plot = $this->plot($player);

        $this->service->plant($player, $plot, $crop);

        self::assertFalse($plot->isEmpty());
        self::assertSame($crop, $plot->getCrop());
        self::assertFalse($plot->isRipe(), 'La pousse prend du temps reel.');
        self::assertCount(1, $this->removed, 'Une seule unite est consommee.');
    }

    public function testPlantingWithoutTheCropIsRefused(): void
    {
        $player = $this->playerWith([]);
        $plot = $this->plot($player);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('a planter');

        $this->service->plant($player, $plot, $this->item('plant-sage'));
    }

    public function testAnOccupiedPlotCannotBeReplanted(): void
    {
        $crop = $this->item('plant-sage');
        $player = $this->playerWith([$crop]);
        $plot = $this->plot($player);
        $plot->plant($crop, new \DateTimeImmutable('+1 hour'));

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('deja plantee');

        $this->service->plant($player, $plot, $crop);
    }

    public function testANonPlantableItemIsRefused(): void
    {
        $ore = $this->item('ore-iron');
        $player = $this->playerWith([$ore]);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('ne se cultive pas');

        $this->service->plant($player, $this->plot($player), $ore);
    }

    public function testHarvestingARipePlotYieldsMoreThanWasSown(): void
    {
        $crop = $this->item('plant-sage');
        $player = $this->playerWith([]);
        $plot = $this->plot($player);
        $plot->plant($crop, new \DateTimeImmutable('-1 second'));

        $quantity = $this->service->harvest($player, $plot);

        self::assertGreaterThanOrEqual(GardenPlot::YIELD_MIN, $quantity);
        self::assertLessThanOrEqual(GardenPlot::YIELD_MAX, $quantity);
        self::assertGreaterThan(1, $quantity, 'Le jardin multiplie ce qu\'on y seme.');
        self::assertCount($quantity, $this->added);
        self::assertTrue($plot->isEmpty(), 'La parcelle se libere apres recolte.');
    }

    public function testHarvestingBeforeMaturityIsRefused(): void
    {
        $player = $this->playerWith([]);
        $plot = $this->plot($player);
        $plot->plant($this->item('plant-sage'), new \DateTimeImmutable('+1 hour'));

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('de pousse');

        $this->service->harvest($player, $plot);
    }

    public function testHarvestingAnEmptyPlotIsRefused(): void
    {
        $player = $this->playerWith([]);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('vide');

        $this->service->harvest($player, $this->plot($player));
    }

    public function testTendingSomeoneElsesGardenIsRefused(): void
    {
        $owner = $this->playerWith([], 1);
        $plot = $this->plot($owner);
        $plot->plant($this->item('plant-sage'), new \DateTimeImmutable('-1 second'));

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('n\'est pas le votre');

        $this->service->harvest($this->playerWith([], 2), $plot);
    }

    /**
     * HOU-04 : une demeure en arriere de loyer dort. La recolte est suspendue,
     * mais la plante **reste en terre** — rien n'est detruit, et le paiement
     * remet tout en marche.
     */
    public function testAGardenIsDormantWhileTheRentIsUnpaid(): void
    {
        $crop = $this->item('plant-sage');
        $player = $this->playerWith([]);
        $plot = $this->plot($player, new \DateTimeImmutable('-1 day'));
        $plot->plant($crop, new \DateTimeImmutable('-1 second'));

        try {
            $this->service->harvest($player, $plot);
            self::fail('La recolte aurait du etre refusee.');
        } catch (\InvalidArgumentException $e) {
            self::assertStringContainsString('arriere de loyer', $e->getMessage());
        }

        self::assertFalse($plot->isEmpty(), 'La plante reste en terre.');
        self::assertSame($crop, $plot->getCrop());
        self::assertSame([], $this->added);
    }

    /**
     * @param list<Item> $bagItems
     */
    private function playerWith(array $bagItems, int $id = 1): Player
    {
        $player = new Player();
        (new \ReflectionProperty(Player::class, 'id'))->setValue($player, $id);

        $bag = new Inventory();
        $bag->setType(Inventory::TYPE_BAG);
        $bag->setSize(20);
        $bag->setPlayer($player);

        foreach ($bagItems as $item) {
            $playerItem = new PlayerItem();
            $playerItem->setGenericItem($item);
            $playerItem->setInventory($bag);
            $bag->addItem($playerItem);
        }

        (new \ReflectionProperty(Player::class, 'inventories'))->setValue($player, new ArrayCollection([$bag]));

        return $player;
    }

    private function plot(Player $owner, ?\DateTimeImmutable $rentDueAt = null): GardenPlot
    {
        $house = new PlayerHouse();
        $house->setOwner($owner);
        $house->setRentDueAt($rentDueAt ?? new \DateTimeImmutable('+7 days'));

        $plot = new GardenPlot();
        $plot->setHouse($house);
        $plot->setPosition(0);

        return $plot;
    }

    private function item(string $slug): Item
    {
        $item = new Item();
        $item->setName($slug);
        $item->setSlug($slug);
        $item->setType(Item::TYPE_RESOURCE);
        (new \ReflectionProperty(Item::class, 'id'))->setValue($item, crc32($slug));

        return $item;
    }
}
