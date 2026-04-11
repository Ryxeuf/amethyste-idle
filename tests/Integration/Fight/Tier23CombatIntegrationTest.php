<?php

namespace App\Tests\Integration\Fight;

use App\Entity\App\Map;
use App\Entity\App\Mob;
use App\Entity\Game\Monster;
use App\GameEngine\Fight\Handler\FightHandler;
use App\GameEngine\Fight\MobActionHandler;
use App\Tests\Integration\AbstractIntegrationTestCase;

/**
 * Sprint 2 — DoD : tests d'integration combat monstres tier 2-3.
 *
 * Verifie que les monstres et boss de la tache 141 sont correctement
 * charges en base, que leurs patterns de combat (phases, resistances
 * elementaires, IA) sont actifs, et qu'un combat contre l'un d'eux
 * peut etre demarre et execute sans erreur via les handlers du moteur.
 */
class Tier23CombatIntegrationTest extends AbstractIntegrationTestCase
{
    /**
     * Slugs couverts par la tache 141 (10 monstres tier 2-3 + 3 boss).
     *
     * @var list<string>
     */
    private const TIER_2_3_MONSTERS = [
        'troll',
        'werewolf',
        'wyvern',
        'cursed_knight',
        'naga',
        'crystal_golem',
        'salamander',
        'undine',
        'sylph',
        'clay_golem',
    ];

    /**
     * @var list<string>
     */
    private const TIER_2_3_BOSSES = [
        'alpha_wolf',
        'will_o_wisp',
        'creeping_shadow',
    ];

    /**
     * Chaque monstre tier 2-3 de la tache 141 est charge en DB avec
     * un niveau >= 3 et une difficulte >= 2.
     */
    public function testTier23MonstersAreLoadedWithValidStats(): void
    {
        $repo = $this->em->getRepository(Monster::class);

        foreach (self::TIER_2_3_MONSTERS as $slug) {
            $monster = $repo->findOneBy(['slug' => $slug]);
            self::assertNotNull(
                $monster,
                sprintf('Monster fixture "%s" should be loaded.', $slug)
            );
            self::assertGreaterThanOrEqual(
                3,
                $monster->getLevel(),
                sprintf('Monster "%s" should be at least level 3 (tier 2+).', $slug)
            );
            self::assertGreaterThanOrEqual(
                2,
                $monster->getDifficulty(),
                sprintf('Monster "%s" should have difficulty >= 2.', $slug)
            );
            self::assertGreaterThan(
                0,
                $monster->getLife(),
                sprintf('Monster "%s" should have positive max life.', $slug)
            );
        }
    }

    /**
     * Les monstres tier 2 (lvl 10-15) ont des resistances elementaires
     * configurees, ce qui les differencie des mobs tier 1 basiques.
     */
    public function testTier2MonstersHaveElementalResistances(): void
    {
        $repo = $this->em->getRepository(Monster::class);
        $tier2Slugs = ['wyvern', 'cursed_knight', 'naga', 'crystal_golem'];

        foreach ($tier2Slugs as $slug) {
            $monster = $repo->findOneBy(['slug' => $slug]);
            self::assertNotNull($monster, sprintf('Monster "%s" not found.', $slug));

            $resistances = $monster->getElementalResistances();
            self::assertNotNull(
                $resistances,
                sprintf('Tier 2 monster "%s" should declare elementalResistances.', $slug)
            );
            self::assertNotEmpty(
                $resistances,
                sprintf('Tier 2 monster "%s" should have at least one resistance.', $slug)
            );
        }
    }

    /**
     * Les 3 boss de zone tier 2-3 sont flagues isBoss et declarent
     * des phases a 100/50/25% HP.
     */
    public function testZoneBossesHaveBossPhases(): void
    {
        $repo = $this->em->getRepository(Monster::class);

        foreach (self::TIER_2_3_BOSSES as $slug) {
            $monster = $repo->findOneBy(['slug' => $slug]);
            self::assertNotNull($monster, sprintf('Boss "%s" should be loaded.', $slug));
            self::assertTrue(
                $monster->isBoss(),
                sprintf('Monster "%s" should be flagged isBoss.', $slug)
            );

            $phases = $monster->getBossPhases();
            self::assertNotNull(
                $phases,
                sprintf('Boss "%s" should declare bossPhases.', $slug)
            );
            self::assertGreaterThanOrEqual(
                3,
                count($phases),
                sprintf('Boss "%s" should have at least 3 phases (100/50/25%% HP).', $slug)
            );

            // La phase 3 doit etre atteinte a 25% HP ou moins
            $lowHpPhase = $monster->getCurrentBossPhase(20);
            self::assertNotNull(
                $lowHpPhase,
                sprintf('Boss "%s" should expose a phase at 20%% HP.', $slug)
            );

            // Et la transition doit etre differente entre 100% et 25%
            $fullHpPhase = $monster->getCurrentBossPhase(100);
            self::assertNotSame(
                $fullHpPhase['name'] ?? null,
                $lowHpPhase['name'] ?? null,
                sprintf('Boss "%s" should change phase name between 100%% and 20%% HP.', $slug)
            );
        }
    }

