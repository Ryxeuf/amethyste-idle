<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller\Api\V1;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Migration API-first phase 3.2 — actions des arbres de talent sous /api/v1.
 *
 * Ne teste que des chemins sans mutation destructive des fixtures
 * (pas de respec reussi ni d'acquisition reelle).
 */
class SkillActionsControllerTest extends WebTestCase
{
    public function testUnauthenticatedAcquireReturnsJson401Envelope(): void
    {
        $client = static::createClient();
        $client->request('POST', '/api/v1/skills/acquire', server: ['CONTENT_TYPE' => 'application/json'], content: '{}');

        $response = $client->getResponse();
        $this->assertSame(401, $response->getStatusCode());

        $payload = json_decode((string) $response->getContent(), true);
        $this->assertFalse($payload['success']);
        $this->assertSame('unauthenticated', $payload['error']['code']);
    }

    public function testAcquireWithoutJsonContentTypeIsRejected(): void
    {
        $client = $this->createAuthenticatedClient();
        $client->request('POST', '/api/v1/skills/acquire', server: ['CONTENT_TYPE' => 'application/x-www-form-urlencoded'], content: 'skillId=1');

        $response = $client->getResponse();
        $this->assertSame(400, $response->getStatusCode());

        $payload = json_decode((string) $response->getContent(), true);
        $this->assertFalse($payload['success']);
        $this->assertSame('bad_request', $payload['error']['code']);
        $this->assertStringContainsString('Content-Type', $payload['error']['message']);
    }

    public function testAcquireWithMissingSkillIdReturns400(): void
    {
        $client = $this->createAuthenticatedClient();
        $client->request('POST', '/api/v1/skills/acquire', server: ['CONTENT_TYPE' => 'application/json'], content: '{}');

        $response = $client->getResponse();
        $this->assertSame(400, $response->getStatusCode());

        $payload = json_decode((string) $response->getContent(), true);
        $this->assertSame('bad_request', $payload['error']['code']);
    }

    public function testAcquireUnknownSkillReturns404(): void
    {
        $client = $this->createAuthenticatedClient();
        $client->request('POST', '/api/v1/skills/acquire', server: ['CONTENT_TYPE' => 'application/json'], content: '{"skillId": 99999999}');

        $response = $client->getResponse();
        $this->assertSame(404, $response->getStatusCode());

        $payload = json_decode((string) $response->getContent(), true);
        $this->assertSame('not_found', $payload['error']['code']);
    }

    public function testRespecWithoutJsonContentTypeIsRejected(): void
    {
        $client = $this->createAuthenticatedClient();
        $client->request('POST', '/api/v1/skills/respec');

        $response = $client->getResponse();
        $this->assertSame(400, $response->getStatusCode());

        $payload = json_decode((string) $response->getContent(), true);
        $this->assertSame('bad_request', $payload['error']['code']);
    }

    public function testSavePresetRespectsEnvelopeContract(): void
    {
        $client = $this->createAuthenticatedClient();
        // Nom vide : rejete en 422 (nom invalide) si le joueur peut sauvegarder,
        // ou en 409 (aucune competence / limite atteinte) sinon.
        $client->request('POST', '/api/v1/skills/presets', server: ['CONTENT_TYPE' => 'application/json'], content: '{"name": ""}');

        $response = $client->getResponse();
        $payload = json_decode((string) $response->getContent(), true);

        $this->assertFalse($payload['success']);
        $this->assertContains($response->getStatusCode(), [409, 422]);
        $this->assertContains($payload['error']['code'], ['action_rejected', 'validation_failed']);
    }

    public function testLoadUnknownPresetReturns404(): void
    {
        $client = $this->createAuthenticatedClient();
        $client->request('POST', '/api/v1/skills/presets/99999999/load', server: ['CONTENT_TYPE' => 'application/json'], content: '{}');

        $response = $client->getResponse();
        $this->assertSame(404, $response->getStatusCode());

        $payload = json_decode((string) $response->getContent(), true);
        $this->assertFalse($payload['success']);
        $this->assertSame('not_found', $payload['error']['code']);
    }

    public function testDeleteUnknownPresetReturns404(): void
    {
        $client = $this->createAuthenticatedClient();
        $client->request('DELETE', '/api/v1/skills/presets/99999999');

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
