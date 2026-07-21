<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller\Api\V1;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Migration API-first phase 2.1 — GET /api/v1/inventory.
 */
class InventoryControllerTest extends WebTestCase
{
    public function testUnauthenticatedRequestReturnsJson401Envelope(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/v1/inventory');

        $response = $client->getResponse();
        $this->assertSame(401, $response->getStatusCode());

        $payload = json_decode((string) $response->getContent(), true);
        $this->assertFalse($payload['success']);
        $this->assertSame('unauthenticated', $payload['error']['code']);
    }

    public function testAuthenticatedRequestReturnsInventoryPayload(): void
    {
        $client = static::createClient();

        /** @var EntityManagerInterface $em */
        $em = static::getContainer()->get(EntityManagerInterface::class);
        $user = $em->getRepository(User::class)->findOneBy(['email' => 'remy@amethyste.game']);

        if ($user === null) {
            $this->markTestSkipped('Fixture user remy@amethyste.game not found — run doctrine:fixtures:load first.');
        }

        $client->loginUser($user);
        $client->request('GET', '/api/v1/inventory');

        $response = $client->getResponse();
        $this->assertSame(200, $response->getStatusCode());

        $payload = json_decode((string) $response->getContent(), true);
        $this->assertTrue($payload['success']);

        $data = $payload['data'];
        foreach (['summary', 'consumables', 'materials', 'equipment', 'materia', 'bank'] as $section) {
            $this->assertArrayHasKey($section, $data);
        }

        $this->assertIsInt($data['summary']['gold']);
        $this->assertIsInt($data['summary']['bagSize']);
        $this->assertIsInt($data['summary']['bagUsed']);

        $equipment = $data['equipment'];
        $this->assertIsArray($equipment['equipped']);
        $this->assertIsArray($equipment['availableGear']);
        $this->assertIsArray($equipment['sets']);
        $this->assertIsArray($equipment['stats']);
        $this->assertArrayHasKey('protection', $equipment['setBonuses']);

        $this->assertIsArray($data['bank']['items']);
        $this->assertIsInt($data['bank']['size']);
    }
}
