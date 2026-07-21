<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller\Api\V1;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Migration API-first phase 1.4 — GET /api/v1/fight/loot et POST proceed.
 */
class FightLootControllerTest extends WebTestCase
{
    public function testUnauthenticatedLootReturnsJson401Envelope(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/v1/fight/loot');

        $response = $client->getResponse();
        $this->assertSame(401, $response->getStatusCode());

        $payload = json_decode((string) $response->getContent(), true);
        $this->assertFalse($payload['success']);
        $this->assertSame('unauthenticated', $payload['error']['code']);
    }

    public function testAuthenticatedLootRespectsEnvelopeContract(): void
    {
        $client = static::createClient();

        /** @var EntityManagerInterface $em */
        $em = static::getContainer()->get(EntityManagerInterface::class);
        $user = $em->getRepository(User::class)->findOneBy(['email' => 'remy@amethyste.game']);

        if ($user === null) {
            $this->markTestSkipped('Fixture user remy@amethyste.game not found — run doctrine:fixtures:load first.');
        }

        $client->loginUser($user);
        $client->request('GET', '/api/v1/fight/loot');

        $response = $client->getResponse();
        $payload = json_decode((string) $response->getContent(), true);

        $this->assertIsArray($payload);

        if ($payload['success']) {
            // Joueur en combat : payload de butin complet
            $this->assertSame(200, $response->getStatusCode());
            $this->assertArrayHasKey('fightId', $payload['data']);
            $this->assertArrayHasKey('victory', $payload['data']);
            $this->assertIsArray($payload['data']['items']);
            $this->assertIsArray($payload['data']['contributions']);
        } else {
            // Pas de combat en cours : 404 enveloppe
            $this->assertSame(404, $response->getStatusCode());
            $this->assertSame('not_found', $payload['error']['code']);
        }
    }

    public function testProceedWithInvalidBodyReturnsEnvelopedError(): void
    {
        $client = static::createClient();

        /** @var EntityManagerInterface $em */
        $em = static::getContainer()->get(EntityManagerInterface::class);
        $user = $em->getRepository(User::class)->findOneBy(['email' => 'remy@amethyste.game']);

        if ($user === null) {
            $this->markTestSkipped('Fixture user remy@amethyste.game not found — run doctrine:fixtures:load first.');
        }

        $client->loginUser($user);
        $client->request('POST', '/api/v1/fight/loot/proceed', server: ['CONTENT_TYPE' => 'application/json'], content: '{}');

        $response = $client->getResponse();
        $this->assertSame(400, $response->getStatusCode());

        $payload = json_decode((string) $response->getContent(), true);
        $this->assertFalse($payload['success']);
        $this->assertSame('bad_request', $payload['error']['code']);
    }
}
