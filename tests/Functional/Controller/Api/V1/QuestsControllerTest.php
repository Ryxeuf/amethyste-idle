<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller\Api\V1;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Migration API-first phase 3.3 — GET /api/v1/quests.
 */
class QuestsControllerTest extends WebTestCase
{
    public function testUnauthenticatedRequestReturnsJson401Envelope(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/v1/quests');

        $response = $client->getResponse();
        $this->assertSame(401, $response->getStatusCode());

        $payload = json_decode((string) $response->getContent(), true);
        $this->assertFalse($payload['success']);
        $this->assertSame('unauthenticated', $payload['error']['code']);
    }

    public function testAuthenticatedRequestReturnsQuestJournal(): void
    {
        $client = static::createClient();

        /** @var EntityManagerInterface $em */
        $em = static::getContainer()->get(EntityManagerInterface::class);
        $user = $em->getRepository(User::class)->findOneBy(['email' => 'remy@amethyste.game']);

        if ($user === null) {
            $this->markTestSkipped('Fixture user remy@amethyste.game not found — run doctrine:fixtures:load first.');
        }

        $client->loginUser($user);
        $client->request('GET', '/api/v1/quests');

        $response = $client->getResponse();
        $this->assertSame(200, $response->getStatusCode());

        $payload = json_decode((string) $response->getContent(), true);
        $this->assertTrue($payload['success']);

        $data = $payload['data'];
        foreach (['active', 'available', 'completed', 'daily'] as $section) {
            $this->assertArrayHasKey($section, $data);
            $this->assertIsArray($data[$section]);
        }

        foreach (['active', 'completed', 'available'] as $dailySection) {
            $this->assertArrayHasKey($dailySection, $data['daily']);
        }

        if ($data['available'] !== []) {
            $entry = $data['available'][0];
            $this->assertArrayHasKey('quest', $entry);
            $this->assertArrayHasKey('type', $entry);
            foreach (['id', 'name', 'description', 'requirements', 'rewards'] as $key) {
                $this->assertArrayHasKey($key, $entry['quest']);
            }
        }

        if ($data['active'] !== []) {
            $entry = $data['active'][0];
            foreach (['playerQuestId', 'quest', 'progress', 'tracking'] as $key) {
                $this->assertArrayHasKey($key, $entry);
            }
        }
    }
}
