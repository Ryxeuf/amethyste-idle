<?php

namespace App\Tests\Unit\EventListener;

use App\Entity\App\Fight;
use App\Entity\App\Mob;
use App\Entity\App\Player;
use App\Entity\App\PlayerBestiary;
use App\Entity\Game\Monster;
use App\Event\Fight\MobDeadEvent;
use App\EventListener\BestiaryListener;
use App\Repository\PlayerBestiaryRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class BestiaryListenerTest extends TestCase
{
    private PlayerBestiaryRepository&MockObject $bestiaryRepository;
    private EntityManagerInterface&MockObject $entityManager;
    private BestiaryListener $listener;

    protected function setUp(): void
    {
        $this->bestiaryRepository = $this->createMock(PlayerBestiaryRepository::class);
        $this->entityManager = $this->createMock(EntityManagerInterface::class);

        $this->listener = new BestiaryListener(
            $this->bestiaryRepository,
            $this->entityManager,
        );
    }

    private function createMobWithFight(array $players = []): Mob&MockObject
    {
        $monster = $this->createMock(Monster::class);
        $monster->method('getName')->willReturn('Slime');

        $fight = $this->createMock(Fight::class);
        $fight->method('getPlayers')->willReturn(new ArrayCollection($players));

        $mob = $this->createMock(Mob::class);
        $mob->method('getFight')->willReturn($fight);
        $mob->method('getMonster')->willReturn($monster);

        return $mob;
    }

    private function createPlayer(bool $isDead = false): Player&MockObject
    {
        $player = $this->createMock(Player::class);
        $player->method('isDead')->willReturn($isDead);

        return $player;
    }

    public function testGetSubscribedEvents(): void
    {
        $events = BestiaryListener::getSubscribedEvents();

        $this->assertArrayHasKey(MobDeadEvent::NAME, $events);
        $this->assertSame('onMobDead', $events[MobDeadEvent::NAME]);
    }

    public function testFirstKillCreatesNewEntry(): void
    {
        $player = $this->createPlayer();
        $mob = $this->createMobWithFight([$player]);

        $this->bestiaryRepository->method('findOneByPlayerAndMonster')
            ->willReturn(null);

        $this->entityManager->expects($this->once())
            ->method('persist')
            ->with($this->isInstanceOf(PlayerBestiary::class));

        $this->entityManager->expects($this->once())
            ->method('flush');

        $this->listener->onMobDead(new MobDeadEvent($mob));
    }

    public function testSubsequentKillIncrementsCount(): void
    {
        $player = $this->createPlayer();
        $mob = $this->createMobWithFight([$player]);

        $existingEntry = $this->createMock(PlayerBestiary::class);
        $existingEntry->expects($this->once())
            ->method('incrementKillCount');

        $this->bestiaryRepository->method('findOneByPlayerAndMonster')
            ->willReturn($existingEntry);

        $this->entityManager->expects($this->never())
            ->method('persist');

        $this->entityManager->expects($this->once())
            ->method('flush');

        $this->listener->onMobDead(new MobDeadEvent($mob));
    }

    public function testAllLivingPlayersGetCredit(): void
    {
        $player1 = $this->createPlayer(isDead: false);
        $player2 = $this->createPlayer(isDead: false);
        $mob = $this->createMobWithFight([$player1, $player2]);

        $this->bestiaryRepository->method('findOneByPlayerAndMonster')
            ->willReturn(null);

        $this->entityManager->expects($this->exactly(2))
            ->method('persist');

        $this->listener->onMobDead(new MobDeadEvent($mob));
    }

    public function testDeadPlayersDoNotGetCredit(): void
    {
        $alivePlayer = $this->createPlayer(isDead: false);
        $deadPlayer = $this->createPlayer(isDead: true);
        $mob = $this->createMobWithFight([$alivePlayer, $deadPlayer]);

        $this->bestiaryRepository->method('findOneByPlayerAndMonster')
            ->willReturn(null);

        $this->entityManager->expects($this->once())
            ->method('persist');

        $this->listener->onMobDead(new MobDeadEvent($mob));
    }

    public function testNoFightReturnsEarly(): void
    {
        $mob = $this->createMock(Mob::class);
        $mob->method('getFight')->willReturn(null);
        $mob->method('getMonster')->willReturn($this->createMock(Monster::class));

        $this->entityManager->expects($this->never())->method('flush');

        $this->listener->onMobDead(new MobDeadEvent($mob));
    }
}
