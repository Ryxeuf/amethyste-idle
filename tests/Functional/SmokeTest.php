<?php

namespace App\Tests\Functional;

use App\Entity\App\Player;
use App\Entity\App\Zone;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Smoke tests: verify that critical routes do not return HTTP 500.
 * Requires a real database with fixtures loaded (TST-01).
 */
class SmokeTest extends WebTestCase
{
    private KernelBrowser $client;

    protected function setUp(): void
    {
        $this->client = static::createClient();

        /** @var EntityManagerInterface $em */
        $em = static::getContainer()->get(EntityManagerInterface::class);
        $user = $em->getRepository(User::class)->findOneBy(['email' => 'remy@amethyste.game']);

        if ($user === null) {
            $this->markTestSkipped('Fixture user remy@amethyste.game not found — run doctrine:fixtures:load first.');
        }

        $this->client->loginUser($user);
    }

    #[DataProvider('gameRoutesProvider')]
    public function testGameRouteDoesNotReturn500(string $url): void
    {
        $this->client->request('GET', $url);
        $statusCode = $this->client->getResponse()->getStatusCode();

        $this->assertLessThan(
            500,
            $statusCode,
            sprintf('Route %s returned HTTP %d', $url, $statusCode),
        );
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function gameRoutesProvider(): iterable
    {
        yield 'zone' => ['/game/zone'];
        yield 'inventory' => ['/game/inventory'];
        yield 'skills' => ['/game/skills'];
        yield 'bestiary' => ['/game/bestiary'];
        yield 'achievements' => ['/game/achievements'];
        yield 'codex' => ['/game/codex'];
        yield 'quests' => ['/game/quests'];
        // Les onglets de l'inventaire sont charges dans un turbo-frame : ce sont
        // des requetes distinctes, et c'est la que vivent ses gabarits.
        yield 'inventory_equipment' => ['/game/inventory/equipment'];
        yield 'inventory_items' => ['/game/inventory/items'];
        yield 'inventory_materia' => ['/game/inventory/materia'];
        yield 'inventory_materials' => ['/game/inventory/materials'];
        yield 'inventory_bank' => ['/game/inventory/bank'];
    }

    /**
     * Les ecrans d'administration passent par le meme garde-fou : un gabarit
     * casse ou une dependance manquante s'y voit en HTTP 500, pas autrement.
     */
    #[DataProvider('adminRoutesProvider')]
    public function testAdminRouteDoesNotReturn500(string $url): void
    {
        $this->client->request('GET', $url);
        $statusCode = $this->client->getResponse()->getStatusCode();

        $this->assertLessThan(
            500,
            $statusCode,
            sprintf('Route %s returned HTTP %d', $url, $statusCode),
        );
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function adminRoutesProvider(): iterable
    {
        yield 'dashboard' => ['/admin'];
        yield 'zones' => ['/admin/zones'];
        yield 'zone_new' => ['/admin/zones/new'];
        yield 'players' => ['/admin/players'];
    }

    /**
     * Fiches liees a une entite reelle : elles ne sont pas atteignables par une
     * URL fixe, et c'est justement la ou vivent les gabarits les plus fournis.
     */
    public function testZoneAndPlayerDetailScreensRender(): void
    {
        /** @var EntityManagerInterface $em */
        $em = static::getContainer()->get(EntityManagerInterface::class);

        $zone = $em->getRepository(Zone::class)->findOneBy([], ['id' => 'ASC']);
        if ($zone === null) {
            $this->markTestSkipped('Aucune zone en base — lancer app:zone:import.');
        }

        foreach ([
            sprintf('/admin/zones/%d', $zone->getId()),
            sprintf('/admin/zones/%d/edit', $zone->getId()),
            sprintf('/admin/zones/%d/connections/new', $zone->getId()),
        ] as $url) {
            $this->client->request('GET', $url);
            $this->assertLessThan(
                500,
                $this->client->getResponse()->getStatusCode(),
                sprintf('Route %s returned HTTP %d', $url, $this->client->getResponse()->getStatusCode()),
            );
        }

        $player = $em->getRepository(Player::class)->findOneBy([], ['id' => 'ASC']);
        if ($player === null) {
            $this->markTestSkipped('Aucun joueur en base.');
        }

        foreach ([
            sprintf('/admin/players/%d', $player->getId()),
            sprintf('/admin/players/%d/teleport', $player->getId()),
        ] as $url) {
            $this->client->request('GET', $url);
            $this->assertLessThan(
                500,
                $this->client->getResponse()->getStatusCode(),
                sprintf('Route %s returned HTTP %d', $url, $this->client->getResponse()->getStatusCode()),
            );
        }
    }

    public function testUnauthenticatedGameAccessRedirects(): void
    {
        // Ensure the kernel is shut down before creating a fresh anonymous client
        static::ensureKernelShutdown();
        $anonymousClient = static::createClient();

        $anonymousClient->request('GET', '/game/zone');
        $statusCode = $anonymousClient->getResponse()->getStatusCode();

        $this->assertTrue(
            $statusCode >= 300 && $statusCode < 400,
            sprintf('Expected redirect (3xx) for unauthenticated access, got HTTP %d', $statusCode),
        );
    }
}
