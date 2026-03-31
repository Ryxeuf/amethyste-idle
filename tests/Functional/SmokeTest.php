<?php

namespace App\Tests\Functional;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
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

    /**
     * @dataProvider gameRoutesProvider
     */
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
        yield 'map' => ['/game/map'];
        yield 'inventory' => ['/game/inventory'];
        yield 'skills' => ['/game/skills'];
        yield 'bestiary' => ['/game/bestiary'];
        yield 'achievements' => ['/game/achievements'];
        yield 'quests' => ['/game/quests'];
    }

    public function testApiMapConfigReturnsJson(): void
    {
        $this->client->request('GET', '/api/map/config');
        $response = $this->client->getResponse();

        $this->assertLessThan(500, $response->getStatusCode(), '/api/map/config returned HTTP ' . $response->getStatusCode());
        $this->assertJson($response->getContent());
    }

    public function testUnauthenticatedUserIsRedirectedToLogin(): void
    {
        $anonymousClient = static::createClient();
        $anonymousClient->request('GET', '/game/map');

        $this->assertResponseRedirects();
    }
}
