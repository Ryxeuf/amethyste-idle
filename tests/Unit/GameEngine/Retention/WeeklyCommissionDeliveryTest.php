<?php

namespace App\Tests\Unit\GameEngine\Retention;

use App\Entity\App\Player;
use App\Entity\App\PlayerWeeklyCommission;
use App\Entity\App\Settlement;
use App\Entity\App\Zone;
use App\Enum\InfluenceActivityType;
use App\Enum\WeeklyCommissionReward;
use App\Enum\WeeklyCommissionStatus;
use App\GameEngine\Retention\WeeklyCommissionDelivery;
use App\GameEngine\Retention\WeeklyCommissionException;
use App\GameEngine\Retention\WeeklyCommissionTemplateLoader;
use App\GameEngine\Settlement\SettlementDepositService;
use App\Repository\PlayerWeeklyCommissionRepository;
use App\Repository\SettlementRepository;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

/**
 * La livraison de la commission au foyer (RET-02b).
 *
 * Trois proprietes portent la brique. **Il faut y aller** : sans l'exigence de
 * presence, la zone de livraison ne serait qu'une decoration sur une carte.
 * **Le refus n'est jamais muet** : chaque blocage rend une clef de traduction,
 * parce qu'un bouton grise sans explication fait croire a un bug. Et **le Tribut
 * remplace, il n'ajoute pas** : le joueur ne donne pas en plus de sa
 * recompense, il donne a la place — c'est la seule option qui pese sur le
 * chantier collectif.
 */
class WeeklyCommissionDeliveryTest extends TestCase
{
    private Player $player;
    private Zone $deliveryZone;
    private ?PlayerWeeklyCommission $commission = null;
    private ?Settlement $settlement = null;
    private float $lastMultiplier = 0.0;

    protected function setUp(): void
    {
        $this->deliveryZone = new Zone();
        $this->deliveryZone->setSlug('mines-profondes');
        (new \ReflectionProperty(Zone::class, 'id'))->setValue($this->deliveryZone, 3);

        $this->player = new Player();
        $this->player->setCurrentZone($this->deliveryZone);
        $this->player->setGils(0);
        $this->player->setActionEnergy(10);

        $this->settlement = new Settlement($this->deliveryZone);
        $this->commission = $this->completeCommission();
        $this->lastMultiplier = 0.0;
    }

    public function testAFinishedCommissionDeliveredOnSiteIsAccepted(): void
    {
        self::assertNull($this->delivery()->blocker($this->player));
    }

    public function testAnUnfinishedCommissionCannotBeDelivered(): void
    {
        $this->commission = new PlayerWeeklyCommission($this->player, '2026-W31', 'slug', InfluenceActivityType::Quest, 5);
        $this->commission->setDeliveryZone($this->deliveryZone);

        self::assertSame('game.commission.error.incomplete', $this->delivery()->blocker($this->player));
    }

    /**
     * Sans cette exigence, la zone de livraison ne serait qu'une decoration sur
     * une carte, et le rendez-vous n'aurait pas de lieu.
     */
    public function testACommissionCannotBeDeliveredFromElsewhere(): void
    {
        $elsewhere = new Zone();
        (new \ReflectionProperty(Zone::class, 'id'))->setValue($elsewhere, 9);
        $this->player->setCurrentZone($elsewhere);

        self::assertSame('game.commission.error.elsewhere', $this->delivery()->blocker($this->player));
    }

    public function testAnAlreadyDeliveredCommissionIsRefused(): void
    {
        $this->commission?->setStatus(WeeklyCommissionStatus::Delivered);

        self::assertSame('game.commission.error.closed', $this->delivery()->blocker($this->player));
    }

    public function testACommissionWithoutADeliveryZoneWaitsRatherThanFails(): void
    {
        $this->commission?->setDeliveryZone(null);

        self::assertSame('game.commission.error.no_settlement', $this->delivery()->blocker($this->player));
    }

    public function testTheAbsenceOfACommissionIsSaidPlainly(): void
    {
        $this->commission = null;

        self::assertSame('game.commission.error.none', $this->delivery()->blocker($this->player));
    }

