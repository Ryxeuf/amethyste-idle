<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller\Api\V1;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Migration API-first phase 1.2 — POST /api/v1/fight/{attack,spell,item,flee}.
 *
 * Verifie le contrat d'enveloppe des alias v1 qui deleguent aux controleurs
 * legacy /game/fight/* : toute reponse est soit {success: true, data},
 * soit {success: false, error: {code, message}}.
 */
class FightActionsControllerTest extends WebTestCase
{
    /**
     * @return iterable<string, array{string}>
     */
    public static function actionsProvider(): iterable
    {
        yield 'attack' => ['/api/v1/fight/attack'];
        yield 'spell' => ['/api/v1/fight/spell'];
        yield 'item' => ['/api/v1/fight/item'];
        yield 'flee' => ['/api/v1/fight/flee'];
    }

    #[DataProvider('actionsProvider')]
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

    #[DataProvider('actionsProvider')]
    public function testAuthenticatedActionRespectsEnvelopeContract(string $url): void
    {
        $client = $this->createAuthenticatedClient();
        $client->request('POST', $url, server: ['CONTENT_TYPE' => 'application/json'], content: '{}');

        $response = $client->getResponse();
        $payload = json_decode((string) $response->getContent(), true);

        $this->assertIsArray($payload);
        $this->assertArrayHasKey('success', $payload);

        if ($payload['success']) {
            $this->assertSame(200, $response->getStatusCode());
            $this->assertArrayHasKey('data', $payload);
        } else {
            // Corps vide -> le legacy repond 404 (pas de combat), 400 (donnees
            // invalides) ou un rejet metier re-enveloppe en 409.
            $this->assertGreaterThanOrEqual(400, $response->getStatusCode());
            $this->assertNotEmpty($payload['error']['code']);
            $this->assertNotEmpty($payload['error']['message']);
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
