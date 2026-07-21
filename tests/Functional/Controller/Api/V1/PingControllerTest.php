<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller\Api\V1;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Migration API-first phase 0.1 — verifie la convention d'enveloppe JSON
 * sur /api/v1 : succes via /ping (public), erreur via une route inconnue.
 */
class PingControllerTest extends WebTestCase
{
    public function testPingReturnsSuccessEnvelopeWithoutAuthentication(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/v1/ping');

        $response = $client->getResponse();
        $this->assertSame(200, $response->getStatusCode());
        $this->assertStringContainsString('application/json', (string) $response->headers->get('Content-Type'));

        $payload = json_decode((string) $response->getContent(), true);
        $this->assertTrue($payload['success']);
        $this->assertTrue($payload['data']['pong']);
        $this->assertNotEmpty($payload['data']['version']);
        $this->assertNotEmpty($payload['data']['serverTime']);
    }

    public function testUnknownApiRouteReturnsJsonErrorEnvelope(): void
    {
        $client = static::createClient();
        $client->catchExceptions(true);
        $client->request('GET', '/api/v1/does-not-exist');

        $response = $client->getResponse();
        $this->assertSame(404, $response->getStatusCode());
        $this->assertStringContainsString('application/json', (string) $response->headers->get('Content-Type'));

        $payload = json_decode((string) $response->getContent(), true);
        $this->assertFalse($payload['success']);
        $this->assertSame('not_found', $payload['error']['code']);
    }

    public function testMethodNotAllowedReturnsJsonErrorEnvelope(): void
    {
        $client = static::createClient();
        $client->catchExceptions(true);
        $client->request('POST', '/api/v1/ping');

        $response = $client->getResponse();
        $this->assertSame(405, $response->getStatusCode());

        $payload = json_decode((string) $response->getContent(), true);
        $this->assertFalse($payload['success']);
        $this->assertSame('method_not_allowed', $payload['error']['code']);
    }
}
