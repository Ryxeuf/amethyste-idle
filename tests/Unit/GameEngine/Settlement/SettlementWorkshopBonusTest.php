<?php

namespace App\Tests\Unit\GameEngine\Settlement;

use App\Entity\App\Settlement;
use App\Entity\App\Zone;
use App\Enum\SettlementRank;
use App\Enum\SettlementType;
use App\GameEngine\Crafting\CraftSpecializationService;
use App\GameEngine\Settlement\SettlementDefinitionLoader;
use App\GameEngine\Settlement\SettlementWorkshopBonus;
use App\Repository\SettlementRepository;
use PHPUnit\Framework\TestCase;

/**
 * On voyage pour crafter (FOY-07).
 *
 * Trois proprietes portent le jalon. Le bonus se lit **sur le lieu**, jamais sur
 * le joueur — sinon il suivrait l'artisan partout et ne creerait aucun
 * arbitrage. Il **plafonne** — sinon un lieu vaudrait plus qu'une specialisation
 * de metier, qui est un choix irreversible paye en experience de domaine. Et il
 * est **nul par defaut** — une zone sans foyer n'est pas penalisee, elle n'est
 * simplement pas avantagee.
 */
class SettlementWorkshopBonusTest extends TestCase
{
    private ?Settlement $settlement = null;
    private Zone $zone;

    protected function setUp(): void
    {
        $this->zone = new Zone();
        $this->zone->setSlug('mines-profondes');
        $this->settlement = null;
    }

    public function testNoZoneMeansNoBonus(): void
    {
        self::assertSame(0, $this->bonus()->bonusFor(null, 'forgeron'));
    }

    /**
     * Lumiere et les Jardins sont batis sur la Voute : rien ne s'y depose, donc
     * rien n'y ameliore un etabli. Le plancher T1 y reste entier (ECO-02).
     */
    public function testAZoneWithoutSettlementGivesNothing(): void
    {
        $this->settlement = null;

        $bonus = $this->bonus()->describe($this->zone, 'forgeron');

        self::assertTrue($bonus->isZero());
        self::assertNull($bonus->settlementRank);
    }

    public function testTheRankAloneImprovesEveryCraft(): void
    {
        $this->settlementAt(SettlementRank::Town, null);

        // Le Bourg vaut 2, et les Mines nourrissent le metal : le forgeron y
        // gagne aussi la part de ligne.
        self::assertSame(2, $this->bonus()->describe($this->zone, 'alchimiste')->rank);
        self::assertSame(0, $this->bonus()->describe($this->zone, 'alchimiste')->line);
    }

    public function testTheProductionLineFavoursTheCraftItFeeds(): void
    {
        $this->settlementAt(SettlementRank::Town, null);

        $bonus = $this->bonus()->describe($this->zone, 'forgeron');

        self::assertSame(2, $bonus->rank);
        self::assertSame(3, $bonus->line);
        self::assertSame(0, $bonus->type);
        self::assertSame(5, $bonus->total);
        self::assertSame('metal', $bonus->productionLine);
    }

    public function testTheTypeFavoursTheCraftItServes(): void
    {
        $this->settlementAt(SettlementRank::Town, SettlementType::Bastion);

        $forge = $this->bonus()->describe($this->zone, 'forgeron');
        self::assertSame(3, $forge->type);

        // Le meme Bastion n'apporte rien de plus a l'alchimiste : le type sert
        // un metier, pas tous.
        self::assertSame(0, $this->bonus()->describe($this->zone, 'alchimiste')->type);
    }

    /**
     * Le plafond doit **mordre** et le dire. Un bonus qui cesse d'augmenter sans
     * explication ferait conclure au joueur — a raison — que faire monter sa
     * ville ne sert plus a rien.
     */
    public function testTheBonusIsCappedAndSaysSo(): void
    {
        $this->settlementAt(SettlementRank::Metropolis, SettlementType::Bastion);

        $bonus = $this->bonus()->describe($this->zone, 'forgeron');

        // 4 (Metropole) + 3 (Bastion) + 3 (metal) = 10, plafonne a 8.
        self::assertSame(8, $bonus->total);
        self::assertTrue($bonus->capped);
    }

