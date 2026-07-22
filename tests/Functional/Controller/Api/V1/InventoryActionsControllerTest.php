<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller\Api\V1;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Migration API-first phase 2.2 — actions d'inventaire sous /api/v1.
 *
 * Ne teste que des chemins sans mutation d'etat des fixtures
 * (pas d'equipement ni d'utilisation reelle d'objet).
 */
class InventoryActionsControllerTest extends WebTestCase
{
    /**
     * @return iterable<string, array{string}>
     */
    public static function actionsProvider(): iterable
    {
        yield 'equip' => ['/api/v1/inventory/equip/99999999'];
        yield 'unequip' => ['/api/v1/inventory/unequip/99999999'];
        yield 'use' => ['/api/v1/inventory/use/99999999'];
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
    public function testActionWithoutJsonContentTypeIsRejected(string $url): void
    {
        $client = $this->createAuthenticatedClient();
        $client->request('POST', $url);

        $response = $client->getResponse();
        $this->assertSame(400, $response->getStatusCode());

        $payload = json_decode((string) $response->getContent(), true);
        $this->assertFalse($payload['success']);
        $this->assertSame('bad_request', $payload['error']['code']);
        $this->assertStringContainsString('Content-Type', $payload['error']['message']);
    }

    #[DataProvider('actionsProvider')]
    public function testActionOnUnknownItemReturns404(string $url): void
    {
        $client = $this->createAuthenticatedClient();
        $client->request('POST', $url, server: ['CONTENT_TYPE' => 'application/json'], content: '{}');

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
