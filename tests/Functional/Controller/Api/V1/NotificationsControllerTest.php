<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller\Api\V1;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Migration API-first phase 4.4 — notifications sous /api/v1.
 */
class NotificationsControllerTest extends WebTestCase
{
    public function testUnauthenticatedListReturnsJson401Envelope(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/v1/notifications');

        $response = $client->getResponse();
        $this->assertSame(401, $response->getStatusCode());

        $payload = json_decode((string) $response->getContent(), true);
        $this->assertFalse($payload['success']);
        $this->assertSame('unauthenticated', $payload['error']['code']);
    }

    public function testAuthenticatedListReturnsNotificationsPayload(): void
    {
        $client = $this->createAuthenticatedClient();
        $client->request('GET', '/api/v1/notifications?limit=5');

        $response = $client->getResponse();
        $this->assertSame(200, $response->getStatusCode());

        $payload = json_decode((string) $response->getContent(), true);
        $this->assertTrue($payload['success']);
        $this->assertIsArray($payload['data']['notifications']);
        $this->assertIsInt($payload['data']['unreadCount']);
        $this->assertLessThanOrEqual(5, count($payload['data']['notifications']));

        if ($payload['data']['notifications'] !== []) {
            $entry = $payload['data']['notifications'][0];
            foreach (['id', 'type', 'title', 'comment', 'isRead', 'createdAt'] as $key) {
                $this->assertArrayHasKey($key, $entry);
            }
        }
    }

    public function testMarkAllReadWithoutJsonContentTypeIsRejected(): void
    {
        $client = $this->createAuthenticatedClient();
        $client->request('POST', '/api/v1/notifications/mark-all-read');

        $response = $client->getResponse();
        $this->assertSame(400, $response->getStatusCode());

        $payload = json_decode((string) $response->getContent(), true);
        $this->assertFalse($payload['success']);
        $this->assertSame('bad_request', $payload['error']['code']);
    }

    public function testMarkReadUnknownNotificationReturns404(): void
    {
        $client = $this->createAuthenticatedClient();
        $client->request('POST', '/api/v1/notifications/99999999/read', server: ['CONTENT_TYPE' => 'application/json'], content: '{}');

        $response = $client->getResponse();
        $this->assertSame(404, $response->getStatusCode());

        $payload = json_decode((string) $response->getContent(), true);
        $this->assertFalse($payload['success']);
        $this->assertSame('not_found', $payload['error']['code']);
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
