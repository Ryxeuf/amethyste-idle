<?php

namespace App\Tests\Unit\GameEngine\Reputation;

use App\Entity\App\Player;
use App\Entity\App\PlayerFaction;
use App\Entity\App\PlayerItem;
use App\Entity\App\Slot;
use App\Entity\Game\Faction;
use App\Entity\Game\Item;
use App\Enum\Purity;
use App\GameEngine\Reputation\CounterfeitService;
use App\GameEngine\Reputation\CrystalBuybackFloor;
use App\GameEngine\Reputation\ShadowsMarketCatalog;
use App\GameEngine\Reputation\ShadowsMarketException;
use App\Helper\InventoryHelper;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * « Une contrefacon marche neuf fois et vous trahit a la dixieme » (FAC-07).
 *
 * Le compteur se tire a la creation et se decremente par lancement ; la
 * trahison brise la materia en amethyste Trouble (plus les eclats) et frappe
 * le lanceur. L'œil du faussaire voit a Honore, le desamorcage demonte a
 * Revere — et le refus, comme toujours aux Ruelles, ne revele rien.
 */
class CounterfeitServiceTest extends TestCase
{
    private EntityManagerInterface&MockObject $entityManager;
    private InventoryHelper&MockObject $inventoryHelper;
    private ?PlayerFaction $line = null;
    private ?Faction $faction = null;

