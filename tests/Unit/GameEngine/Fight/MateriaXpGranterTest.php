<?php

namespace App\Tests\Unit\GameEngine\Fight;

use App\Entity\App\Fight;
use App\Entity\App\Inventory;
use App\Entity\App\Mob;
use App\Entity\App\Player;
use App\Entity\App\PlayerItem;
use App\Entity\App\Slot;
use App\Entity\Game\Item;
use App\Entity\Game\Monster;
use App\Enum\Element;
use App\Event\Fight\MobDeadEvent;
use App\GameEngine\Event\GameEventBonusProvider;
use App\GameEngine\Fight\MateriaXpGranter;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class MateriaXpGranterTest extends TestCase
{
    private EntityManagerInterface&MockObject $entityManager;
    private LoggerInterface&MockObject $logger;
    private MateriaXpGranter $granter;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->entityManager->method('persist');
        $this->entityManager->method('flush');

        $bonusProvider = $this->createMock(GameEventBonusProvider::class);
        $bonusProvider->method('getXpMultiplier')->willReturn(1.0);

        $this->granter = new MateriaXpGranter(
            $this->entityManager,
            $this->logger,
            $bonusProvider,
        );
    }

    /**
     * Cree un Mob mock lie a un Fight.
     */
    private function createMobWithFight(
        int $monsterLevel = 1,
        bool $isBoss = false,
        array $players = [],
        array $fightMetadata = [],
    ): Mob&MockObject {
        $monster = $this->createMock(Monster::class);
        $monster->method('getLevel')->willReturn($monsterLevel);
        $monster->method('isBoss')->willReturn($isBoss);

        $fight = $this->createMock(Fight::class);
        $fight->method('getPlayers')->willReturn(new ArrayCollection($players));
        $fight->method('getMetadataValue')->willReturnCallback(
            fn (string $key, mixed $default = null) => $fightMetadata[$key] ?? $default,
        );

        $mob = $this->createMock(Mob::class);
        $mob->method('getFight')->willReturn($fight);
        $mob->method('getMonster')->willReturn($monster);
        $mob->method('isSummoned')->willReturn(false);

        return $mob;
    }

    /**
     * Cree un PlayerItem materia mock qui attend recevoir de l'XP.
     */
    private function createMateriaItem(int $expectedXp = 0, Element $element = Element::Fire): PlayerItem&MockObject
    {
        $genericItem = $this->createMock(Item::class);
        $genericItem->method('getName')->willReturn('Materia Feu');
        $genericItem->method('getElement')->willReturn($element);

        $materia = $this->createMock(PlayerItem::class);
        $materia->method('isMateria')->willReturn(true);
        $materia->method('getGenericItem')->willReturn($genericItem);
        $materia->method('getExperience')->willReturn(100);
        $materia->method('getMateriaLevel')->willReturn(1);

        if ($expectedXp > 0) {
            $materia->expects($this->once())
                ->method('addExperience')
                ->with($expectedXp);
        }

        return $materia;
    }

    /**
     * Cree un Slot mock contenant un item (ou null).
     */
    private function createSlot(?PlayerItem $materia = null, ?Element $element = null): Slot&MockObject
    {
        $slot = $this->createMock(Slot::class);
        $slot->method('getItemSet')->willReturn($materia);
        $slot->method('getElement')->willReturn($element);

        return $slot;
    }

    /**
     * Cree un PlayerItem equipement avec des slots.
     */
    private function createEquipmentWithSlots(array $slots): PlayerItem&MockObject
    {
        $equipment = $this->createMock(PlayerItem::class);
        $equipment->method('getSlots')->willReturn(new ArrayCollection($slots));

        return $equipment;
    }

    /**
     * Cree un Player mock avec des inventaires et items.
     */
    private function createPlayerWithInventory(
        bool $isDead = false,
        array $inventoryItems = [],
    ): Player&MockObject {
        $inventory = $this->createMock(Inventory::class);
        $inventory->method('getItems')->willReturn(new ArrayCollection($inventoryItems));

        $player = $this->createMock(Player::class);
        $player->method('isDead')->willReturn($isDead);
        $player->method('getInventories')->willReturn(new ArrayCollection([$inventory]));

        return $player;
    }

    public function testGrantsBaseXpToSocketedMateria(): void
    {
        // Monster level 1 => XP = 10 * 1 = 10
        $materia = $this->createMateriaItem(expectedXp: 10);
        $slot = $this->createSlot($materia);
        $equipment = $this->createEquipmentWithSlots([$slot]);
        $player = $this->createPlayerWithInventory(isDead: false, inventoryItems: [$equipment]);

        $mob = $this->createMobWithFight(monsterLevel: 1, isBoss: false, players: [$player]);
        $event = new MobDeadEvent($mob);

        $this->granter->onMobDead($event);
    }

    public function testGrantsScaledXpBasedOnMonsterLevel(): void
    {
        // Monster level 5 => XP = 10 * 5 = 50
        $materia = $this->createMateriaItem(expectedXp: 50);
        $slot = $this->createSlot($materia);
        $equipment = $this->createEquipmentWithSlots([$slot]);
        $player = $this->createPlayerWithInventory(isDead: false, inventoryItems: [$equipment]);

        $mob = $this->createMobWithFight(monsterLevel: 5, isBoss: false, players: [$player]);
        $event = new MobDeadEvent($mob);

        $this->granter->onMobDead($event);
    }

    public function testBossKillGives5xXpMultiplier(): void
    {
        // Boss level 3 => XP = 10 * 3 * 5 = 150
        $materia = $this->createMateriaItem(expectedXp: 150);
        $slot = $this->createSlot($materia);
        $equipment = $this->createEquipmentWithSlots([$slot]);
        $player = $this->createPlayerWithInventory(isDead: false, inventoryItems: [$equipment]);

        $mob = $this->createMobWithFight(monsterLevel: 3, isBoss: true, players: [$player]);
        $event = new MobDeadEvent($mob);

        $this->granter->onMobDead($event);
    }

    public function testDeadPlayersDontReceiveMateriaXp(): void
    {
        $materia = $this->createMock(PlayerItem::class);
        $materia->method('isMateria')->willReturn(true);
        // Ne devrait JAMAIS recevoir d'XP
        $materia->expects($this->never())->method('addExperience');

        $slot = $this->createSlot($materia);
        $equipment = $this->createEquipmentWithSlots([$slot]);
        $player = $this->createPlayerWithInventory(isDead: true, inventoryItems: [$equipment]);

        $mob = $this->createMobWithFight(monsterLevel: 1, isBoss: false, players: [$player]);
        $event = new MobDeadEvent($mob);

        $this->granter->onMobDead($event);
    }

    public function testOnlyMateriaItemsReceiveXp(): void
    {
        // Item non-materia dans le slot
        $nonMateria = $this->createMock(PlayerItem::class);
        $nonMateria->method('isMateria')->willReturn(false);
        $nonMateria->expects($this->never())->method('addExperience');

        $slot = $this->createSlot($nonMateria);
        $equipment = $this->createEquipmentWithSlots([$slot]);
        $player = $this->createPlayerWithInventory(isDead: false, inventoryItems: [$equipment]);

        $mob = $this->createMobWithFight(monsterLevel: 1, isBoss: false, players: [$player]);
        $event = new MobDeadEvent($mob);

        $this->granter->onMobDead($event);
    }

    public function testEmptySlotIsSkipped(): void
    {
        // Slot vide (getItemSet retourne null)
        $slot = $this->createSlot(null);
        $equipment = $this->createEquipmentWithSlots([$slot]);
        $player = $this->createPlayerWithInventory(isDead: false, inventoryItems: [$equipment]);

        $mob = $this->createMobWithFight(monsterLevel: 1, isBoss: false, players: [$player]);
        $event = new MobDeadEvent($mob);

        // Ne devrait pas lever d'exception
        $this->granter->onMobDead($event);
        $this->assertTrue(true); // Verifie que l'execution n'a pas plante
    }

    public function testNoFightReturnsEarly(): void
    {
        $mob = $this->createMock(Mob::class);
        $mob->method('getFight')->willReturn(null);

        $event = new MobDeadEvent($mob);

        // Pas de persist/flush appele pour les materia
        $this->entityManager->expects($this->never())->method('flush');

        $this->granter->onMobDead($event);
    }

    public function testMultipleMateriaAllReceiveXp(): void
    {
        // Deux materia dans des slots differents
        $materia1 = $this->createMateriaItem(expectedXp: 10);
        $materia2 = $this->createMateriaItem(expectedXp: 10);

        $slot1 = $this->createSlot($materia1);
        $slot2 = $this->createSlot($materia2);
        $equipment = $this->createEquipmentWithSlots([$slot1, $slot2]);
        $player = $this->createPlayerWithInventory(isDead: false, inventoryItems: [$equipment]);

        $mob = $this->createMobWithFight(monsterLevel: 1, isBoss: false, players: [$player]);
        $event = new MobDeadEvent($mob);

        $this->granter->onMobDead($event);
    }

    public function testMultiplePlayersAllReceiveXp(): void
    {
        $materia1 = $this->createMateriaItem(expectedXp: 20);
        $materia2 = $this->createMateriaItem(expectedXp: 20);

        $slot1 = $this->createSlot($materia1);
        $slot2 = $this->createSlot($materia2);

        $equipment1 = $this->createEquipmentWithSlots([$slot1]);
        $equipment2 = $this->createEquipmentWithSlots([$slot2]);

        $player1 = $this->createPlayerWithInventory(isDead: false, inventoryItems: [$equipment1]);
        $player2 = $this->createPlayerWithInventory(isDead: false, inventoryItems: [$equipment2]);

        $mob = $this->createMobWithFight(monsterLevel: 2, isBoss: false, players: [$player1, $player2]);
        $event = new MobDeadEvent($mob);

        $this->granter->onMobDead($event);
    }

    public function testGetSubscribedEvents(): void
    {
        $events = MateriaXpGranter::getSubscribedEvents();

        $this->assertArrayHasKey(MobDeadEvent::NAME, $events);
        $this->assertSame('onMobDead', $events[MobDeadEvent::NAME]);
    }

    public function testMonsterLevelDefaultsTo1WhenLevelIs1(): void
    {
        // Monster avec level 1 => XP = 10 * 1 = 10
        $materia = $this->createMateriaItem(expectedXp: 10);
        $slot = $this->createSlot($materia);
        $equipment = $this->createEquipmentWithSlots([$slot]);
        $player = $this->createPlayerWithInventory(isDead: false, inventoryItems: [$equipment]);

        $mob = $this->createMobWithFight(monsterLevel: 1, isBoss: false, players: [$player]);
        $event = new MobDeadEvent($mob);

        $this->granter->onMobDead($event);
    }

    public function testDungeonHeroicXpMultiplierApplied(): void
    {
        // Monster level 2 => base XP = 10 * 2 = 20, heroic 1.5x => 30
        $materia = $this->createMateriaItem(expectedXp: 30);
        $slot = $this->createSlot($materia);
        $equipment = $this->createEquipmentWithSlots([$slot]);
        $player = $this->createPlayerWithInventory(isDead: false, inventoryItems: [$equipment]);

        $mob = $this->createMobWithFight(
            monsterLevel: 2,
            isBoss: false,
            players: [$player],
            fightMetadata: ['difficulty_xp_multiplier' => 1.5],
        );
        $event = new MobDeadEvent($mob);

        $this->granter->onMobDead($event);
    }

    public function testDungeonMythicXpMultiplierApplied(): void
    {
        // Monster level 2 => base XP = 10 * 2 = 20, mythic 2.5x => 50
        $materia = $this->createMateriaItem(expectedXp: 50);
        $slot = $this->createSlot($materia);
        $equipment = $this->createEquipmentWithSlots([$slot]);
        $player = $this->createPlayerWithInventory(isDead: false, inventoryItems: [$equipment]);

        $mob = $this->createMobWithFight(
            monsterLevel: 2,
            isBoss: false,
            players: [$player],
            fightMetadata: ['difficulty_xp_multiplier' => 2.5],
        );
        $event = new MobDeadEvent($mob);

        $this->granter->onMobDead($event);
    }

    public function testDungeonXpMultiplierStacksWithBossMultiplier(): void
    {
        // Boss level 2 => base XP = 10 * 2 * 5 = 100, mythic 2.5x => 250
        $materia = $this->createMateriaItem(expectedXp: 250);
        $slot = $this->createSlot($materia);
        $equipment = $this->createEquipmentWithSlots([$slot]);
        $player = $this->createPlayerWithInventory(isDead: false, inventoryItems: [$equipment]);

        $mob = $this->createMobWithFight(
            monsterLevel: 2,
            isBoss: true,
            players: [$player],
            fightMetadata: ['difficulty_xp_multiplier' => 2.5],
        );
        $event = new MobDeadEvent($mob);

        $this->granter->onMobDead($event);
    }

    public function testNormalDungeonNoXpBonus(): void
    {
        // Normal difficulty (no metadata) => XP = 10 * 1 = 10 (no bonus)
        $materia = $this->createMateriaItem(expectedXp: 10);
        $slot = $this->createSlot($materia);
        $equipment = $this->createEquipmentWithSlots([$slot]);
        $player = $this->createPlayerWithInventory(isDead: false, inventoryItems: [$equipment]);

        $mob = $this->createMobWithFight(
            monsterLevel: 1,
            isBoss: false,
            players: [$player],
            fightMetadata: ['difficulty_xp_multiplier' => 1.0],
        );
        $event = new MobDeadEvent($mob);

        $this->granter->onMobDead($event);
    }
}
