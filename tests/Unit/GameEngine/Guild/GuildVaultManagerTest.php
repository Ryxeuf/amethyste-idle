<?php

namespace App\Tests\Unit\GameEngine\Guild;

use App\Entity\App\Guild;
use App\Entity\App\GuildMember;
use App\Entity\App\GuildVault;
use App\Entity\App\GuildVaultLog;
use App\Entity\App\Inventory;
use App\Entity\App\Player;
use App\Entity\App\PlayerItem;
use App\Entity\Game\Item;
use App\Enum\GuildRank;
use App\GameEngine\Guild\GuildManager;
use App\GameEngine\Guild\GuildVaultManager;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class GuildVaultManagerTest extends TestCase
{
    private EntityManagerInterface&MockObject $em;
    private GuildManager&MockObject $guildManager;
    private GuildVaultManager $vaultManager;

    protected function setUp(): void
    {
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->guildManager = $this->createMock(GuildManager::class);
        $this->vaultManager = new GuildVaultManager($this->em, $this->guildManager);
    }

    public function testDepositSuccess(): void
    {
        $player = $this->createPlayer(1);
        $guild = $this->createGuildWithVault();
        $membership = $this->createMembership($guild, $player, GuildRank::Member);
        $inventory = $this->createBag($player);
        $playerItem = $this->createPlayerItem($inventory);

        $this->guildManager->method('getPlayerMembership')->willReturn($membership);

        $this->em->expects($this->atLeastOnce())->method('persist');
        $this->em->expects($this->once())->method('flush');

        $this->vaultManager->deposit($player, $playerItem);

        $this->assertNull($playerItem->getInventory());
        $this->assertSame($guild->getVault(), $playerItem->getGuildVault());
    }

    public function testDepositNotInGuild(): void
    {
        $player = $this->createPlayer(1);
        $inventory = $this->createBag($player);
        $playerItem = $this->createPlayerItem($inventory);

        $this->guildManager->method('getPlayerMembership')->willReturn(null);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('pas dans une guilde');

        $this->vaultManager->deposit($player, $playerItem);
    }

    public function testDepositEquippedItemFails(): void
    {
        $player = $this->createPlayer(1);
        $guild = $this->createGuildWithVault();
        $membership = $this->createMembership($guild, $player, GuildRank::Member);
        $inventory = $this->createBag($player);
        $playerItem = $this->createPlayerItem($inventory);
        $playerItem->setGear(PlayerItem::GEAR_CHEST);

        $this->guildManager->method('getPlayerMembership')->willReturn($membership);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('équipé');

        $this->vaultManager->deposit($player, $playerItem);
    }

    public function testDepositBoundItemFails(): void
    {
        $player = $this->createPlayer(1);
        $guild = $this->createGuildWithVault();
        $membership = $this->createMembership($guild, $player, GuildRank::Member);
        $inventory = $this->createBag($player);
        $playerItem = $this->createPlayerItem($inventory);
        $playerItem->setBoundToPlayerId(1);

        $this->guildManager->method('getPlayerMembership')->willReturn($membership);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('lié');

        $this->vaultManager->deposit($player, $playerItem);
    }

    public function testDepositVaultFullFails(): void
    {
        $player = $this->createPlayer(1);
        $guild = $this->createGuildWithVault(0); // maxSlots = 0
        $membership = $this->createMembership($guild, $player, GuildRank::Member);
        $inventory = $this->createBag($player);
        $playerItem = $this->createPlayerItem($inventory);

        $this->guildManager->method('getPlayerMembership')->willReturn($membership);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('plein');

        $this->vaultManager->deposit($player, $playerItem);
    }

    public function testDepositItemNotOwnedFails(): void
    {
        $player = $this->createPlayer(1);
        $otherPlayer = $this->createPlayer(2);
        $guild = $this->createGuildWithVault();
        $membership = $this->createMembership($guild, $player, GuildRank::Member);
        $otherInventory = $this->createBag($otherPlayer);
        $playerItem = $this->createPlayerItem($otherInventory);

        $this->guildManager->method('getPlayerMembership')->willReturn($membership);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('appartient pas');

        $this->vaultManager->deposit($player, $playerItem);
    }

    public function testWithdrawSuccess(): void
    {
        $player = $this->createPlayer(1);
        $guild = $this->createGuildWithVault();
        $vault = $guild->getVault();
        $membership = $this->createMembership($guild, $player, GuildRank::Member);
        $bag = $this->createBag($player, 10);

        $playerItem = $this->createPlayerItem(null);
        $vault->addItem($playerItem);

        $inventories = new ArrayCollection([$bag]);
        $player->method('getInventories')->willReturn($inventories);

        $this->guildManager->method('getPlayerMembership')->willReturn($membership);

        $this->em->expects($this->atLeastOnce())->method('persist');
        $this->em->expects($this->once())->method('flush');

        $this->vaultManager->withdraw($player, $playerItem);

        $this->assertSame($bag, $playerItem->getInventory());
        $this->assertNull($playerItem->getGuildVault());
    }

    public function testWithdrawRecruitFails(): void
    {
        $player = $this->createPlayer(1);
        $guild = $this->createGuildWithVault();
        $vault = $guild->getVault();
        $membership = $this->createMembership($guild, $player, GuildRank::Recruit);

        $playerItem = $this->createPlayerItem(null);
        $vault->addItem($playerItem);

        $this->guildManager->method('getPlayerMembership')->willReturn($membership);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('permission');

        $this->vaultManager->withdraw($player, $playerItem);
    }

    public function testWithdrawBagFullFails(): void
    {
        $player = $this->createPlayer(1);
        $guild = $this->createGuildWithVault();
        $vault = $guild->getVault();
        $membership = $this->createMembership($guild, $player, GuildRank::Member);

        $bag = $this->createBag($player, 0); // bag size = 0, always full

        $playerItem = $this->createPlayerItem(null);
        $vault->addItem($playerItem);

        $inventories = new ArrayCollection([$bag]);
        $player->method('getInventories')->willReturn($inventories);

        $this->guildManager->method('getPlayerMembership')->willReturn($membership);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('inventaire est plein');

        $this->vaultManager->withdraw($player, $playerItem);
    }

    public function testWithdrawItemNotInVaultFails(): void
    {
        $player = $this->createPlayer(1);
        $guild = $this->createGuildWithVault();
        $membership = $this->createMembership($guild, $player, GuildRank::Member);

        $playerItem = $this->createPlayerItem(null);

        $this->guildManager->method('getPlayerMembership')->willReturn($membership);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('pas dans le coffre');

        $this->vaultManager->withdraw($player, $playerItem);
    }

    public function testGetOrCreateVaultCreatesNew(): void
    {
        $guild = new Guild();
        $guild->setName('Test');
        $guild->setTag('TST');
        $guild->setLeader($this->createPlayer(1));

        $this->em->expects($this->once())->method('persist');
        $this->em->expects($this->once())->method('flush');

        $vault = $this->vaultManager->getOrCreateVault($guild);

        $this->assertSame($guild, $vault->getGuild());
        $this->assertSame(GuildVault::DEFAULT_MAX_SLOTS, $vault->getMaxSlots());
    }

    public function testGetOrCreateVaultReturnsExisting(): void
    {
        $guild = new Guild();
        $guild->setName('Test');
        $guild->setTag('TST');
        $guild->setLeader($this->createPlayer(1));

        $existingVault = new GuildVault();
        $existingVault->setGuild($guild);
        $guild->setVault($existingVault);

        $this->em->expects($this->never())->method('persist');

        $vault = $this->vaultManager->getOrCreateVault($guild);

        $this->assertSame($existingVault, $vault);
    }

    public function testGetRecentLogs(): void
    {
        $guild = new Guild();
        $guild->setName('Test');
        $guild->setTag('TST');
        $guild->setLeader($this->createPlayer(1));

        $repo = $this->createMock(EntityRepository::class);
        $repo->method('findBy')->willReturn([]);
        $this->em->method('getRepository')
            ->with(GuildVaultLog::class)
            ->willReturn($repo);

        $logs = $this->vaultManager->getRecentLogs($guild);

        $this->assertIsArray($logs);
        $this->assertCount(0, $logs);
    }

    /**
     * @return Player&MockObject
     */
    private function createPlayer(int $id): Player&MockObject
    {
        $player = $this->createMock(Player::class);
        $player->method('getId')->willReturn($id);
        $player->method('getName')->willReturn('Player' . $id);

        return $player;
    }

    private function createGuildWithVault(int $maxSlots = 30): Guild
    {
        $guild = new Guild();
        $guild->setName('Test Guild');
        $guild->setTag('TST');
        $guild->setLeader($this->createPlayer(99));

        $vault = new GuildVault();
        $vault->setGuild($guild);
        $vault->setMaxSlots($maxSlots);
        $guild->setVault($vault);

        return $guild;
    }

    private function createMembership(Guild $guild, Player $player, GuildRank $rank): GuildMember
    {
        $member = new GuildMember();
        $member->setGuild($guild);
        $member->setPlayer($player);
        $member->setRank($rank);

        return $member;
    }

    private function createBag(Player $player, int $size = 20): Inventory
    {
        $bag = new Inventory();
        $bag->setType(Inventory::TYPE_BAG);
        $bag->setSize($size);
        $bag->setPlayer($player);

        return $bag;
    }

    private function createPlayerItem(?Inventory $inventory): PlayerItem
    {
        $genericItem = $this->createMock(Item::class);
        $genericItem->method('getName')->willReturn('Test Item');

        $playerItem = new PlayerItem();
        $playerItem->setGenericItem($genericItem);
        if ($inventory) {
            $playerItem->setInventory($inventory);
            $inventory->addItem($playerItem);
        }

        return $playerItem;
    }
}
