<?php

namespace App\Tests\Integration;

use App\Entity\App\Fight;
use App\Entity\App\Map;
use App\Entity\App\Mob;
use App\Entity\App\Player;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * Base class for integration tests using a real database with fixtures.
 *
 * - Schema + fixtures are loaded once per test class (setUpBeforeClass).
 * - Each test runs inside a transaction that is rolled back in tearDown,
 *   ensuring full isolation without the cost of reloading fixtures.
 *
 * Prerequisites: TST-01 (schema + fixtures available in CI).
 */
abstract class AbstractIntegrationTestCase extends KernelTestCase
{
    private static bool $fixturesLoaded = false;

    protected EntityManagerInterface $em;

    /**
     * Boot the kernel once and ensure schema + fixtures are present.
     * This runs once per concrete test class.
     */
    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();

        if (self::$fixturesLoaded) {
            return;
        }

        self::bootKernel();

        /** @var EntityManagerInterface $em */
        $em = self::getContainer()->get(EntityManagerInterface::class);

        // Check if fixtures are already loaded by looking for the fixture user.
        $user = $em->getRepository(User::class)->findOneBy(['email' => 'remy@amethyste.game']);

        if ($user === null) {
            // Schema + fixtures not present — skip the entire class.
            self::markTestSkipped(
                'Fixtures not loaded. Run: docker compose exec php php bin/console doctrine:fixtures:load --env=test --no-interaction'
            );
        }

        self::$fixturesLoaded = true;
        self::ensureKernelShutdown();
    }

    /**
     * Begin a transaction before each test.
     */
    protected function setUp(): void
    {
        self::bootKernel();

        /** @var EntityManagerInterface $em */
        $em = self::getContainer()->get(EntityManagerInterface::class);
        $this->em = $em;

        $this->em->getConnection()->beginTransaction();
    }

    /**
     * Roll back the transaction after each test to restore DB state.
     */
    protected function tearDown(): void
    {
        if ($this->em->getConnection()->isTransactionActive()) {
            $this->em->getConnection()->rollBack();
        }

        parent::tearDown();
    }

    // ---------------------------------------------------------------
    //  Helper methods — fetch fixture entities
    // ---------------------------------------------------------------

    /**
     * Return the main fixture player (remy).
     */
    protected function getPlayer(string $name = 'Rémy'): Player
    {
        $player = $this->em->getRepository(Player::class)->findOneBy(['name' => $name]);
        self::assertNotNull($player, sprintf('Fixture player "%s" not found.', $name));

        return $player;
    }

    /**
     * Return the fixture User entity by email.
     */
    protected function getUser(string $email = 'remy@amethyste.game'): User
    {
        $user = $this->em->getRepository(User::class)->findOneBy(['email' => $email]);
        self::assertNotNull($user, sprintf('Fixture user "%s" not found.', $email));

        return $user;
    }

    /**
     * Return the first Mob found on the given map (or any map if null).
     */
    protected function getMob(?Map $map = null): Mob
    {
        $criteria = ['fight' => null];
        if ($map !== null) {
            $criteria['map'] = $map;
        }

        $mob = $this->em->getRepository(Mob::class)->findOneBy($criteria);
        self::assertNotNull($mob, 'No available mob found in fixtures.');

        return $mob;
    }

    /**
     * Create a Fight between a player and a mob, persisted in DB.
     */
    protected function createFight(Player $player, Mob $mob): Fight
    {
        $fight = new Fight();
        $fight->setInProgress(true);
        $fight->setStep(1);
        $fight->setCreatedAt(new \DateTime());
        $fight->setUpdatedAt(new \DateTime());

        $this->em->persist($fight);

        $player->setFight($fight);
        $mob->setFight($fight);

        $this->em->flush();

        return $fight;
    }

    /**
     * Return a Mob by its monster slug (not in a fight).
     */
    protected function getMobByMonsterSlug(string $slug): Mob
    {
        $mobs = $this->em->getRepository(Mob::class)->findBy(['fight' => null]);

        foreach ($mobs as $mob) {
            if ($mob->getMonster()->getSlug() === $slug) {
                return $mob;
            }
        }

        self::fail(sprintf('No available mob with monster slug "%s" found in fixtures.', $slug));
    }

    /**
     * Return an active fight, or null if none exists.
     */
    protected function getFight(): ?Fight
    {
        return $this->em->getRepository(Fight::class)->findOneBy(['inProgress' => true]);
    }

    /**
     * Return a Map entity by name.
     */
    protected function getMap(string $name = 'Carte de test'): Map
    {
        $map = $this->em->getRepository(Map::class)->findOneBy(['name' => $name]);
        self::assertNotNull($map, sprintf('Fixture map "%s" not found.', $name));

        return $map;
    }

    /**
     * Persist and flush entities within the current transaction.
     */
    protected function persistAndFlush(object ...$entities): void
    {
        foreach ($entities as $entity) {
            $this->em->persist($entity);
        }

        $this->em->flush();
    }

    /**
     * Refresh an entity from the database.
     */
    protected function refresh(object $entity): void
    {
        $this->em->refresh($entity);
    }

    /**
     * Shortcut: get any service from the container.
     *
     * @template T of object
     *
     * @param class-string<T> $serviceId
     *
     * @return T
     */
    protected function getService(string $serviceId): object
    {
        /** @var T $service */
        $service = self::getContainer()->get($serviceId);

        return $service;
    }
}
