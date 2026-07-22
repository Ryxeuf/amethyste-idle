<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller\Api\V1;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Migration API-first phase 3.4 — actions de quetes sous /api/v1.
 *
 * Ne teste que des chemins sans mutation d'etat des fixtures
 * (identifiants inexistants, corps invalides).
 */
class QuestActionsControllerTest extends WebTestCase
{
    /**
     * @return iterable<string, array{string}>
     */
    public static function notFoundActionsProvider(): iterable
    {
        yield 'accept' => ['/api/v1/quests/99999999/accept'];
        yield 'abandon' => ['/api/v1/quests/99999999/abandon'];
        yield 'complete' => ['/api/v1/quests/99999999/complete'];
        yield 'daily accept' => ['/api/v1/quests/daily/99999999/accept'];
        yield 'daily complete' => ['/api/v1/quests/daily/99999999/complete'];
        yield 'daily abandon' => ['/api/v1/quests/daily/99999999/abandon'];
    }

    #[DataProvider('notFoundActionsProvider')]
    public function testUnauthenticatedActionReturnsJson401Envelope(string $url): void
    {
        $client = static::createClient();
        $client->request('POST', $url, server: ['CONTENT_TYPE' => 'application/json'], content: '{}');

        $response = $client->getResponse();
        $this->assertSame(401, $response->getStatusCode());

        $payload = json_decode((string) $response->getContent(), true);
        $this->assertFalse($payload['success']);
        $this->assertSame('unauthenticated', $payload['error']['code']);
    }

    #[DataProvider('notFoundActionsProvider')]
    public function testActionWithoutJsonContentTypeIsRejected(string $url): void
    {
        $client = $this->createAuthenticatedClient();
        $client->request('POST', $url);

        $response = $client->getResponse();
        $this->assertSame(400, $response->getStatusCode());

        $payload = json_decode((string) $response->getContent(), true);
        $this->assertFalse($payload['success']);
        $this->assertSame('bad_request', $payload['error']['code']);
    }

    #[DataProvider('notFoundActionsProvider')]
    public function testActionOnUnknownQuestReturns404(string $url): void
    {
        $client = $this->createAuthenticatedClient();
        $client->request('POST', $url, server: ['CONTENT_TYPE' => 'application/json'], content: '{}');

        $response = $client->getResponse();
        $this->assertSame(404, $response->getStatusCode());

        $payload = json_decode((string) $response->getContent(), true);
        $this->assertFalse($payload['success']);
        $this->assertSame('not_found', $payload['error']['code']);
    }

    public function testDeliverToUnknownPnjReturnsActionRejected(): void
    {
        $client = $this->createAuthenticatedClient();
        $client->request('POST', '/api/v1/quests/deliver/99999999', server: ['CONTENT_TYPE' => 'application/json'], content: '{}');

        $response = $client->getResponse();
        $this->assertSame(409, $response->getStatusCode());

        $payload = json_decode((string) $response->getContent(), true);
        $this->assertFalse($payload['success']);
        $this->assertSame('action_rejected', $payload['error']['code']);
        $this->assertNotEmpty($payload['error']['message']);
    }

    public function testPuzzleAnswerWithoutAnswerReturns400(): void
    {
        $client = $this->createAuthenticatedClient();
        $client->request('POST', '/api/v1/quests/puzzle-answer/99999999', server: ['CONTENT_TYPE' => 'application/json'], content: '{}');

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