    public function testABonusUnderTheCapIsNotFlaggedAsCapped(): void
    {
        $this->settlementAt(SettlementRank::Hamlet, null);

        self::assertFalse($this->bonus()->describe($this->zone, 'forgeron')->capped);
    }

    /**
     * Une zone sans ligne declaree ne fait pas tomber le calcul : elle n'apporte
     * que son rang. Le cas se produira a chaque zone neuve, et le refuser
     * bloquerait la livraison de contenu pour une raison de calibrage.
     */
    public function testAZoneWithoutADeclaredLineStillGetsItsRank(): void
    {
        $this->zone->setSlug('zone-neuve-sans-ligne');
        $this->settlementAt(SettlementRank::City, null);

        $bonus = $this->bonus()->describe($this->zone, 'forgeron');

        self::assertSame(3, $bonus->total);
        self::assertNull($bonus->productionLine);
    }

    /**
     * La table livree ne doit jamais laisser un lieu valoir plus qu'une
     * carriere : le cumul maximal atteignable reste sous le plafond, et le
     * plafond lui-meme reste sous le bonus de specialisation, qui se paie d'un
     * choix irreversible.
     */
    public function testTheShippedTableNeverLetsAPlaceOutweighACareer(): void
    {
        $workshop = (new SettlementDefinitionLoader(\dirname(__DIR__, 4)))->load()['workshop'];

        self::assertGreaterThan(0, $workshop['cap']);
        self::assertLessThanOrEqual(
            CraftSpecializationService::QUALITY_BONUS_CHANCE,
            $workshop['cap'],
            'Le plafond du bonus d\'atelier depasse la specialisation de metier : un lieu vaudrait plus qu\'un choix irreversible.',
        );
    }

    /**
     * Chaque zone du monde livre qui porte un foyer doit nommer une ligne de
     * production. En omettre une ne casse rien — et c'est le probleme : la zone
     * n'accorderait que son rang, sans que rien ne le signale.
     */
    public function testEverySeededZoneDeclaresItsProductionLine(): void
    {
        $definition = (new SettlementDefinitionLoader(\dirname(__DIR__, 4)))->load();

        $missing = array_values(array_diff(
            array_keys($definition['seed']),
            array_keys($definition['workshop']['zone_line']),
        ));

        self::assertSame([], $missing, sprintf(
            'Ces zones ont un foyer mais aucune ligne de production : %s.',
            implode(', ', $missing),
        ));
    }

    private function settlementAt(SettlementRank $rank, ?SettlementType $type): void
    {
        $this->settlement = new Settlement($this->zone);
        $this->settlement->setRank($rank);
        $this->settlement->setType($type);
    }

    private function bonus(): SettlementWorkshopBonus
    {
        $repository = $this->createMock(SettlementRepository::class);
        $repository->method('findOneByZone')->willReturnCallback(
            fn (Zone $zone): ?Settlement => $zone === $this->zone ? $this->settlement : null,
        );

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
            'seed' => [],
            'without_settlement' => [],
            'workshop' => [
                'rank_bonus' => ['hamlet' => 1, 'town' => 2, 'city' => 3, 'metropolis' => 4],
                'type_bonus' => [
                    'trading' => ['tanneur' => 3],
                    'bastion' => ['forgeron' => 3],
                    'athenaeum' => ['alchimiste' => 3],
                    'sanctuary' => ['joaillier' => 3],
                ],
                'line_bonus' => [
                    'metal' => ['forgeron' => 3],
                    'leather' => ['tanneur' => 3],
                    'stone' => ['joaillier' => 3],
                    'water' => ['alchimiste' => 3],
                    'wood' => [],
                    'amethyst' => [],
                ],
                'cap' => 8,
                'zone_line' => ['mines-profondes' => 'metal'],
            ],
        ]);

        return new SettlementWorkshopBonus($repository, $loader);
    }
}