    /**
     * MonsterRepository contient au moins 10 monstres tier 2+ (niveau >= 3),
     * correspondant a l'exigence de la tache 141.
     */
    public function testAtLeastTenTier2Monsters(): void
    {
        $tier2Count = (int) $this->em->createQueryBuilder()
            ->select('COUNT(m.id)')
            ->from(Monster::class, 'm')
            ->where('m.level >= :level')
            ->setParameter('level', 3)
            ->getQuery()
            ->getSingleScalarResult();

        self::assertGreaterThanOrEqual(
            10,
            $tier2Count,
            'Fixtures should expose at least 10 tier 2+ monsters (level >= 3).'
        );
    }

    /**
     * Un combat peut etre demarre contre un mob tier 2 (wyvern) via
     * FightHandler, le Fight est persiste et les deux parties sont liees.
     */
    public function testStartFightAgainstTier2Wyvern(): void
    {
        $player = $this->getPlayer();
        $mob = $this->findMobBySlug('wyvern', $player->getMap());

        /** @var FightHandler $fightHandler */
        $fightHandler = $this->getService(FightHandler::class);
        $fight = $fightHandler->startFight($player, $mob);

        $this->refresh($player);
        $this->refresh($mob);

        self::assertNotNull($fight->getId());
        self::assertFalse($fight->isTerminated());
        self::assertSame($fight->getId(), $player->getFight()?->getId());
        self::assertSame($fight->getId(), $mob->getFight()?->getId());
        self::assertSame('wyvern', $mob->getMonster()->getSlug());
    }

    /**
     * Demarrer un combat contre un boss initialise le tracking de phase
     * via la metadata `boss_phase_<mobId>` du Fight.
     */
    public function testStartFightAgainstBossInitializesPhaseMetadata(): void
    {
        $player = $this->getPlayer();
        $mob = $this->findMobBySlug('alpha_wolf', $player->getMap());

        /** @var FightHandler $fightHandler */
        $fightHandler = $this->getService(FightHandler::class);
        $fight = $fightHandler->startFight($player, $mob);

        $phaseName = $fight->getMetadataValue('boss_phase_' . $mob->getId());
        self::assertNotNull(
            $phaseName,
            'Starting a fight against a boss should set the boss_phase metadata.'
        );

        $phase = $mob->getMonster()->getCurrentBossPhase(100);
        self::assertNotNull($phase, 'Boss should expose a phase at 100% HP.');
        self::assertSame($phase['name'] ?? null, $phaseName);
    }

    /**
     * MobActionHandler::doAction() execute le tour d'un boss tier 2-3
     * sans exception et retourne une structure de resultat valide.
     */
    public function testMobActionHandlerExecutesBossTurn(): void
    {
        $player = $this->getPlayer();
        $mob = $this->findMobBySlug('will_o_wisp', $player->getMap());

        /** @var FightHandler $fightHandler */
        $fightHandler = $this->getService(FightHandler::class);
        $fight = $fightHandler->startFight($player, $mob);

        /** @var MobActionHandler $mobActionHandler */
        $mobActionHandler = $this->getService(MobActionHandler::class);

        // Doit executer le tour du boss sans exception. La structure de retour
        // est garantie par le type de retour de MobActionHandler::doAction().
        $result = $mobActionHandler->doAction($fight);

        // Aucun message null en cas de succes ; la cle dangerAlert est toujours
        // presente (nullable). On verifie juste que le combat est toujours
        // referencable (le boss n'a pas disparu).
        self::assertNotNull($fight->getId());
        self::assertSame($mob->getFight()?->getId(), $fight->getId());
        // Les messages sont eventuellement vides mais toujours des strings.
        foreach ($result['messages'] as $message) {
            self::assertIsString($message);
        }
    }

    /**
     * Trouver un mob par slug du monstre associe, eventuellement sur une map donnee.
     */
    private function findMobBySlug(string $slug, ?Map $map = null): Mob
    {
        $qb = $this->em->createQueryBuilder()
            ->select('m')
            ->from(Mob::class, 'm')
            ->join('m.monster', 'monster')
            ->where('m.fight IS NULL')
            ->andWhere('m.diedAt IS NULL')
            ->andWhere('monster.slug = :slug')
            ->setParameter('slug', $slug)
            ->setMaxResults(1);

        if ($map !== null) {
            $qb->andWhere('m.map = :map')->setParameter('map', $map);
        }

        $mob = $qb->getQuery()->getOneOrNullResult();
        self::assertNotNull(
            $mob,
            sprintf('No available mob with monster slug "%s" in fixtures.', $slug)
        );

        return $mob;
    }
}
