<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller\Api\V1;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Migration API-first phase 4.1 — chat sous /api/v1.
 *
 * Ne teste que des chemins sans envoi reel de message (pas de mutation
 * de l'historique ni de publication Mercure).
 */
class ChatControllerTest extends WebTestCase
{
    public function testUnauthenticatedHistoryReturnsJson401Envelope(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/v1/chat/history/global');

        $response = $client->getResponse();
        $this->assertSame(401, $response->getStatusCode());

        $payload = json_decode((string) $response->getContent(), true);
        $this->assertFalse($payload['success']);
        $this->assertSame('unauthenticated', $payload['error']['code']);
    }

    public function testAuthenticatedHistoryReturnsMessagesEnvelope(): void
    {
        $client = $this->createAuthenticatedClient();
        $client->request('GET', '/api/v1/chat/history/global');

        $response = $client->getResponse();
        $this->assertSame(200, $response->getStatusCode());

        $payload = json_decode((string) $response->getContent(), true);
        $this->assertTrue($payload['success']);
        $this->assertIsArray($payload['data']['messages']);
    }

    public function testAuthenticatedConversationsReturnsEnvelope(): void
    {
        $client = $this->createAuthenticatedClient();
        $client->request('GET', '/api/v1/chat/conversations');

        $response = $client->getResponse();
        $this->assertSame(200, $response->getStatusCode());

        $payload = json_decode((string) $response->getContent(), true);
        $this->assertTrue($payload['success']);
        $this->assertIsArray($payload['data']['conversations']);
    }

    public function testPlayersSearchWithShortQueryReturnsEmptyList(): void
    {
        $client = $this->createAuthenticatedClient();
        $client->request('GET', '/api/v1/chat/players/search?q=a');

        $response = $client->getResponse();
        $this->assertSame(200, $response->getStatusCode());

        $payload = json_decode((string) $response->getContent(), true);
        $this->assertTrue($payload['success']);
        $this->assertSame([], $payload['data']['players']);
    }

    public function testSendWithoutJsonContentTypeIsRejected(): void
    {
        $client = $this->createAuthenticatedClient();
        $client->request('POST', '/api/v1/chat/send', server: ['CONTENT_TYPE' => 'application/x-www-form-urlencoded'], content: 'content=hello');

        $response = $client->getResponse();
        $this->assertSame(400, $response->getStatusCode());

        $payload = json_decode((string) $response->getContent(), true);
        $this->assertFalse($payload['success']);
        $this->assertSame('bad_request', $payload['error']['code']);
        $this->assertStringContainsString('Content-Type', $payload['error']['message']);
    }

    public function testSendEmptyMessageReturnsEnvelopedError(): void
    {
        $client = $this->createAuthenticatedClient();
        $client->request('POST', '/api/v1/chat/send', server: ['CONTENT_TYPE' => 'application/json'], content: '{"content": ""}');

        $response = $client->getResponse();
        $this->assertSame(400, $response->getStatusCode());

        $payload = json_decode((string) $response->getContent(), true);
        $this->assertFalse($payload['success']);
        $this->assertSame('bad_request', $payload['error']['code']);
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
