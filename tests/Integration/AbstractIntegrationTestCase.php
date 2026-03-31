<?php

namespace App\Tests\Integration;

use App\Entity\App\Fight;
use App\Entity\App\Mob;
use App\Entity\App\Player;
use App\Entity\User;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * Base class for integration tests that need a real database with fixtures.
 *
 * Wraps each test in a transaction that is rolled back after execution,
 * so tests never pollute each other and fixtures stay intact.
 *
 * Prerequisites: schema created and fixtures loaded in the test database (TST-01).
 */
abstract class AbstractIntegrationTestCase extends KernelTestCase
{
    protected EntityManagerInterface $em;

    private Connection $connection;

    protected function setUp(): void
    {
        self::bootKernel();

        $this->em = self::getContainer()->get(EntityManagerInterface::class);
        $this->connection = $this->em->getConnection();

        // Wrap each test in a transaction for isolation
        $this->connection->beginTransaction();
    }

    protected function tearDown(): void
    {
        // Roll back all changes made during the test
        if ($this->connection->isTransactionActive()) {
            $this->connection->rollBack();
        }

        // Clear the entity manager to avoid stale references
        $this->em->clear();

        parent::tearDown();
    }

    // ------------------------------------------------------------------
    // Helpers — entity retrieval from fixtures
    // ------------------------------------------------------------------

    /**
     * Returns the main test player (remy) from fixtures.
     */
    protected function getPlayer(string $name = 'Rémy'): Player
    {
        $player = $this->em->getRepository(Player::class)->findOneBy(['name' => $name]);
        self::assertNotNull($player, sprintf('Fixture player "%s" not found — run doctrine:fixtures:load first.', $name));

        return $player;
    }

    /**
     * Returns the User entity by email.
     */
    protected function getUser(string $email = 'remy@amethyste.game'): User
    {
        $user = $this->em->getRepository(User::class)->findOneBy(['email' => $email]);
        self::assertNotNull($user, sprintf('Fixture user "%s" not found — run doctrine:fixtures:load first.', $email));

        return $user;
    }

    /**
     * Finds a Mob on the player's current map (first available).
     * Returns null if no mob exists on that map.
     */
    protected function getMob(?Player $player = null): ?Mob
    {
        $player ??= $this->getPlayer();
        $map = $player->getMap();
        self::assertNotNull($map, 'Player has no map assigned.');

        return $this->em->getRepository(Mob::class)->findOneBy(['map' => $map]);
    }

    /**
     * Finds the player's current fight, or null if not in combat.
     */
    protected function getFight(?Player $player = null): ?Fight
    {
        $player ??= $this->getPlayer();

        return $player->getFight();
    }

    // ------------------------------------------------------------------
    // Helpers — service retrieval
    // ------------------------------------------------------------------

    /**
     * Shortcut to fetch a service from the container.
     *
     * @template T of object
     *
     * @param class-string<T> $serviceClass
     *
     * @return T
     */
    protected function getService(string $serviceClass): object
    {
        /** @var T $service */
        $service = self::getContainer()->get($serviceClass);
        self::assertNotNull($service, sprintf('Service "%s" not found in the container.', $serviceClass));

        return $service;
    }

    // ------------------------------------------------------------------
    // Helpers — database utilities
    // ------------------------------------------------------------------

    /**
     * Flush and clear the entity manager, then re-fetch an entity by id.
     * Useful to verify persisted state after a service call.
     *
     * @template T of object
     *
     * @param class-string<T> $class
     *
     * @return T|null
     */
    protected function refreshEntity(string $class, int $id): ?object
    {
        $this->em->flush();
        $this->em->clear();

        return $this->em->find($class, $id);
    }
}
