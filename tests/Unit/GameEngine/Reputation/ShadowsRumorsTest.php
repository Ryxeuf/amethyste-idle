<?php

namespace App\Tests\Unit\GameEngine\Reputation;

use App\Entity\App\Player;
use App\Entity\App\Pnj;
use App\Entity\App\WeeklyOutcrop;
use App\Entity\App\Zone;
use App\GameEngine\Economy\PurityDrawer;
use App\GameEngine\Reputation\HostileConsequenceResolver;
use App\GameEngine\Reputation\ShadowsApproach;
use App\GameEngine\Reputation\ShadowsMarketCatalog;
use App\GameEngine\Reputation\ShadowsMarketException;
use App\GameEngine\Reputation\ShadowsRumors;
use App\GameEngine\Zone\GatherService;
use App\Repository\WeeklyOutcropRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * La rumeur vraie, et la rumeur empoisonnee (FAC-06).
 *
 * A un client en regle, le reseau vend la verite — l'Affleurement de la
 * semaine, la zone et le filon. A un Hostile de la Confrerie, la rumeur a la
 * meme forme, le meme prix, et une zone qui n'est pas la bonne : elle ne vous
 * attaque pas, elle vous ment (poisoned_rumors, FAC-03).
 */
class ShadowsRumorsTest extends TestCase
{
    private HostileConsequenceResolver&MockObject $hostileConsequences;
    private ShadowsApproach&MockObject $approach;
    private Zone $truthZone;
    private Zone $otherZone;

    protected function setUp(): void
    {
        $this->hostileConsequences = $this->createMock(HostileConsequenceResolver::class);
        $this->approach = $this->createMock(ShadowsApproach::class);
        $this->approach->method('hasMet')->willReturn(true);
        $this->truthZone = (new Zone())->setSlug('marais-brumeux')->setName('Marais Brumeux');
        $this->otherZone = (new Zone())->setSlug('dunes-d-ambre')->setName("Dunes d'Ambre");
    }

    private function rumors(): ShadowsRumors
    {
        $outcropRepository = $this->createMock(WeeklyOutcropRepository::class);
        $outcropRepository->method('findForWeek')
            ->willReturn(new WeeklyOutcrop('2026-W31', $this->truthZone, 'filon-de-fer'));

        $zoneRepository = $this->createMock(EntityRepository::class);
        $zoneRepository->method('findAll')->willReturn([$this->truthZone, $this->otherZone]);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->method('getRepository')->willReturn($zoneRepository);

        return new class(
            $entityManager,
            new ShadowsMarketCatalog(\dirname(__DIR__, 4)),
            $this->approach,
            $this->hostileConsequences,
            $outcropRepository,
            $this->createMock(PurityDrawer::class),
            $this->createMock(GatherService::class),
        ) extends ShadowsRumors {
            protected function roll(int $max): int
            {
                // La rumeur d'Affleurement, et la premiere zone candidate au
                // mensonge : le tirage est fixe, la regle est ce qu'on teste.
                return 1;
            }
        };
    }

    private function counter(): Pnj
    {
        $pnj = $this->createMock(Pnj::class);
        $pnj->method('getSlug')->willReturn('village-veilleur-tancrede');

        return $pnj;
    }

    public function testAClientInGoodStandingBuysTheTruth(): void
    {
        $player = new Player();
        $player->setGils(100);

        $rumor = $this->rumors()->buy($player, $this->counter());

        self::assertSame('game.shadows.rumor.outcrop', $rumor['key']);
        self::assertSame('Marais Brumeux', $rumor['params']['%zone%'], 'Au client en regle, la verite.');
        self::assertSame('filon-de-fer', $rumor['params']['%vein%']);
        self::assertSame(100 - (new ShadowsMarketCatalog(\dirname(__DIR__, 4)))->rumorPriceGils(), $player->getGils(), 'L\'information a un prix.');
    }

    /**
     * FAC-03, poisoned_rumors : meme forme, meme prix, une zone qui n'est
     * pas la bonne. La verite n'est jamais dans une rumeur empoisonnee.
     */
    public function testAHostileBuysALie(): void
    {
        $this->hostileConsequences->method('areRumorsPoisoned')->willReturn(true);
        $player = new Player();
        $player->setGils(100);

        $rumor = $this->rumors()->buy($player, $this->counter());

        self::assertSame('game.shadows.rumor.outcrop', $rumor['key'], 'Le mensonge a la meme forme que l\'information.');
        self::assertNotSame('Marais Brumeux', $rumor['params']['%zone%'], 'A un Hostile, jamais la vraie zone.');
        self::assertSame("Dunes d'Ambre", $rumor['params']['%zone%']);
    }

    public function testAnEmptyPurseBuysNothing(): void
    {
        $player = new Player();
        $player->setGils(0);

        $this->expectException(ShadowsMarketException::class);

        $this->rumors()->buy($player, $this->counter());
    }

    /**
     * Avant le premier contact, le guichet est une echoppe comme une autre :
     * le reseau ne parle pas aux inconnus — et le refus est le meme refus
     * neutre que pour un mauvais guichet, pour ne rien reveler.
     */
    public function testTheNetworkDoesNotSpeakToStrangers(): void
    {
        $approach = $this->createMock(ShadowsApproach::class);
        $approach->method('hasMet')->willReturn(false);
        $this->approach = $approach;

        $player = new Player();
        $player->setGils(100);

        try {
            $this->rumors()->buy($player, $this->counter());
            self::fail('Le reseau ne parle pas aux inconnus.');
        } catch (ShadowsMarketException $e) {
            self::assertSame('game.shadows.rumor.error.counter', $e->getMessage());
        }

        self::assertSame(100, $player->getGils(), 'Le refus ne coute rien.');
    }
}
