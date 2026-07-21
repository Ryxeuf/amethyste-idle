<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller\Api\V1;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Migration API-first phase 1.1 — GET /api/v1/fight.
 */
class FightControllerTest extends WebTestCase
{
    public function testUnauthenticatedRequestReturnsJson401Envelope(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/v1/fight');

        $response = $client->getResponse();
        $this->assertSame(401, $response->getStatusCode());
        $this->assertStringContainsString('application/json', (string) $response->headers->get('Content-Type'));

        $payload = json_decode((string) $response->getContent(), true);
        $this->assertFalse($payload['success']);
        $this->assertSame('unauthenticated', $payload['error']['code']);
    }

    public function testAuthenticatedRequestReturnsFightState(): void
    {
        $client = static::createClient();

        /** @var EntityManagerInterface $em */
        $em = static::getContainer()->get(EntityManagerInterface::class);
        $user = $em->getRepository(User::class)->findOneBy(['email' => 'remy@amethyste.game']);

        if ($user === null) {
            $this->markTestSkipped('Fixture user remy@amethyste.game not found — run doctrine:fixtures:load first.');
        }

        $client->loginUser($user);
        $client->request('GET', '/api/v1/fight');

        $response = $client->getResponse();
        $this->assertSame(200, $response->getStatusCode());

        $payload = json_decode((string) $response->getContent(), true);
        $this->assertTrue($payload['success']);
        $this->assertArrayHasKey('inFight', $payload['data']);
        $this->assertArrayHasKey('fight', $payload['data']);

        if ($payload['data']['inFight']) {
            $fight = $payload['data']['fight'];
            $this->assertContains($fight['status'], ['active', 'victory', 'defeat']);
            $this->assertIsArray($fight['players']);
            $this->assertIsArray($fight['mobs']);
            $this->assertIsArray($fight['spells']);
            $this->assertIsArray($fight['timeline']);
            $this->assertIsArray($fight['logs']);
        } else {
            $this->assertNull($payload['data']['fight']);
        }
    }
}
