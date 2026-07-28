<?php

namespace App\Tests\Unit\GameEngine\Settlement;

use App\Entity\App\Settlement;
use App\Entity\App\Zone;
use App\Entity\App\ZoneConnection;
use App\Enum\SettlementRank;
use App\GameEngine\Settlement\VassalageService;
use App\Repository\SettlementRepository;
use App\Repository\ZoneConnectionRepository;
use PHPUnit\Framework\TestCase;

/**
 * Une grande ville boit la croissance de ses voisines (FOY-09).
 *
 * Trois proprietes portent le jalon, et la premiere est la plus importante :
 * **seule la croissance est plafonnee**. Le vassal garde son marche, son type et
 * son identite — un rang deja tenu n'est jamais retire. C'est la meme regle que
 * la decision A du pilier : on ne reprend pas ce qui a ete acquis, on borne ce
 * qui reste a acquerir.
 *
 * Ensuite : **pas de cascade** — le plafond se lit sur les voisins directs et
 * une seule fois. Et **la liberation est automatique**, parce que le plafond est
 * derive et non stocke : le jour ou la capitale tombe, ses vassales peuvent
 * monter au tick suivant sans qu'aucun champ n'ait a etre remis a zero.
 */
class VassalageServiceTest extends TestCase
{
    /** @var array<string, list<Zone>> */
    private array $graph = [];

    /** @var array<string, Settlement> */
    private array $settlements = [];

    protected function setUp(): void
    {
        $this->graph = [];
        $this->settlements = [];
    }

    public function testAHigherNeighbourCapsGrowthOneRankBelowIt(): void
    {
        $capital = $this->settlement('lumiere', SettlementRank::Metropolis);
        $vassal = $this->settlement('vallons', SettlementRank::Hamlet);
        $this->link('vallons', 'lumiere');

        $service = $this->service();

        self::assertSame($capital, $service->overlordOf($vassal));
        self::assertSame(SettlementRank::City, $service->capFor($vassal));
        self::assertSame(SettlementRank::City, $service->clamp($vassal, SettlementRank::Metropolis));
    }

    /**
     * La propriete centrale : un rang deja tenu n'est jamais retire. Un joueur
     * ne doit pas decouvrir qu'une ville a **recule** parce qu'une autre a
     * grandi.
     */
    public function testARankAlreadyHeldIsNeverTakenAway(): void
    {
        $this->settlement('lumiere', SettlementRank::Town);
        $vassal = $this->settlement('vallons', SettlementRank::City);
        $this->link('vallons', 'lumiere');

        // Lumiere est **sous** le vassal : elle ne domine rien.
        self::assertNull($this->service()->overlordOf($vassal));
        self::assertSame(SettlementRank::City, $this->service()->clamp($vassal, SettlementRank::City));
    }

    /**
     * Meme quand la suzeraine domine, le plafond ne descend pas sous le rang
     * tenu : il borne l'avenir, pas le passe.
     */
    public function testTheCapNeverPushesAVassalBelowItsCurrentRank(): void
    {
        $this->settlement('lumiere', SettlementRank::Hamlet);
        $vassal = $this->settlement('vallons', SettlementRank::Camp);
        $this->link('vallons', 'lumiere');

        // Plafond theorique : Ruine (Hameau - 1 = Campement… donc Campement).
        self::assertSame(SettlementRank::Camp, $this->service()->clamp($vassal, SettlementRank::Town));
    }

    /**
     * Un voisin de **meme rang** ne domine pas. Sans cette regle, deux bourgs
     * voisins se plafonneraient mutuellement au Hameau et aucun des deux ne
     * pourrait plus grandir — un blocage reciproque que rien ne denouerait.
     */
    public function testAnEqualNeighbourDoesNotCap(): void
    {
        $this->settlement('lumiere', SettlementRank::Town);
        $vassal = $this->settlement('vallons', SettlementRank::Town);
        $this->link('vallons', 'lumiere');

        self::assertNull($this->service()->overlordOf($vassal));
        self::assertNull($this->service()->capFor($vassal));
    }

