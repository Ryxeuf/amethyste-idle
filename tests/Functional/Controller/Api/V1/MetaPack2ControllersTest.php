<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller\Api\V1;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Migration API-first, ecrans meta pack 2 — GET /api/v1/rankings,
 * /api/v1/factions, /api/v1/mounts.
 */
class MetaPack2ControllersTest extends WebTestCase
{
    /**
     * @return iterable<string, array{string}>
     */
    public static function endpointsProvider(): iterable
    {
        yield 'rankings' => ['/api/v1/rankings'];
        yield 'factions' => ['/api/v1/factions'];
        yield 'mounts' => ['/api/v1/mounts'];
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

    public function testRankingsPayloadStructureAndTabFallback(): void
    {
        $client = $this->createAuthenticatedClient();
        $client->request('GET', '/api/v1/rankings?tab=invalid');

        $response = $client->getResponse();
        $this->assertSame(200, $response->getStatusCode());

        $payload = json_decode((string) $response->getContent(), true);
        $this->assertTrue($payload['success']);

        $data = $payload['data'];
        $this->assertSame('kills', $data['tab']);
        $this->assertSame(['kills', 'quests', 'xp'], $data['tabs']);
        $this->assertIsArray($data['top']);
        $this->assertArrayHasKey('rank', $data['me']);
        $this->assertArrayHasKey('total', $data['me']);

        if ($data['top'] !== []) {
            $entry = $data['top'][0];
            $this->assertSame(1, $entry['rank']);
            $this->assertNotEmpty($entry['player']['name']);
            $this->assertIsInt($entry['total']);
        }
    }

    public function testFactionsPayloadStructure(): void
    {
        $client = $this->createAuthenticatedClient();
        $client->request('GET', '/api/v1/factions');

        $response = $client->getResponse();
        $this->assertSame(200, $response->getStatusCode());

        $payload = json_decode((string) $response->getContent(), true);
        $this->assertTrue($payload['success']);
        $this->assertIsArray($payload['data']['factions']);

        foreach ($payload['data']['factions'] as $faction) {
            foreach (['slug', 'name', 'reputation', 'progressPercent', 'rewards'] as $key) {
                $this->assertArrayHasKey($key, $faction);
            }
        }
    }

    public function testMountsPayloadStructureAndFilterFallback(): void
    {
        $client = $this->createAuthenticatedClient();
        $client->request('GET', '/api/v1/mounts?type=invalid');

        $response = $client->getResponse();
        $this->assertSame(200, $response->getStatusCode());

        $payload = json_decode((string) $response->getContent(), true);
        $this->assertTrue($payload['success']);

        $data = $payload['data'];
        $this->assertSame('', $data['filters']['selected']);
        $this->assertIsArray($data['filters']['available']);
        $this->assertIsInt($data['playerGils']);
        $this->assertIsArray($data['mounts']);

        foreach ($data['mounts'] as $mount) {
            foreach (['slug', 'name', 'obtentionType', 'owned', 'active'] as $key) {
                $this->assertArrayHasKey($key, $mount);
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
