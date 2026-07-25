<?php

declare(strict_types=1);

namespace App\Tests\Unit\GameEngine\Codex;

use App\Entity\App\Player;
use App\Entity\App\PlayerCodexEntry;
use App\Entity\Game\CodexEntry;
use App\GameEngine\Codex\CodexUnlockService;
use App\Repository\CodexEntryRepository;
use App\Repository\PlayerCodexEntryRepository;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

final class CodexUnlockServiceTest extends TestCase
{
    public function testUnlockPersistsWhenNotAlreadyUnlocked(): void
    {
        $player = new Player();
        $entry = (new CodexEntry())->setSlug('region-foret');

        $playerCodexRepo = $this->createMock(PlayerCodexEntryRepository::class);
        $playerCodexRepo->method('hasUnlocked')->willReturn(false);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->once())->method('persist')->with($this->isInstanceOf(PlayerCodexEntry::class));
        $em->expects($this->once())->method('flush');

        $service = new CodexUnlockService($this->createMock(CodexEntryRepository::class), $playerCodexRepo, $em);

        self::assertTrue($service->unlock($player, $entry));
    }

    public function testUnlockIsIdempotentWhenAlreadyUnlocked(): void
    {
        $player = new Player();
        $entry = (new CodexEntry())->setSlug('region-foret');

        $playerCodexRepo = $this->createMock(PlayerCodexEntryRepository::class);
        $playerCodexRepo->method('hasUnlocked')->willReturn(true);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->never())->method('persist');
        $em->expects($this->never())->method('flush');

        $service = new CodexUnlockService($this->createMock(CodexEntryRepository::class), $playerCodexRepo, $em);

        self::assertFalse($service->unlock($player, $entry));
    }

    public function testUnlockByTriggerUnlocksEveryMatchingEntry(): void
    {
        $player = new Player();
        $entryA = (new CodexEntry())->setSlug('a');
        $entryB = (new CodexEntry())->setSlug('b');

        $codexRepo = $this->createMock(CodexEntryRepository::class);
        $codexRepo->expects($this->once())
            ->method('findByUnlock')
            ->with(CodexEntry::UNLOCK_ZONE_VISIT, 'foret-des-murmures')
            ->willReturn([$entryA, $entryB]);

        $playerCodexRepo = $this->createMock(PlayerCodexEntryRepository::class);
        $playerCodexRepo->method('hasUnlocked')->willReturn(false);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->exactly(2))->method('persist');

        $service = new CodexUnlockService($codexRepo, $playerCodexRepo, $em);

        self::assertSame(2, $service->unlockByTrigger($player, CodexEntry::UNLOCK_ZONE_VISIT, 'foret-des-murmures'));
    }

    public function testUnlockByTriggerCountsOnlyNewUnlocks(): void
    {
        $player = new Player();
        $entryA = (new CodexEntry())->setSlug('a');
        $entryB = (new CodexEntry())->setSlug('b');

        $codexRepo = $this->createMock(CodexEntryRepository::class);
        $codexRepo->method('findByUnlock')->willReturn([$entryA, $entryB]);

        // entryA deja debloque, entryB nouveau.
        $playerCodexRepo = $this->createMock(PlayerCodexEntryRepository::class);
        $playerCodexRepo->method('hasUnlocked')->willReturnOnConsecutiveCalls(true, false);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->once())->method('persist');

        $service = new CodexUnlockService($codexRepo, $playerCodexRepo, $em);

        self::assertSame(1, $service->unlockByTrigger($player, CodexEntry::UNLOCK_BOSS_KILL, 'forest_guardian'));
    }
}
