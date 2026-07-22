<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller\Api\V1;

use App\Entity\User;
use App\Security\Api\ApiJwtManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Migration API-first phase 0.2 — authentification JWT de l'API v1.
 */
class AuthControllerTest extends WebTestCase
{
    public function testLoginWithoutJsonContentTypeIsRejected(): void
    {
        $client = static::createClient();
        $client->request('POST', '/api/v1/auth/login', server: ['CONTENT_TYPE' => 'application/x-www-form-urlencoded'], content: 'email=a@b.c');

        $response = $client->getResponse();
        $this->assertSame(400, $response->getStatusCode());

        $payload = json_decode((string) $response->getContent(), true);
        $this->assertSame('bad_request', $payload['error']['code']);
    }

    public function testLoginWithMissingFieldsReturns400(): void
    {
        $client = static::createClient();
        $client->request('POST', '/api/v1/auth/login', server: ['CONTENT_TYPE' => 'application/json'], content: '{"email": "a@b.c"}');

        $response = $client->getResponse();
        $this->assertSame(400, $response->getStatusCode());
    }

    public function testLoginWithUnknownUserReturns401(): void
    {
        $client = static::createClient();
        $client->request('POST', '/api/v1/auth/login', server: ['CONTENT_TYPE' => 'application/json'], content: '{"email": "nobody@amethyste.test", "password": "wrong"}');

        $response = $client->getResponse();
        $this->assertSame(401, $response->getStatusCode());

        $payload = json_decode((string) $response->getContent(), true);
        $this->assertFalse($payload['success']);
        $this->assertSame('invalid_credentials', $payload['error']['code']);
    }

    public function testBearerTokenGrantsAccessToProtectedEndpoint(): void
    {
        $client = static::createClient();
        $user = $this->findFixtureUser();

        /** @var ApiJwtManager $jwtManager */
        $jwtManager = static::getContainer()->get(ApiJwtManager::class);
        $pair = $jwtManager->createTokenPair($user);

        $client->request('GET', '/api/v1/fight', server: ['HTTP_AUTHORIZATION' => 'Bearer ' . $pair['accessToken']]);

        $response = $client->getResponse();
        $this->assertSame(200, $response->getStatusCode());

        $payload = json_decode((string) $response->getContent(), true);
        $this->assertTrue($payload['success']);
        $this->assertArrayHasKey('inFight', $payload['data']);
    }

    public function testInvalidBearerTokenIsRejected(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/v1/fight', server: ['HTTP_AUTHORIZATION' => 'Bearer invalid.token.here']);

        $this->assertSame(401, $client->getResponse()->getStatusCode());
    }

    public function testRefreshRotatesTokenPair(): void
    {
        $client = static::createClient();
        $user = $this->findFixtureUser();

        /** @var ApiJwtManager $jwtManager */
        $jwtManager = static::getContainer()->get(ApiJwtManager::class);
        $pair = $jwtManager->createTokenPair($user);

        $client->request('POST', '/api/v1/auth/refresh', server: ['CONTENT_TYPE' => 'application/json'], content: json_encode(['refreshToken' => $pair['refreshToken']]));

        $response = $client->getResponse();
        $this->assertSame(200, $response->getStatusCode());

        $payload = json_decode((string) $response->getContent(), true);
        $this->assertTrue($payload['success']);
        $this->assertNotEmpty($payload['data']['accessToken']);
        $this->assertNotEmpty($payload['data']['refreshToken']);
    }

    public function testRefreshRejectsAccessToken(): void
    {
        $client = static::createClient();
        $user = $this->findFixtureUser();

        /** @var ApiJwtManager $jwtManager */
        $jwtManager = static::getContainer()->get(ApiJwtManager::class);
        $pair = $jwtManager->createTokenPair($user);

        $client->request('POST', '/api/v1/auth/refresh', server: ['CONTENT_TYPE' => 'application/json'], content: json_encode(['refreshToken' => $pair['accessToken']]));

        $response = $client->getResponse();
        $this->assertSame(401, $response->getStatusCode());

        $payload = json_decode((string) $response->getContent(), true);
        $this->assertSame('invalid_credentials', $payload['error']['code']);
    }

    private function findFixtureUser(): User
    {
        /** @var EntityManagerInterface $em */
        $em = static::getContainer()->get(EntityManagerInterface::class);
        $user = $em->getRepository(User::class)->findOneBy(['email' => 'remy@amethyste.game']);

        if ($user === null) {
            $this->markTestSkipped('Fixture user remy@amethyste.game not found — run doctrine:fixtures:load first.');
        }

        return $user;
    }
}
