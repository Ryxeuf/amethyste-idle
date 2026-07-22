<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\Realtime;

use App\Service\Realtime\MercureSubscriberTokenFactory;
use Lcobucci\JWT\Configuration;
use Lcobucci\JWT\Signer\Hmac\Sha256;
use Lcobucci\JWT\Signer\Key\InMemory;
use Lcobucci\JWT\Token\Plain;
use Lcobucci\JWT\Validation\Constraint\SignedWith;
use PHPUnit\Framework\TestCase;

class MercureSubscriberTokenFactoryTest extends TestCase
{
    private const SECRET = 'ci_test_mercure_secret_0123456789abcdef0123456789abcdef';

    public function testTokenCarriesSubscribeClaimAndSignature(): void
    {
        $factory = new MercureSubscriberTokenFactory(self::SECRET);
        $token = $factory->create(['map/move', 'chat/global']);

        $this->assertNotNull($token);

        $configuration = Configuration::forSymmetricSigner(new Sha256(), InMemory::plainText(self::SECRET));
        $parsed = $configuration->parser()->parse($token);
        $this->assertInstanceOf(Plain::class, $parsed);

        $constraint = new SignedWith($configuration->signer(), $configuration->signingKey());
        $this->assertTrue($configuration->validator()->validate($parsed, $constraint));

        $this->assertSame(['subscribe' => ['map/move', 'chat/global']], $parsed->claims()->get('mercure'));
        $this->assertFalse($parsed->isExpired(new \DateTimeImmutable()));
    }

    public function testTooShortSecretYieldsNullInsteadOfThrowing(): void
    {
        $factory = new MercureSubscriberTokenFactory('short');

        $this->assertNull($factory->create(['map/move']));
    }
}
