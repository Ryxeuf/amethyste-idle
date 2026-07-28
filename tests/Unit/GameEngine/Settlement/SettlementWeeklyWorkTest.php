<?php

namespace App\Tests\Unit\GameEngine\Settlement;

use App\Entity\App\Settlement;
use App\Entity\App\SettlementWeeklyWork;
use App\Entity\App\Zone;
use App\Enum\InfluenceActivityType;
use App\Enum\SettlementRank;
use App\Enum\SettlementType;
use App\GameEngine\Settlement\SettlementDefinitionLoader;
use App\GameEngine\Settlement\SettlementWeeklyWorkGenerator;
use App\Repository\SettlementRepository;
use App\Repository\SettlementWeeklyWorkRepository;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

/**
 * Le chantier de la semaine d'un foyer (RET-05).
 *
 * A la maniere de la Restauration d'Ishgard : la maree dit **ou va** la ville, le
 * chantier dit **ce qu'elle attend cette semaine**. Deux proprietes portent le
 * jalon. Ce que la ville demande, **personne ne le choisit** — le type se deduit
 * de l'indice dominant, donc de la frequentation passee. Et l'avancement
 * **s'ecrete a la cible**, contrairement a la commission personnelle : le
 * compteur est collectif, et du depassement empile sur un besoin rempli
 * masquerait ceux qui restent.
 */
class SettlementWeeklyWorkTest extends TestCase
{
    public function testATradingPostAsksForMaterialAndWork(): void
    {
        $needs = $this->needsFor(SettlementType::Trading, SettlementRank::Hamlet);

        self::assertSame(['harvest', 'craft'], array_column($needs, 'activity'));
    }

    public function testABastionAsksThatTheOutskirtsBeHeld(): void
    {
        $needs = $this->needsFor(SettlementType::Bastion, SettlementRank::Hamlet);

        self::assertSame(['mob_kill'], array_column($needs, 'activity'));
    }

    /**
     * Sous le Hameau, un foyer n'a pas encore d'identite (FOY-03). Le laisser
     * sans chantier l'aurait prive du seul rendez-vous collectif qu'un Campement
     * puisse offrir.
     */
    public function testASettlementWithoutATypeStillGetsAWork(): void
    {
        $needs = $this->needsFor(null, SettlementRank::Camp);

        self::assertSame(['harvest'], array_column($needs, 'activity'));
    }

    /**
     * Une grande ville demande davantage. Le multiplicateur porte sur la
     * **cible**, jamais sur la recompense : un chantier de Metropole est plus
     * long, il ne rapporte pas proportionnellement plus a chacun.
     */
    public function testABiggerSettlementAsksForMore(): void
    {
        $hamlet = $this->needsFor(SettlementType::Bastion, SettlementRank::Hamlet);
        $metropolis = $this->needsFor(SettlementType::Bastion, SettlementRank::Metropolis);

        self::assertSame(150, $hamlet[0]['target']);
        self::assertSame(600, $metropolis[0]['target']);
    }

    public function testOnlyTheDemandedActivityAdvancesTheWork(): void
    {
        $work = $this->work([['activity' => 'mob_kill', 'target' => 10, 'progress' => 0]]);

        self::assertSame(0, $work->contribute(InfluenceActivityType::Fishing, 5));
        self::assertSame(5, $work->contribute(InfluenceActivityType::MobKill, 5));
    }

    /**
     * L'ecretage est la difference avec la commission personnelle : le compteur
     * est collectif, et du depassement empile sur un besoin rempli masquerait
     * ceux qui restent — or c'est ce que le chantier existe pour montrer.
     */
    public function testContributionIsClippedToWhatIsStillNeeded(): void
    {
        $work = $this->work([['activity' => 'mob_kill', 'target' => 10, 'progress' => 8]]);

        self::assertSame(2, $work->contribute(InfluenceActivityType::MobKill, 30));
        self::assertSame(10, $work->getNeeds()[0]['progress']);
        self::assertSame(0, $work->contribute(InfluenceActivityType::MobKill, 5));
    }

    public function testAWorkIsCompleteOnlyWhenEveryNeedIsFilled(): void
    {
        $work = $this->work([
            ['activity' => 'harvest', 'target' => 10, 'progress' => 10],
            ['activity' => 'craft', 'target' => 5, 'progress' => 4],
        ]);

        self::assertFalse($work->isComplete());

        $work->contribute(InfluenceActivityType::Craft, 1);
        self::assertTrue($work->isComplete());
    }

    /**
     * La jauge est **ponderee par les cibles** : un besoin de 200 unites et un
     * besoin de 15 ne pesent pas pareil dans l'effort, et les moyenner a poids
     * egal donnerait une barre qui bondit puis stagne.
     */
    public function testTheGaugeWeighsNeedsByTheirTarget(): void
    {
        $work = $this->work([
            ['activity' => 'harvest', 'target' => 90, 'progress' => 0],
            ['activity' => 'quest', 'target' => 10, 'progress' => 10],
        ]);

        self::assertSame(10, $work->getProgressPercent());
    }

    public function testAnEmptyWorkIsNeverComplete(): void
    {
        self::assertFalse($this->work([])->isComplete());
    }

    /**
     * Contrat sur la table livree : **chaque type de foyer** doit avoir une
     * demande, et le sans-type aussi. Un type oublie laisserait ses foyers sans
     * chantier, c'est-a-dire sans le seul rendez-vous collectif qu'ils puissent
     * offrir — et rien ne le dirait.
     */
    public function testTheShippedTableLeavesNoSettlementWithoutAWork(): void
    {
        $demands = (new SettlementDefinitionLoader(\dirname(__DIR__, 4)))->load()['weekly_work']['demands'];

        $expected = array_map(static fn (SettlementType $type): string => $type->value, SettlementType::cases());
        $expected[] = 'none';

        $missing = array_values(array_diff($expected, array_keys($demands)));

        self::assertSame([], $missing, sprintf(
            'Ces types de foyer n\'ont aucune demande declaree : %s.',
            implode(', ', $missing),
        ));
    }

    /**
     * @param list<array{activity: string, target: int, progress: int}> $needs
     */
    private function work(array $needs): SettlementWeeklyWork
    {
        return new SettlementWeeklyWork(new Settlement(new Zone()), '2026-W31', $needs);
    }

    /**
     * @return list<array{activity: string, target: int, progress: int}>
     */
    private function needsFor(?SettlementType $type, SettlementRank $rank): array
    {
        $settlement = new Settlement(new Zone());
        $settlement->setRank($rank);
        $settlement->setType($type);

        $loader = $this->createMock(SettlementDefinitionLoader::class);

        $generator = new SettlementWeeklyWorkGenerator(
            $this->createMock(EntityManagerInterface::class),
            $this->createMock(SettlementRepository::class),
            $this->createMock(SettlementWeeklyWorkRepository::class),
            $loader,
        );

        return $generator->needsFor($settlement, [
            'demands' => [
                'trading' => ['harvest', 'craft'],
                'bastion' => ['mob_kill'],
                'athenaeum' => ['quest'],
                'sanctuary' => ['craft', 'quest'],
                'none' => ['harvest'],
            ],
            'targets' => ['harvest' => 200, 'craft' => 40, 'mob_kill' => 150, 'quest' => 15],
            'rank_multipliers' => ['ruin' => 1, 'camp' => 1, 'hamlet' => 1, 'town' => 2, 'city' => 3, 'metropolis' => 4],
        ]);
    }
}