    public function testThePurseGivesGilsAndClosesTheCommission(): void
    {
        $result = $this->delivery()->deliver($this->player, WeeklyCommissionReward::Purse);

        self::assertSame(2500, $result->gils);
        self::assertSame(2500, $this->player->getGils());
        self::assertSame(1.0, $this->lastMultiplier);
        self::assertSame(WeeklyCommissionStatus::Delivered, $this->commission?->getStatus());
        self::assertSame(WeeklyCommissionReward::Purse, $this->commission?->getReward());
    }

    /**
     * L'energie rendue est bornee par le plafond du personnage, et le resultat
     * rapporte ce qui a **reellement** ete rendu : annoncer 120 points a un
     * joueur qui en recoit 30 ferait passer la recompense pour un bug.
     */
    public function testTheRallyNeverOverfillsTheEnergyPool(): void
    {
        $this->player->setActionEnergy($this->player->getMaxActionEnergy() - 30);

        $result = $this->delivery()->deliver($this->player, WeeklyCommissionReward::Vigour);

        self::assertSame(30, $result->energy);
        self::assertSame($this->player->getMaxActionEnergy(), $this->player->getActionEnergy());
    }

    public function testTheTributeReplacesTheRewardInsteadOfAddingToIt(): void
    {
        $result = $this->delivery()->deliver($this->player, WeeklyCommissionReward::Tribute);

        self::assertSame(3.0, $this->lastMultiplier);
        self::assertSame(0, $result->gils);
        self::assertSame(0, $result->energy);
        self::assertSame(0, $this->player->getGils());
    }

    public function testARefusedDeliveryThrowsWithItsReason(): void
    {
        $this->commission?->setStatus(WeeklyCommissionStatus::Delivered);

        $this->expectException(WeeklyCommissionException::class);
        $this->expectExceptionMessage('game.commission.error.closed');

        $this->delivery()->deliver($this->player, WeeklyCommissionReward::Purse);
    }

    /**
     * Contrat sur la configuration livree.
     *
     * A un multiplicateur de 1, le Tribut ne donnerait rien de plus au foyer que
     * la bourse : le joueur renoncerait a sa part pour rien, et l'option qui
     * porte tout le sens collectif de la commission deviendrait un piege. Le
     * chargeur l'interdit ; ce test verifie que le fichier livre en profite.
     */
    public function testTheShippedRewardsNeverMakeTheTributeAWastedChoice(): void
    {
        $rewards = (new WeeklyCommissionTemplateLoader(\dirname(__DIR__, 4)))->load()['rewards'];

        self::assertGreaterThanOrEqual(2, $rewards['tribute_multiplier']);
        self::assertGreaterThan(0, $rewards['purse_gils']);
        self::assertGreaterThan(0, $rewards['vigour_energy']);
    }

    private function completeCommission(): PlayerWeeklyCommission
    {
        $commission = new PlayerWeeklyCommission($this->player, '2026-W31', 'gibier-de-la-semaine', InfluenceActivityType::MobKill, 40);
        $commission->addProgress(40);
        $commission->setDeliveryZone($this->deliveryZone);

        return $commission;
    }

    private function delivery(): WeeklyCommissionDelivery
    {
        $commissionRepository = $this->createMock(PlayerWeeklyCommissionRepository::class);
        $commissionRepository->method('findCurrent')->willReturnCallback(
            fn (): ?PlayerWeeklyCommission => $this->commission,
        );

        $settlementRepository = $this->createMock(SettlementRepository::class);
        $settlementRepository->method('findOneByZone')->willReturnCallback(
            fn (): ?Settlement => $this->settlement,
        );

        $depositService = $this->createMock(SettlementDepositService::class);
        $depositService->method('deposit')->willReturnCallback(
            function (Player $player, string $action, ?Zone $zone, ?\DateTimeImmutable $now, float $multiplier): int {
                $this->lastMultiplier = $multiplier;

                return (int) (24 * $multiplier);
            },
        );

        $loader = $this->createMock(WeeklyCommissionTemplateLoader::class);
        $loader->method('load')->willReturn([
            'per_week' => 1,
            'rewards' => [
                'purse_gils' => 2500,
                'vigour_energy' => 120,
                'tribute_multiplier' => 3,
            ],
            'commissions' => [],
        ]);

        return new WeeklyCommissionDelivery(
            $commissionRepository,
            $settlementRepository,
            $depositService,
            $loader,
            $this->createMock(EntityManagerInterface::class),
        );
    }
}
