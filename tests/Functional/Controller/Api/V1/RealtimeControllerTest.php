<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller\Api\V1;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Migration API-first phase 0.4 — GET /api/v1/realtime/config.
 */
class RealtimeControllerTest extends WebTestCase
{
    public function testUnauthenticatedRequestReturnsJson401Envelope(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/v1/realtime/config');

        $response = $client->getResponse();
        $this->assertSame(401, $response->getStatusCode());

        $payload = json_decode((string) $response->getContent(), true);
        $this->assertFalse($payload['success']);
        $this->assertSame('unauthenticated', $payload['error']['code']);
    }

    public function testAuthenticatedRequestReturnsRealtimeConfig(): void
    {
        $client = static::createClient();

        /** @var EntityManagerInterface $em */
        $em = static::getContainer()->get(EntityManagerInterface::class);
        $user = $em->getRepository(User::class)->findOneBy(['email' => 'remy@amethyste.game']);

        if ($user === null) {
            $this->markTestSkipped('Fixture user remy@amethyste.game not found — run doctrine:fixtures:load first.');
        }

        $client->loginUser($user);
        $client->request('GET', '/api/v1/realtime/config');

        $response = $client->getResponse();
        $this->assertSame(200, $response->getStatusCode());

        $payload = json_decode((string) $response->getContent(), true);
        $this->assertTrue($payload['success']);

        $data = $payload['data'];
        $this->assertNotEmpty($data['hubUrl']);

        foreach (['map', 'chat', 'notifications', 'events', 'fight'] as $section) {
            $this->assertArrayHasKey($section, $data['topics']);
        }

        $this->assertContains('map/move', $data['topics']['map']);
        $this->assertContains('chat/global', $data['topics']['chat']);
        $this->assertNotEmpty($data['topics']['notifications']);

        // Secret CI >= 256 bits : le token subscriber doit etre emis (JWT a 3 segments)
        if ($data['subscriberToken'] !== null) {
            $this->assertCount(3, explode('.', $data['subscriberToken']));
        }
    }
}