    /**
     * **Pas de cascade** : la metropole ne contraint que ses voisins directs. Le
     * voisin d'un vassal est borne par son propre voisinage, ce qui produit un
     * degrade naturel plutot qu'une onde qui traverse la carte.
     */
    public function testTheCapDoesNotReachBeyondDirectNeighbours(): void
    {
        $this->settlement('lumiere', SettlementRank::Metropolis);
        $this->settlement('vallons', SettlementRank::Hamlet);
        $distant = $this->settlement('dunes', SettlementRank::Camp);
        $this->link('vallons', 'lumiere');
        $this->link('dunes', 'vallons');

        // Dunes ne touche pas Lumiere : son plafond vient des Vallons (Hameau),
        // pas de la metropole.
        $service = $this->service();
        self::assertSame('vallons', $service->overlordOf($distant)?->getZone()->getSlug());
        self::assertSame(SettlementRank::Camp, $service->capFor($distant));
    }

    /**
     * La liberation est automatique : le plafond est **derive**, jamais stocke.
     */
    public function testTheCapLiftsOnItsOwnWhenTheCapitalFalls(): void
    {
        $capital = $this->settlement('lumiere', SettlementRank::Metropolis);
        $vassal = $this->settlement('vallons', SettlementRank::Hamlet);
        $this->link('vallons', 'lumiere');

        self::assertNotNull($this->service()->capFor($vassal));

        $capital->setRank(SettlementRank::Hamlet);

        self::assertNull($this->service()->capFor($vassal), 'Une capitale tombee ne plafonne plus personne.');
    }

    public function testAZoneWithoutNeighbourIsFree(): void
    {
        $lonely = $this->settlement('mer-de-sel', SettlementRank::Camp);

        self::assertNull($this->service()->overlordOf($lonely));
        self::assertSame(SettlementRank::Metropolis, $this->service()->clamp($lonely, SettlementRank::Metropolis));
    }

    /**
     * Une voisine **sans foyer** ne plafonne rien : Lumiere et les Jardins sont
     * batis sur la Voute, et n'ont donc pas de rang a faire valoir.
     */
    public function testANeighbourWithoutASettlementCapsNothing(): void
    {
        $vassal = $this->settlement('vallons', SettlementRank::Hamlet);
        $this->graph['vallons'][] = $this->zone('quartier-des-jardins');

        self::assertNull($this->service()->overlordOf($vassal));
    }

    private function zone(string $slug): Zone
    {
        $zone = new Zone();
        $zone->setSlug($slug);
        $zone->setName($slug);

        return $zone;
    }

    private function settlement(string $slug, SettlementRank $rank): Settlement
    {
        $settlement = new Settlement($this->zone($slug));
        $settlement->setRank($rank);
        $this->settlements[$slug] = $settlement;

        return $settlement;
    }

    private function link(string $from, string $to): void
    {
        $this->graph[$from][] = $this->settlements[$to]->getZone();
    }

    private function service(): VassalageService
    {
        $connections = $this->createMock(ZoneConnectionRepository::class);
        $connections->method('findEnabledFrom')->willReturnCallback(
            function (Zone $zone): array {
                $rows = [];
                foreach ($this->graph[$zone->getSlug()] ?? [] as $neighbour) {
                    $connection = $this->createMock(ZoneConnection::class);
                    $connection->method('getToZone')->willReturn($neighbour);
                    $rows[] = $connection;
                }

                return $rows;
            },
        );

        $settlements = $this->createMock(SettlementRepository::class);
        $settlements->method('findOneByZone')->willReturnCallback(
            fn (Zone $zone): ?Settlement => $this->settlements[$zone->getSlug()] ?? null,
        );

        return new VassalageService($connections, $settlements);
    }
}
