<?php

namespace App\Tests\Unit\GameEngine\Realtime;

use App\Entity\App\Settlement;
use App\Entity\App\Zone;
use App\Enum\SettlementRank;
use App\Event\Zone\SettlementRankChangedEvent;
use App\GameEngine\Realtime\SettlementServiceAnnouncementHandler;
use App\GameEngine\Settlement\SettlementServiceDirectory;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Mercure\Update;

/**
 * Le palier se voit (FOY-06).
 *
 * Ce qui se teste ici n'est pas qu'un message part : c'est qu'il part **dans les
 * deux sens**, et qu'il range les services du bon cote. Une annonce qui ne
 * dirait que les ouvertures donnerait un monde ou les villes ne font que
 * grandir — l'inverse de ce que la decroissance existe pour raconter.
 */
class SettlementServiceAnnouncementHandlerTest extends TestCase
{
    public function testAPromotionAnnouncesWhatItOpens(): void
    {
        $captured = null;
        $hub = $this->hubCapturing($captured);

        $this->handler($hub, ['regional_market'])->onSettlementRankChanged(
            $this->rankChange(SettlementRank::Hamlet, SettlementRank::Town),
        );

        self::assertIsArray($captured);
        self::assertSame('zone/7/event', $captured['topic']);
        self::assertSame('settlement_rank_changed', $captured['type']);
        self::assertTrue($captured['settlement']['promotion']);
        self::assertSame(['regional_market'], $captured['settlement']['opened']);
        self::assertSame([], $captured['settlement']['closed']);
    }

    public function testARegressionAnnouncesWhatItCloses(): void
    {
        $captured = null;
        $hub = $this->hubCapturing($captured);

        $this->handler($hub, ['regional_market'])->onSettlementRankChanged(
            $this->rankChange(SettlementRank::Town, SettlementRank::Hamlet),
        );

        self::assertIsArray($captured);
        self::assertFalse($captured['settlement']['promotion']);
        self::assertSame([], $captured['settlement']['opened']);
        self::assertSame(['regional_market'], $captured['settlement']['closed']);
    }

    /**
     * Un changement de rang qui n'ouvre rien s'annonce quand meme : le palier
     * lui-meme est la nouvelle, et un Campement devenu Hameau merite d'etre vu
     * par ceux qui l'ont bati.
     */
    public function testARankChangeWithoutServiceIsStillAnnounced(): void
    {
        $captured = null;
        $hub = $this->hubCapturing($captured);

        $this->handler($hub, [])->onSettlementRankChanged(
            $this->rankChange(SettlementRank::Camp, SettlementRank::Hamlet),
        );

        self::assertIsArray($captured);
        self::assertSame('camp', $captured['settlement']['from']);
        self::assertSame('hamlet', $captured['settlement']['to']);
        self::assertSame([], $captured['settlement']['opened']);
    }

    public function testSubscribesToTheRankChange(): void
    {
        $events = SettlementServiceAnnouncementHandler::getSubscribedEvents();

        self::assertArrayHasKey(SettlementRankChangedEvent::NAME, $events);
        self::assertSame('onSettlementRankChanged', $events[SettlementRankChangedEvent::NAME]);
    }

    private function rankChange(SettlementRank $from, SettlementRank $to): SettlementRankChangedEvent
    {
        $zone = new Zone();
        $zone->setName('Foret des murmures');
        (new \ReflectionProperty(Zone::class, 'id'))->setValue($zone, 7);

        $settlement = new Settlement($zone);
        $settlement->setRank($to);

        return new SettlementRankChangedEvent($settlement, $from, $to);
    }

    /**
     * @param array<string, mixed>|null $captured
     */
    private function hubCapturing(?array &$captured): HubInterface
    {
        $hub = $this->createMock(HubInterface::class);
        $hub->expects($this->once())
            ->method('publish')
            ->willReturnCallback(static function (Update $update) use (&$captured): string {
                $captured = json_decode($update->getData(), true, 512, JSON_THROW_ON_ERROR);

                return 'id';
            });

        return $hub;
    }

    /**
     * @param list<string> $crossed
     */
    private function handler(HubInterface $hub, array $crossed): SettlementServiceAnnouncementHandler
    {
        $directory = $this->createMock(SettlementServiceDirectory::class);
        $directory->method('crossedBetween')->willReturn($crossed);

        return new SettlementServiceAnnouncementHandler($hub, new NullLogger(), $directory);
    }
}
