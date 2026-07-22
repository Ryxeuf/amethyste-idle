<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller\Api\V1;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Migration API-first, ecrans meta pack 1 — GET /api/v1/bestiary et
 * GET /api/v1/achievements.
 */
class MetaReadControllersTest extends WebTestCase
{
    /**
     * @return iterable<string, array{string}>
     */
    public static function endpointsProvider(): iterable
    {
        yield 'bestiary' => ['/api/v1/bestiary'];
        yield 'achievements' => ['/api/v1/achievements'];
    }

    #[DataProvider('endpointsProvider')]
    public function testUnauthenticatedRequestReturnsJson401Envelope(string $url): void
    {
        $client = static::createClient();
        $client->request('GET', $url);

        $response = $client->getResponse();
        $this->assertSame(401, $response->getStatusCode());

        $payload = json_decode((string) $response->getContent(), true);
        $this->assertFalse($payload['success']);
        $this->assertSame('unauthenticated', $payload['error']['code']);
    }

    public function testBestiaryPayloadStructure(): void
    {
        $client = $this->createAuthenticatedClient();
        $client->request('GET', '/api/v1/bestiary');

        $response = $client->getResponse();
        $this->assertSame(200, $response->getStatusCode());

        $payload = json_decode((string) $response->getContent(), true);
        $this->assertTrue($payload['success']);

        $data = $payload['data'];
        $this->assertIsInt($data['summary']['discoveredCount']);
        $this->assertIsInt($data['summary']['totalMonsters']);
        $this->assertIsInt($data['summary']['totalKills']);
        $this->assertIsArray($data['entries']);

        if ($data['entries'] !== []) {
            $entry = $data['entries'][0];
            foreach (['monster', 'killCount', 'tier', 'nextTierThreshold'] as $key) {
                $this->assertArrayHasKey($key, $entry);
            }
            $this->assertNotEmpty($entry['monster']['slug']);
        }
    }

    public function testAchievementsPayloadStructure(): void
    {
        $client = $this->createAuthenticatedClient();
        $client->request('GET', '/api/v1/achievements');

        $response = $client->getResponse();
        $this->assertSame(200, $response->getStatusCode());

        $payload = json_decode((string) $response->getContent(), true);
        $this->assertTrue($payload['success']);

        $data = $payload['data'];
        $this->assertIsInt($data['summary']['completed']);
        $this->assertIsInt($data['summary']['totalVisible']);
        $this->assertIsArray($data['categories']);

        foreach ($data['categories'] as $achievements) {
            foreach ($achievements as $achievement) {
                foreach (['slug', 'title', 'progress', 'completed', 'hidden'] as $key) {
                    $this->assertArrayHasKey($key, $achievement);
                }
            }
        }
    }

    private function createAuthenticatedClient(): KernelBrowser
    {
        $client = static::createClient();

        /** @var EntityManagerInterface $em */
        $em = static::getContainer()->get(EntityManagerInterface::class);
        $user = $em->getRepository(User::class)->findOneBy(['email' => 'remy@amethyste.game']);

        if ($user === null) {
            $this->markTestSkipped('Fixture user remy@amethyste.game not found — run doctrine:fixtures:load first.');
        }

        $client->loginUser($user);

        return $client;
    }
}
