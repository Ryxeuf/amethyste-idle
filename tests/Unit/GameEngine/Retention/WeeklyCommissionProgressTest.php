<?php

namespace App\Tests\Unit\GameEngine\Retention;

use App\Entity\App\Player;
use App\Entity\App\PlayerWeeklyCommission;
use App\Enum\InfluenceActivityType;
use App\GameEngine\Retention\WeeklyCommissionProgress;
use App\Repository\PlayerWeeklyCommissionRepository;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

/**
 * Ce qui fait avancer la commission de la semaine (RET-02b).
 *
 * La propriete qui porte la brique est plus etroite qu'il n'y parait : **seule
 * l'activite demandee compte**. Faire avancer une commission de chasse en
 * pechant reviendrait a dire que la semaine n'attendait rien de precis, et un
 * rendez-vous qui accepte n'importe quoi n'est plus un rendez-vous.
 */
class WeeklyCommissionProgressTest extends TestCase
{
    private Player $player;
    private ?PlayerWeeklyCommission $commission = null;

    protected function setUp(): void
    {
        $this->player = new Player();
        $this->commission = null;
    }

    public function testOnlyTheRequestedActivityAdvancesTheCommission(): void
    {
        $commission = $this->commissionFor(InfluenceActivityType::MobKill, 40);

        $this->progress()->advance($this->player, InfluenceActivityType::Fishing);

        self::assertSame(0, $commission->getProgress());
    }

    public function testTheRequestedActivityAdvancesIt(): void
    {
        $commission = $this->commissionFor(InfluenceActivityType::MobKill, 40);

        $this->progress()->advance($this->player, InfluenceActivityType::MobKill);

        self::assertSame(1, $commission->getProgress());
    }

    /**
     * La recolte avance de la **quantite recoltee**, pas de un. « Prelevez 60
     * unites » se compterait sinon en 60 actions, soit pres d'un jour entier
     * d'energie : le rendez-vous hebdomadaire deviendrait une corvee
     * quotidienne.
     */
    public function testGatheringAdvancesByTheQuantityGathered(): void
    {
        $commission = $this->commissionFor(InfluenceActivityType::Harvest, 60);

        $this->progress()->advance($this->player, InfluenceActivityType::Harvest, 7);

        self::assertSame(7, $commission->getProgress());
    }

    public function testCompletionIsReportedOnceAndOnlyOnce(): void
    {
        $this->commissionFor(InfluenceActivityType::Quest, 2);
        $progress = $this->progress();

        self::assertFalse($progress->advance($this->player, InfluenceActivityType::Quest));
        self::assertTrue($progress->advance($this->player, InfluenceActivityType::Quest));
        // Depasser est normal, mais la completion ne s'annonce pas deux fois :
        // sinon chaque action supplementaire rejouerait la meme nouvelle.
        self::assertFalse($progress->advance($this->player, InfluenceActivityType::Quest));
    }

    /**
     * L'avancement ne s'ecrete pas a l'objectif : savoir de combien on a depasse
     * dit si l'objectif etait bien calibre, et c'est la jauge qui borne
     * l'affichage a 100 %, pas le compteur.
     */
    public function testOvershootIsKept(): void
    {
        $commission = $this->commissionFor(InfluenceActivityType::Harvest, 10);

        $this->progress()->advance($this->player, InfluenceActivityType::Harvest, 25);

        self::assertSame(25, $commission->getProgress());
        self::assertSame(100, $commission->getProgressPercent());
    }

    public function testAPlayerWithoutACommissionIsNotAnError(): void
    {
        $this->commission = null;

        self::assertFalse($this->progress()->advance($this->player, InfluenceActivityType::Craft));
    }

    public function testANonPositiveAmountDoesNothing(): void
    {
        $commission = $this->commissionFor(InfluenceActivityType::Harvest, 10);

        self::assertFalse($this->progress()->advance($this->player, InfluenceActivityType::Harvest, 0));
        self::assertSame(0, $commission->getProgress());
    }

    private function commissionFor(InfluenceActivityType $activity, int $target): PlayerWeeklyCommission
    {
        $this->commission = new PlayerWeeklyCommission($this->player, '2026-W31', 'slug', $activity, $target);

        return $this->commission;
    }

    private function progress(): WeeklyCommissionProgress
    {
        $repository = $this->createMock(PlayerWeeklyCommissionRepository::class);
        $repository->method('findCurrent')->willReturnCallback(
            fn (): ?PlayerWeeklyCommission => $this->commission,
        );

        return new WeeklyCommissionProgress($repository, $this->createMock(EntityManagerInterface::class));
    }
}
