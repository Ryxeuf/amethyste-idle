<?php

namespace App\Tests\Unit\GameEngine\Zone;

use App\Entity\App\Fight;
use App\Entity\App\Player;
use App\Entity\App\PlayerVisitedZone;
use App\Entity\App\Zone;
use App\Entity\App\ZoneConnection;
use App\Entity\Game\Mount;
use App\Event\Zone\PlayerTraveledEvent;
use App\GameEngine\GameMaster\GameMasterPolicy;
use App\GameEngine\Mount\MountTravelSpeed;
use App\GameEngine\Reputation\HostileConsequenceResolver;
use App\GameEngine\Zone\ZoneTravelException;
use App\GameEngine\Zone\ZoneTravelService;
use App\Repository\PlayerVisitedZoneRepository;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class ZoneTravelServiceTest extends TestCase
{
    private EntityManagerInterface&MockObject $entityManager;
    private PlayerVisitedZoneRepository&MockObject $visitedZoneRepository;
    private EventDispatcherInterface&MockObject $eventDispatcher;
    private HostileConsequenceResolver&MockObject $hostileConsequences;
    private \App\GameEngine\Reputation\ShadowsSmuggling&MockObject $shadowsSmuggling;
    private \App\GameEngine\Reputation\FactionGate&MockObject $factionGate;

    private ZoneTravelService $service;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->visitedZoneRepository = $this->createMock(PlayerVisitedZoneRepository::class);
        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        // Par defaut, aucun surcout : le mock rend 0. Les cas FAC-03 le
        // configurent explicitement.
        $this->hostileConsequences = $this->createMock(HostileConsequenceResolver::class);
        // FAC-08 : la fouille aux portes est muette par defaut — les cas de
        // contrebande la configurent explicitement.
        $this->shadowsSmuggling = $this->createMock(\App\GameEngine\Reputation\ShadowsSmuggling::class);
        // FAC-09 : la porte est ouverte par defaut — une zone sans garde l'est
        // pour tout le monde, et les cinq portes ont leur propre contrat.
        $this->factionGate = $this->createMock(\App\GameEngine\Reputation\FactionGate::class);
        $this->factionGate->method('isOpenFor')->willReturn(true);
        $this->service = new ZoneTravelService($this->entityManager, $this->visitedZoneRepository, $this->eventDispatcher, new MountTravelSpeed(), new GameMasterPolicy(), $this->hostileConsequences, $this->shadowsSmuggling, $this->factionGate);
    }

    private function buildZone(string $slug): Zone
    {
        return (new Zone())->setSlug($slug)->setName(ucfirst($slug));
    }

    /**
     * Un personnage qui a **deja voyage**.
     *
     * ONB-10 offre le premier voyage : sans cela, chaque cas de ce fichier
     * mesurerait la faveur au lieu de la duree qu'il verifie. La faveur a son
     * propre test, plus bas — c'est la le bon endroit pour la mesurer.
     */
    private function buildPlayerIn(Zone $zone): Player
    {
        $player = new Player();
        $player->setCurrentZone($zone);
        $player->spendFirstTravel();

        return $player;
    }

    /**
     * ONB-10 — le premier voyage est offert, et une seule fois.
     *
     * L'acte I fait rejoindre une zone pour y recolter (etape 7) bien avant
     * d'enseigner que le voyage coute du temps reel (etape 9). Sans faveur, la
     * chaine s'arrete sur une attente que rien n'a preparee, juste avant la
     * premiere recolte.
     */
    public function testTheFirstJourneyIsFree(): void
    {
        $from = $this->buildZone('village');
        $to = $this->buildZone('vallons');
        $player = new Player();
        $player->setCurrentZone($from);

        self::assertTrue($player->hasFirstTravelOffer());

        $arrivesAt = $this->service->startTravel($player, new ZoneConnection($from, $to, 600));

        self::assertEqualsWithDelta(time(), $arrivesAt->getTimestamp(), 2, 'Le premier voyage n\'est pas offert.');
        self::assertSame($to, $player->getCurrentZone(), 'Un voyage instantane doit etre regle dans la foulee.');
        self::assertFalse($player->hasFirstTravelOffer(), 'La faveur n\'a pas ete consommee.');
    }

    /**
     * Et le second se paie.
     *
     * La faveur est **le premier voyage**, pas la premiere attente : la garder
     * pour plus tard en ferait une monnaie a optimiser.
     */
    public function testTheSecondJourneyCostsRealTime(): void
    {
        $village = $this->buildZone('village');
        $vallons = $this->buildZone('vallons');
        $foret = $this->buildZone('foret');

        $player = new Player();
        $player->setCurrentZone($village);

        $this->service->startTravel($player, new ZoneConnection($village, $vallons, 240));
        $arrivesAt = $this->service->startTravel($player, new ZoneConnection($vallons, $foret, 300));

        self::assertEqualsWithDelta(time() + 300, $arrivesAt->getTimestamp(), 2);
    }

    /**
     * FAC-08 — la fouille aux portes : chaque depart interroge la contrebande
     * vers la zone d'arrivee. Le service decide seul (ballot en transit,
     * foyer Bastion, tirage) — le voyage, lui, part dans tous les cas.
     */
    public function testTravelInspectsTheGatesForSmuggledCargo(): void
    {
        $from = $this->buildZone('village');
        $to = $this->buildZone('bastion');
        $player = $this->buildPlayerIn($from);

        $this->shadowsSmuggling->expects(self::once())
            ->method('inspectAtGates')
            ->with($player, $to);

        $this->service->startTravel($player, new ZoneConnection($from, $to, 300));
    }

    /**
     * FAC-03 — les fouilles de l'Ordre : Hostile aux Chevaliers, on entre plus
     * lentement dans une zone a foyer Bastion. Un surcout de temps, applique
     * apres la monture, jamais un refus.
     */
    public function testHostileTravelSurchargeLengthensTheJourney(): void
    {
        $from = $this->buildZone('village');
        $to = $this->buildZone('bastion');
        $player = $this->buildPlayerIn($from);

        $this->hostileConsequences->method('travelSurchargePercent')
            ->with($player, $to)
            ->willReturn(50);

        $arrivesAt = $this->service->startTravel($player, new ZoneConnection($from, $to, 300));

        self::assertEqualsWithDelta(time() + 450, $arrivesAt->getTimestamp(), 2, 'Les fouilles doivent majorer la duree de 50 %.');
    }

    /**
     * Le garde-fou de FAC-03 : la surcharge ne bloque jamais le voyage. Une
     * liaison instantanee reste instantanee (0 majore vaut 0), et le voyage
     * part dans tous les cas — l'hostilite ralentit, elle ne ferme pas.
     */
    public function testHostileSurchargeNeverBlocksNorResurrectsInstantTravel(): void
    {
        $from = $this->buildZone('village');
        $to = $this->buildZone('bastion');
        $player = $this->buildPlayerIn($from);

        $this->hostileConsequences->method('travelSurchargePercent')->willReturn(50);

        $arrivesAt = $this->service->startTravel($player, new ZoneConnection($from, $to, 0));

        self::assertEqualsWithDelta(time(), $arrivesAt->getTimestamp(), 2, 'Une liaison instantanee doit le rester, meme Hostile.');
        self::assertSame($to, $player->getCurrentZone(), 'Le voyage doit aboutir : l\'hostilite ne le refuse jamais.');
    }

    /**
     * ONB-10 gagne sur FAC-03 : le premier voyage reste offert, meme Hostile.
     * La faveur d'onboarding est un droit du personnage neuf — et un
     * personnage neuf n'a d'ailleurs aucun moyen legitime d'etre deja Hostile.
     */
    public function testTheFirstJourneyStaysFreeEvenWhenHostile(): void
    {
        $from = $this->buildZone('village');
        $to = $this->buildZone('bastion');
        $player = new Player();
        $player->setCurrentZone($from);

        $this->hostileConsequences->method('travelSurchargePercent')->willReturn(50);

        $arrivesAt = $this->service->startTravel($player, new ZoneConnection($from, $to, 600));

        self::assertEqualsWithDelta(time(), $arrivesAt->getTimestamp(), 2, 'Le premier voyage reste offert, meme Hostile.');
    }

    public function testStartTravelSetsDestinationAndArrival(): void
    {
        $from = $this->buildZone('village');
        $to = $this->buildZone('foret');
        $player = $this->buildPlayerIn($from);
        $connection = new ZoneConnection($from, $to, 300);

        $this->entityManager->expects($this->once())->method('flush');

        $arrivesAt = $this->service->startTravel($player, $connection);

        $this->assertSame($to, $player->getTravelToZone());
        $this->assertSame($arrivesAt, $player->getTravelArrivesAt());
        $this->assertEqualsWithDelta(time() + 300, $arrivesAt->getTimestamp(), 2);
        $this->assertSame($from, $player->getCurrentZone(), 'Le joueur reste dans sa zone tant que le voyage n\'est pas arrive.');
    }

    public function testStartTravelRecordsDepartureSoDurationIsKnown(): void
    {
        $from = $this->buildZone('village');
        $to = $this->buildZone('foret');
        $player = $this->buildPlayerIn($from);

        $arrivesAt = $this->service->startTravel($player, new ZoneConnection($from, $to, 300));

        $startedAt = $player->getTravelStartedAt();
        $this->assertNotNull($startedAt, 'Le depart est horodate : sans lui, la barre de progression n\'a pas de duree totale.');
        $this->assertEqualsWithDelta(time(), $startedAt->getTimestamp(), 2);
        $this->assertSame(300, $arrivesAt->getTimestamp() - $startedAt->getTimestamp());
    }

    public function testStartTravelDepartureAccountsForMountSpeed(): void
    {
        $from = $this->buildZone('village');
        $to = $this->buildZone('foret');
        $player = $this->buildPlayerIn($from);
        $player->setActiveMount((new Mount())->setSpeedBonus(50));

        $arrivesAt = $this->service->startTravel($player, new ZoneConnection($from, $to, 300));

        $startedAt = $player->getTravelStartedAt();
        $this->assertNotNull($startedAt);
        // La duree totale lue par la barre est celle reellement subie, pas celle du graphe.
        $this->assertSame(200, $arrivesAt->getTimestamp() - $startedAt->getTimestamp());
    }

    public function testActiveMountShortensTravel(): void
    {
        $from = $this->buildZone('village');
        $to = $this->buildZone('foret');
        $player = $this->buildPlayerIn($from);
        $player->setActiveMount((new Mount())->setSpeedBonus(50));
        $connection = new ZoneConnection($from, $to, 300);

        $arrivesAt = $this->service->startTravel($player, $connection);

        // +50 % de vitesse : 300 * 100/150 = 200 s.
        $this->assertEqualsWithDelta(time() + 200, $arrivesAt->getTimestamp(), 2);
        $this->assertSame(300, $connection->getTravelSeconds(), 'La duree de reference de la connexion n\'est pas alteree.');
    }

    public function testActiveMountDoesNotResurrectInstantConnections(): void
    {
        $from = $this->buildZone('village');
        $to = $this->buildZone('taverne');
        $player = $this->buildPlayerIn($from);
        $player->setActiveMount((new Mount())->setSpeedBonus(75));

        $this->service->startTravel($player, new ZoneConnection($from, $to, 0));

        $this->assertSame($to, $player->getCurrentZone());
        $this->assertFalse($player->isTraveling());
    }

    public function testInstantConnectionArrivesImmediately(): void
    {
        $from = $this->buildZone('village');
        $to = $this->buildZone('taverne');
        $player = $this->buildPlayerIn($from);
        $connection = new ZoneConnection($from, $to, 0);

        $this->entityManager->expects($this->once())->method('persist')->with($this->isInstanceOf(PlayerVisitedZone::class));
        $this->entityManager->expects($this->once())->method('flush');

        $this->service->startTravel($player, $connection);

        $this->assertSame($to, $player->getCurrentZone());
        $this->assertFalse($player->isTraveling());
        $this->assertNull($player->getTravelArrivesAt());
    }

    public function testRefusesWhenAlreadyTraveling(): void
    {
        $from = $this->buildZone('village');
        $player = $this->buildPlayerIn($from);
        $player->setTravelToZone($this->buildZone('foret'));
        $player->setTravelArrivesAt(new \DateTimeImmutable('+5 minutes'));

        $this->expectExceptionMessage('game.zone.travel.error.already_traveling');
        $this->service->startTravel($player, new ZoneConnection($from, $this->buildZone('mines'), 60));
    }

    public function testRefusesDuringFight(): void
    {
        $from = $this->buildZone('village');
        $player = $this->buildPlayerIn($from);
        $player->setFight($this->createMock(Fight::class));

        $this->expectExceptionMessage('game.zone.travel.error.in_fight');
        $this->service->startTravel($player, new ZoneConnection($from, $this->buildZone('foret'), 60));
    }

    public function testRefusesConnectionFromAnotherZone(): void
    {
        $player = $this->buildPlayerIn($this->buildZone('village'));
        $elsewhere = new ZoneConnection($this->buildZone('mines'), $this->buildZone('crete'), 60);

        $this->expectExceptionMessage('game.zone.travel.error.wrong_origin');
        $this->service->startTravel($player, $elsewhere);
    }

    public function testRefusesDisabledConnection(): void
    {
        $from = $this->buildZone('village');
        $player = $this->buildPlayerIn($from);
        $connection = (new ZoneConnection($from, $this->buildZone('foret'), 60))->setEnabled(false);

        $this->expectExceptionMessage('game.zone.travel.error.unavailable');
        $this->service->startTravel($player, $connection);
    }

    public function testRefusesUndiscoveredFastLink(): void
    {
        $from = $this->buildZone('village');
        $to = $this->buildZone('crete');
        $player = $this->buildPlayerIn($from);
        $connection = (new ZoneConnection($from, $to, 60))->setRequiresDiscovery(true);

        $this->visitedZoneRepository->method('hasVisited')->with($player, $to)->willReturn(false);

        $this->expectExceptionMessage('game.zone.travel.error.not_discovered');
        $this->service->startTravel($player, $connection);
    }

    /**
     * FAC-09 — une porte fermee refuse, et **elle refuse sans se nommer**.
     *
     * Le message est celui d'« indisponible », pas un « il vous faut etre
     * Exalte chez les Ruelles » : une porte cachee qui se nomme en se refusant
     * n'est plus cachee, et un joueur qui n'a rien gagne apprendrait a la fois
     * qu'une Cour des Miracles existe et ou elle se trouve.
     */
    public function testAClosedDoorRefusesWithoutNamingItself(): void
    {
        $from = $this->buildZone('village');
        $to = $this->buildZone('la-cour-des-miracles');
        $player = $this->buildPlayerIn($from);

        $gate = $this->createMock(\App\GameEngine\Reputation\FactionGate::class);
        $gate->method('isOpenFor')->willReturn(false);
        $service = new ZoneTravelService($this->entityManager, $this->visitedZoneRepository, $this->eventDispatcher, new MountTravelSpeed(), new GameMasterPolicy(), $this->hostileConsequences, $this->shadowsSmuggling, $gate);

        $this->expectExceptionMessage('game.zone.travel.error.unavailable');
        $service->startTravel($player, new ZoneConnection($from, $to, 0));
    }

    public function testAllowsFastLinkTowardsVisitedZone(): void
    {
        $from = $this->buildZone('village');
        $to = $this->buildZone('crete');
        $player = $this->buildPlayerIn($from);
        $connection = (new ZoneConnection($from, $to, 120))->setRequiresDiscovery(true);

        $this->visitedZoneRepository->method('hasVisited')->willReturn(true);

        $this->service->startTravel($player, $connection);

        $this->assertSame($to, $player->getTravelToZone());
    }

    public function testSettleArrivalIsNoOpBeforeArrivalTime(): void
    {
        $from = $this->buildZone('village');
        $player = $this->buildPlayerIn($from);
        $player->setTravelToZone($this->buildZone('foret'));
        $player->setTravelArrivesAt(new \DateTimeImmutable('+10 minutes'));

        $this->assertNull($this->service->settleArrival($player));
        $this->assertTrue($player->isTraveling());
        $this->assertSame($from, $player->getCurrentZone());
    }

    public function testSettleArrivalMovesPlayerAndRecordsDiscovery(): void
    {
        $from = $this->buildZone('village');
        $to = $this->buildZone('foret');
        $player = $this->buildPlayerIn($from);
        $player->setTravelToZone($to);
        $player->setTravelStartedAt(new \DateTimeImmutable('-5 minutes'));
        $player->setTravelArrivesAt(new \DateTimeImmutable('-1 second'));

        $this->visitedZoneRepository->method('hasVisited')->willReturn(false);
        $this->entityManager->expects($this->once())->method('persist')->with($this->isInstanceOf(PlayerVisitedZone::class));
        $this->entityManager->expects($this->once())->method('flush');

        $this->assertSame($to, $this->service->settleArrival($player));
        $this->assertSame($to, $player->getCurrentZone());
        $this->assertFalse($player->isTraveling());
        $this->assertNull($player->getTravelArrivesAt());
        $this->assertNull($player->getTravelStartedAt(), 'Le depart est efface avec le reste du voyage.');
    }

    public function testSettleArrivalDispatchesPlayerTraveledEvent(): void
    {
        $from = $this->buildZone('village');
        $to = $this->buildZone('foret');
        $player = $this->buildPlayerIn($from);
        $player->setTravelToZone($to);
        $player->setTravelArrivesAt(new \DateTimeImmutable('-1 second'));

        $this->visitedZoneRepository->method('hasVisited')->willReturn(true);

        $dispatched = [];
        $this->eventDispatcher->method('dispatch')
            ->willReturnCallback(function (object $event) use (&$dispatched) {
                $dispatched[] = $event;

                return $event;
            });

        $this->service->settleArrival($player);

        $traveled = array_values(array_filter($dispatched, fn ($e) => $e instanceof PlayerTraveledEvent));
        $this->assertCount(1, $traveled, 'Une arrivee emet exactement un PlayerTraveledEvent.');
        $this->assertSame($player, $traveled[0]->getPlayer());
        $this->assertSame($to, $traveled[0]->getZone());
        $this->assertSame($from, $traveled[0]->getFromZone());
    }

    public function testNoTravelEventWhenArrivalTimeNotReached(): void
    {
        $player = $this->buildPlayerIn($this->buildZone('village'));
        $player->setTravelToZone($this->buildZone('foret'));
        $player->setTravelArrivesAt(new \DateTimeImmutable('+10 minutes'));

        $this->eventDispatcher->expects($this->never())->method('dispatch');

        $this->service->settleArrival($player);
    }

    public function testSettleArrivalReturnsNullWhenNotTraveling(): void
    {
        $this->assertNull($this->service->settleArrival($this->buildPlayerIn($this->buildZone('village'))));
    }

    public function testMarkZoneVisitedIsIdempotent(): void
    {
        $zone = $this->buildZone('village');
        $player = $this->buildPlayerIn($zone);

        $this->visitedZoneRepository->method('hasVisited')->willReturn(true);
        $this->entityManager->expects($this->never())->method('persist');

        $this->service->markZoneVisited($player, $zone);
    }

    /**
     * MJ : le voyage est instantane — l'arrivee est reglee dans la foulee, par
     * le meme chemin que les liaisons de duree nulle.
     */
    public function testGameMasterTravelsInstantly(): void
    {
        $from = $this->buildZone('village');
        $to = $this->buildZone('foret');
        $player = $this->buildPlayerIn($from);
        $player->setGameMaster(true);
        $connection = new ZoneConnection($from, $to, 1800);

        $this->service->startTravel($player, $connection);

        $this->assertSame($to, $player->getCurrentZone());
        $this->assertNull($player->getTravelToZone());
        $this->assertNull($player->getTravelArrivesAt());
    }

    /**
     * MJ : une zone jamais decouverte n'est pas un refus — c'est justement ce
     * qu'il doit pouvoir aller voir.
     */
    public function testGameMasterIgnoresDiscoveryRequirement(): void
    {
        $from = $this->buildZone('village');
        $to = $this->buildZone('foret');
        $player = $this->buildPlayerIn($from);
        $player->setGameMaster(true);
        $connection = (new ZoneConnection($from, $to, 300))->setRequiresDiscovery(true);

        $this->visitedZoneRepository->method('hasVisited')->willReturn(false);

        $this->service->startTravel($player, $connection);

        $this->assertSame($to, $player->getCurrentZone());
    }

    /**
     * MJ : une liaison desactivee reste franchissable — c'est le contenu en
     * preparation qu'il vient inspecter.
     */
    public function testGameMasterTravelsThroughDisabledConnection(): void
    {
        $from = $this->buildZone('village');
        $to = $this->buildZone('foret');
        $player = $this->buildPlayerIn($from);
        $player->setGameMaster(true);
        $connection = (new ZoneConnection($from, $to, 300))->setEnabled(false);

        $this->service->startTravel($player, $connection);

        $this->assertSame($to, $player->getCurrentZone());
    }

    /**
     * Les deux refus qui restent valent pour tout le monde : on ne part pas
     * d'un combat.
     */
    public function testGameMasterStillCannotTravelWhileFighting(): void
    {
        $from = $this->buildZone('village');
        $player = $this->buildPlayerIn($from);
        $player->setGameMaster(true);
        $player->setFight(new Fight());

        $this->expectException(ZoneTravelException::class);

        $this->service->startTravel($player, new ZoneConnection($from, $this->buildZone('foret'), 300));
    }
}
