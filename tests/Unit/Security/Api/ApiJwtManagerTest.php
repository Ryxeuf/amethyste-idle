<?php

declare(strict_types=1);

namespace App\Tests\Unit\Security\Api;

use App\Entity\User;
use App\Security\Api\ApiJwtManager;
use PHPUnit\Framework\TestCase;

class ApiJwtManagerTest extends TestCase
{
    private const SECRET = 'e454dd0213910bb338128f1a8306c1caa01114e1c30748e3882eb72ba4c117e7';

    private ApiJwtManager $manager;

    protected function setUp(): void
    {
        $this->manager = new ApiJwtManager(self::SECRET);
    }

    public function testCreateTokenPairShape(): void
    {
        $pair = $this->manager->createTokenPair($this->makeUser());

        $this->assertSame('Bearer', $pair['tokenType']);
        $this->assertSame(ApiJwtManager::ACCESS_TOKEN_TTL, $pair['expiresIn']);
        $this->assertNotSame($pair['accessToken'], $pair['refreshToken']);
    }

    public function testAccessTokenValidates(): void
    {
        $pair = $this->manager->createTokenPair($this->makeUser());

        $this->assertSame('player@amethyste.test', $this->manager->validate($pair['accessToken'], 'access'));
    }

    public function testRefreshTokenValidatesOnlyAsRefresh(): void
    {
        $pair = $this->manager->createTokenPair($this->makeUser());

        $this->assertSame('player@amethyste.test', $this->manager->validate($pair['refreshToken'], 'refresh'));
        $this->assertNull($this->manager->validate($pair['refreshToken'], 'access'));
        $this->assertNull($this->manager->validate($pair['accessToken'], 'refresh'));
    }

    public function testGarbageTokenIsRejected(): void
    {
        $this->assertNull($this->manager->validate('not-a-jwt', 'access'));
        $this->assertNull($this->manager->validate('', 'access'));
    }

    public function testTokenSignedWithAnotherSecretIsRejected(): void
    {
        $other = new ApiJwtManager(str_repeat('x', 64));
        $pair = $other->createTokenPair($this->makeUser());

        $this->assertNull($this->manager->validate($pair['accessToken'], 'access'));
    }

    private function makeUser(): User
    {
        $user = new User();
        $user->setEmail('player@amethyste.test');

        return $user;
    }
}
