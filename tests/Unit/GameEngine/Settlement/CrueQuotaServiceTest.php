<?php

namespace App\Tests\Unit\GameEngine\Settlement;

use App\Entity\App\Settlement;
use App\Entity\App\Zone;
use App\Enum\SettlementRank;
use App\GameEngine\Settlement\CrueQuotaService;
use App\GameEngine\Settlement\SettlementDefinitionLoader;
use App\GameEngine\World\WorldLoadService;
use App\Repository\SettlementRepository;
use PHPUnit\Framework\TestCase;

/**
 * La Crue (FOY-08) — combien de grandes villes le monde peut porter.
 *
 * **Decision B du socle de monde.** Sans quota, tout le monde monte tout et il
 * n'y a pas d'enjeu de territoire : c'est la seule mecanique qui rend le pilier
 * des foyers *politique*, et elle y parvient sans un coup echange.
 *
 * La propriete qui compte le plus n'est pas le refus : c'est que **le sediment
 * n'est jamais perdu**. Le rang se lit sur le sediment, il ne le consomme pas —
 * un foyer en attente monte des qu'une place se libere, sans avoir rien a
 * rattraper.
 */
class CrueQuotaServiceTest extends TestCase
{
    /**
     * L'echelle d'ouverture actee (GAME_WORLD § 13.4) : le 1er Bourg a 40
     * actifs, la 1re Cite a 120, la Metropole a 300.
     */
    public function testTheQuotaFollowsTheAgreedScale(): void
    {
        $service = $this->service(120.0);

        self::assertSame(3, $service->quotaFor(SettlementRank::Town));
        self::assertSame(1, $service->quotaFor(SettlementRank::City));
        self::assertSame(0, $service->quotaFor(SettlementRank::Metropolis));
    }

    /**
     * Les petits rangs ne sont pas contingentes, et c'est delibere : la Crue
     * borne les grandes villes, pas le droit d'exister.
     */
    public function testSmallRanksAreNeverCapped(): void
    {
        $service = $this->service(0.0);

        self::assertNull($service->quotaFor(SettlementRank::Camp));
        self::assertNull($service->quotaFor(SettlementRank::Hamlet));
        self::assertTrue($service->allows($this->settlement('vallons'), SettlementRank::Hamlet));
    }

    /**
     * A 50 actifs, le monde a droit a **un** Bourg. C'est l'unique enjeu
     * territorial du serveur, et c'est voulu.
     */
    public function testAFullQuotaRefusesTheSecondTown(): void
    {
        $service = $this->service(50.0, [$this->settlement('mines', SettlementRank::Town)]);

        self::assertFalse($service->allows($this->settlement('vallons'), SettlementRank::Town));
    }

    public function testTheHolderOfAPlaceDoesNotBlockItself(): void
    {
        $mines = $this->settlement('mines', SettlementRank::Town);
        $service = $this->service(50.0, [$mines]);

        // Le foyer qui occupe deja la place ne se compte pas contre lui-meme :
        // sinon il ne pourrait jamais garder son propre rang.
        self::assertTrue($service->allows($mines, SettlementRank::Town));
    }

    /**
     * Une montee peut franchir plusieurs crans. Le quota s'applique a chacun, et
     * on redescend jusqu'au premier autorise : un foyer qui merite la Cite sans
     * pouvoir l'avoir doit quand meme devenir Bourg si la place existe, sinon il
     * paierait deux fois le succes des autres.
     */
    public function testAForbiddenCityStillBecomesATownWhenThePlaceExists(): void
    {
        $service = $this->service(50.0);

        self::assertSame(
            SettlementRank::Town,
            $service->highestAllowed($this->settlement('vallons'), SettlementRank::City),
        );
    }

    public function testEverythingRefusedFallsBackToTheHighestFreeRank(): void
    {
        $service = $this->service(50.0, [$this->settlement('mines', SettlementRank::Town)]);

        // Le Bourg est pris, la Cite hors quota : il reste le Hameau, qui n'est
        // pas contingente.
        self::assertSame(
            SettlementRank::Hamlet,
            $service->highestAllowed($this->settlement('vallons'), SettlementRank::City),
        );
    }

    /**
     * La competition doit se **voir**. Une attente muette est vecue comme un
     * bug — c'est le risque nomme par le plan.
     */
    public function testTheOccupantsAreNamed(): void
    {
        $service = $this->service(50.0, [
            $this->settlement('mines', SettlementRank::City),
            $this->settlement('foret', SettlementRank::Camp),
        ]);

        $occupants = $service->occupants(SettlementRank::Town);

        self::assertCount(1, $occupants);
        self::assertSame('mines', $occupants[0]->getZone()->getSlug());
    }

    /**
     * Contrat sur la table livree : le quota doit **croitre** avec le rang. Un
     * rang superieur moins exigeant qu'un rang inferieur inverserait l'echelle
     * d'ouverture sans que rien ne le dise.
     */
    public function testTheShippedQuotasIncreaseWithRank(): void
    {
        $quotas = (new SettlementDefinitionLoader(\dirname(__DIR__, 4)))->load()['crue'];

        self::assertNotEmpty($quotas);

        $previous = 0;
        foreach (SettlementRank::ordered() as $rank) {
            if (!isset($quotas[$rank->value])) {
                continue;
            }
            self::assertGreaterThan($previous, $quotas[$rank->value]);
            $previous = $quotas[$rank->value];
        }
    }

    private function settlement(string $zoneSlug, SettlementRank $rank = SettlementRank::Hamlet): Settlement
    {
        $zone = new Zone();
        $zone->setSlug($zoneSlug);
        $zone->setName($zoneSlug);

        $settlement = new Settlement($zone);
        $settlement->setRank($rank);

        return $settlement;
    }

    /**
     * @param list<Settlement> $existing
     */
    private function service(float $population, array $existing = []): CrueQuotaService
    {
        $repository = $this->createMock(SettlementRepository::class);
        $repository->method('findAllRanked')->willReturn($existing);

        $loader = $this->createMock(SettlementDefinitionLoader::class);
        $loader->method('load')->willReturn([
            'ranks' => ['camp' => 150, 'hamlet' => 1200, 'town' => 8000, 'city' => 25000, 'metropolis' => 60000],
            'decay_rate' => 0.02,
            'dominance_margin' => 0.25,
            'sustain_days' => 28,
            'minimum_type_rank' => SettlementRank::Hamlet,
            'sediment' => [],
            'daily_cap_per_player' => 60,
            'diminishing_threshold' => 40,
            'diminishing_factor' => 0.5,
            'grace_days' => 28,
            'rebuild_multiplier' => 2,
            'services' => [],
            'never_gated' => ['shop' => 'boutiques existantes'],
            'workshop' => ['rank_bonus' => [], 'type_bonus' => [], 'line_bonus' => [], 'cap' => 0, 'zone_line' => []],
            'weekly_work' => ['demands' => [], 'targets' => [], 'rank_multipliers' => []],
            'crue' => ['town' => 40, 'city' => 120, 'metropolis' => 300],
            'seed' => [],
            'without_settlement' => [],
        ]);

        $load = $this->createMock(WorldLoadService::class);
        $load->method('effectivePopulation')->willReturn($population);

        return new CrueQuotaService($repository, $loader, $load);
    }
}
