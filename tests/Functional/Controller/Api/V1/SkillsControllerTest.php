<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller\Api\V1;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Migration API-first phase 3.1 — GET /api/v1/skills.
 */
class SkillsControllerTest extends WebTestCase
{
    public function testUnauthenticatedRequestReturnsJson401Envelope(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/v1/skills');

        $response = $client->getResponse();
        $this->assertSame(401, $response->getStatusCode());

        $payload = json_decode((string) $response->getContent(), true);
        $this->assertFalse($payload['success']);
        $this->assertSame('unauthenticated', $payload['error']['code']);
    }

    public function testAuthenticatedRequestReturnsSkillTreePayload(): void
    {
        $client = static::createClient();

        /** @var EntityManagerInterface $em */
        $em = static::getContainer()->get(EntityManagerInterface::class);
        $user = $em->getRepository(User::class)->findOneBy(['email' => 'remy@amethyste.game']);

        if ($user === null) {
            $this->markTestSkipped('Fixture user remy@amethyste.game not found — run doctrine:fixtures:load first.');
        }

        $client->loginUser($user);
        $client->request('GET', '/api/v1/skills');

        $response = $client->getResponse();
        $this->assertSame(200, $response->getStatusCode());

        $payload = json_decode((string) $response->getContent(), true);
        $this->assertTrue($payload['success']);

        $data = $payload['data'];
        foreach (['domains', 'buildStats', 'respec', 'points', 'presets', 'playerGils'] as $section) {
            $this->assertArrayHasKey($section, $data);
        }

        // ARC-10 : le plafond global a disparu, donc la charge utile ne
        // l'annonce plus. Le total investi reste — c'est une information sur
        // le personnage, jamais une borne.
        $this->assertArrayHasKey('totalUsedPoints', $data['points']);
        $this->assertArrayNotHasKey('maxTotalPoints', $data['points']);
        $this->assertIsArray($data['presets']['items']);

        if ($data['domains'] !== []) {
            $domain = $data['domains'][0];
            foreach (['id', 'slug', 'title', 'availableExperience', 'skills'] as $key) {
                $this->assertArrayHasKey($key, $domain);
            }

            if ($domain['skills'] !== []) {
                $skill = $domain['skills'][0];
                foreach (['id', 'title', 'requiredPoints', 'acquired', 'canBeAcquired', 'requirementIds'] as $key) {
                    $this->assertArrayHasKey($key, $skill);
                }
            }
        }
    }
}