    /** @var list<PlayerItem> */
    private array $granted = [];

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->inventoryHelper = $this->createMock(InventoryHelper::class);
        $this->inventoryHelper->method('addItem')->willReturnCallback(
            function (PlayerItem $item): void {
                $this->granted[] = $item;
            },
        );
        $this->faction = (new Faction())->setSlug('ombres')->setName('Confrérie des Ruelles');
    }

    private function service(?int $fixedRoll = null): CounterfeitService
    {
        $factionRepository = $this->createMock(EntityRepository::class);
        $factionRepository->method('findOneBy')->willReturnCallback(fn (): ?Faction => $this->faction);

        $lineRepository = $this->createMock(EntityRepository::class);
        $lineRepository->method('findOneBy')->willReturnCallback(fn (): ?PlayerFaction => $this->line);

        $itemRepository = $this->createMock(EntityRepository::class);
        $itemRepository->method('findOneBy')->willReturnCallback(
            function (array $criteria): ?Item {
                $item = new Item();
                $item->setSlug($criteria['slug']);
                $item->setName($criteria['slug']);

                return $item;
            },
        );

        $this->entityManager->method('getRepository')->willReturnCallback(
            fn (string $class): EntityRepository => match ($class) {
                Faction::class => $factionRepository,
                PlayerFaction::class => $lineRepository,
                default => $itemRepository,
            },
        );

        $catalog = new ShadowsMarketCatalog(\dirname(__DIR__, 4));

        if (null === $fixedRoll) {
            return new CounterfeitService($this->entityManager, $catalog, $this->inventoryHelper);
        }

        return new class($this->entityManager, $catalog, $this->inventoryHelper, $fixedRoll) extends CounterfeitService {
            public function __construct(
                EntityManagerInterface $em,
                ShadowsMarketCatalog $catalog,
                InventoryHelper $inventoryHelper,
                private readonly int $fixedRoll,
            ) {
                parent::__construct($em, $catalog, $inventoryHelper);
            }

            protected function roll(int $min, int $max): int
            {
                return $this->fixedRoll;
            }
        };
    }

    private function materia(): PlayerItem
    {
        $generic = new Item();
        $generic->setName('Matéria feu I');
        $generic->setSlug('m1-fire-ball');

        $materia = new PlayerItem();
        $materia->setGenericItem($generic);

        return $materia;
    }

    private function reputation(Player $player, int $points): void
    {
        $line = new PlayerFaction();
        $line->setPlayer($player);
        $line->setFaction($this->faction);
        $line->setReputation($points);
        $this->line = $line;
    }

    public function testMarkDrawsTheHiddenCounterInTheCanonRange(): void
    {
        $materia = $this->materia();

        $this->service()->mark($materia, false);

        self::assertTrue($materia->isCounterfeit());
        self::assertFalse($materia->isCounterfeitIdentified());
        self::assertGreaterThanOrEqual(8, $materia->getCounterfeitCharges(), 'La fourchette canonique : ~neuf fois avant la trahison.');
        self::assertLessThanOrEqual(12, $materia->getCounterfeitCharges());
    }

    public function testTheCounterBetraysOnlyAtItsLastCharge(): void
    {
        $materia = $this->materia();
        $materia->setCounterfeit(true);
        $materia->setCounterfeitCharges(2);
        $service = $this->service();

        self::assertFalse($service->consumeCharge($materia), 'Elle marche encore.');
        self::assertTrue($service->consumeCharge($materia), 'La derniere charge est la trahison.');
    }

    public function testAnAuthenticMateriaNeverBetrays(): void
    {
        $materia = $this->materia();
        $service = $this->service();

        for ($i = 0; $i < 50; ++$i) {
            self::assertFalse($service->consumeCharge($materia));
        }
        self::assertNull($materia->getCounterfeitCharges(), 'Une authentique n\'a pas de compteur.');
    }

    /**
     * La trahison : la materia quitte son emplacement, se brise en amethyste
     * Trouble + eclats, et le contrecoup coute 25 % de la vie max.
     */
    public function testBetrayalBreaksBacklashesAndLeavesTheParts(): void
    {
        $player = new Player();
        $player->setName('Roshen');
        $player->setLife(80);
        $materia = $this->materia();
        $materia->setCounterfeit(true);
        $materia->setCounterfeitCharges(0);
        $slot = new Slot();
        $slot->setItemSet($materia);

        $this->entityManager->expects(self::once())->method('remove')->with($materia);

        $messages = $this->service()->betray($player, $materia, $slot, 200);

        self::assertNull($slot->getItemSet(), 'La materia brisee quitte son emplacement.');
        self::assertSame(80 - 50, $player->getLife(), 'Le contrecoup : 25 % de 200 PV de vie max effective.');
        self::assertCount(2, $this->granted, 'Il reste un cristal trouble et des eclats.');
        self::assertSame(CrystalBuybackFloor::CRYSTAL_SLUG, $this->granted[0]->getGenericItem()->getSlug());
        self::assertSame(Purity::Trouble, $this->granted[0]->getPurity(), 'Le bris rend du Trouble — jamais mieux.');
        self::assertSame(CounterfeitService::SHARDS_SLUG, $this->granted[1]->getGenericItem()->getSlug());
        self::assertNotEmpty($messages);
    }

    public function testTheForgersEyeSeesAtHonore(): void
    {
        $player = new Player();
        $materia = $this->materia();
        $materia->setCounterfeit(true);
        $service = $this->service();

        self::assertFalse($service->eyeSees($player, $materia), 'Sans l\'œil, une contrefacon non identifiee est indiscernable.');

        $this->reputation($player, 5000);
        self::assertTrue($service->eyeSees($player, $materia), 'Honore : l\'œil du faussaire la voit.');
    }

    public function testAnIdentifiedCounterfeitIsSeenByItsMaker(): void
    {
        $player = new Player();
        $materia = $this->materia();
        $materia->setCounterfeit(true);
        $materia->setCounterfeitIdentified(true);

        self::assertTrue($this->service()->eyeSees($player, $materia), 'Le faussaire connait son œuvre, sans palier.');
    }

    public function testDefuseRequiresRevere(): void
    {
        $player = new Player();
        $this->reputation($player, 5000);
        $materia = $this->materia();
        $materia->setCounterfeit(true);
        $materia->setCounterfeitIdentified(true);

        try {
            $this->service()->defuse($player, $materia);
            self::fail('Honore ne suffit pas : le desamorcage est un savoir de Revere.');
        } catch (ShadowsMarketException $e) {
            self::assertSame('game.shadows.counterfeit.error.tier', $e->getMessage());
        }
    }

    /**
     * Desamorcer une authentique repond « rien a desamorcer » — le meme refus
     * neutre qui protege l'authentique d'un demontage accidentel.
     */
    public function testDefusingAnAuthenticRefusesQuietly(): void
    {
        $player = new Player();
        $this->reputation($player, 10000);

        try {
            $this->service()->defuse($player, $this->materia());
            self::fail('Une authentique n\'a rien a desamorcer.');
        } catch (ShadowsMarketException $e) {
            self::assertSame('game.shadows.counterfeit.error.not_seen', $e->getMessage());
        }
    }

    public function testDefuseDismantlesIntoTroubleAndEssence(): void
    {
        $player = new Player();
        $this->reputation($player, 10000);
        $materia = $this->materia();
        $materia->setCounterfeit(true);
        $materia->setCounterfeitIdentified(true);

        $this->entityManager->expects(self::once())->method('remove')->with($materia);

        $result = $this->service()->defuse($player, $materia);

        self::assertSame(3, $result['essence']);
        self::assertSame(3, $player->getEssence());
        self::assertCount(1, $this->granted);
        self::assertSame(Purity::Trouble, $this->granted[0]->getPurity());
    }

    public function testASocketedCounterfeitMustBeUnsocketedFirst(): void
    {
        $player = new Player();
        $this->reputation($player, 10000);
        $materia = $this->materia();
        $materia->setCounterfeit(true);
        $materia->setCounterfeitIdentified(true);
        $materia->setGear(1);

        $this->expectException(ShadowsMarketException::class);

        $this->service()->defuse($player, $materia);
    }

    public function testLootRollsTheCatalogChance(): void
    {
        $marked = $this->materia();
        self::assertTrue($this->service(4)->maybeMarkLoot($marked), 'Un tirage sous la chance du catalogue sort contrefait.');
        self::assertTrue($marked->isCounterfeit());
        self::assertFalse($marked->isCounterfeitIdentified(), 'Le butin ne previent pas.');

        $clean = $this->materia();
        self::assertFalse($this->service(5)->maybeMarkLoot($clean), 'Au-dessus de la chance, le butin est authentique.');
        self::assertFalse($clean->isCounterfeit());
    }

    /**
     * Le crochet vers une faction pas encore semee est inerte : sans les
     * Ruelles en base, l'œil ne voit rien et la main ne fabrique pas.
     */
    public function testAnUnseededFactionKeepsTheHooksInert(): void
    {
        $this->faction = null;
        $player = new Player();
        $materia = $this->materia();
        $materia->setCounterfeit(true);

        self::assertFalse($this->service()->eyeSees($player, $materia));
        self::assertFalse($this->service()->canForge($player));
    }
}
