<?php

namespace App\Security\Api;

use App\Entity\User;
use Lcobucci\JWT\Configuration;
use Lcobucci\JWT\Signer\Hmac\Sha256;
use Lcobucci\JWT\Signer\Key\InMemory;
use Lcobucci\JWT\Token\Plain;
use Lcobucci\JWT\Validation\Constraint\SignedWith;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Emission et validation des JWT de l'API v1 (migration API-first, phase 0.2).
 *
 * Tokens HS256 signes avec API_JWT_SECRET (fallback : kernel.secret) via
 * lcobucci/jwt (deja present comme dependance de symfony/mercure).
 * - access token : 1 h, claim type=access
 * - refresh token : 30 jours, claim type=refresh (stateless, non revocable —
 *   la revocation par liste noire viendra si le besoin apparait)
 */
class ApiJwtManager
{
    public const ACCESS_TOKEN_TTL = 3600;
    public const REFRESH_TOKEN_TTL = 2592000;

    private const ISSUER = 'amethyste-idle';

    private Configuration $configuration;

    public function __construct(
        #[Autowire('%env(default:kernel.secret:API_JWT_SECRET)%')]
        string $secret,
    ) {
        // Derivation sha256 : garantit une cle de signature >= 256 bits
        // (minimum HMAC de lcobucci) quelle que soit la longueur du secret.
        $signingKey = hash('sha256', 'api-v1:' . $secret);

        $this->configuration = Configuration::forSymmetricSigner(new Sha256(), InMemory::plainText($signingKey));
    }

    /**
     * @return array{accessToken: string, refreshToken: string, tokenType: string, expiresIn: int}
     */
    public function createTokenPair(User $user): array
    {
        return [
            'accessToken' => $this->issue($user, 'access', self::ACCESS_TOKEN_TTL),
            'refreshToken' => $this->issue($user, 'refresh', self::REFRESH_TOKEN_TTL),
            'tokenType' => 'Bearer',
            'expiresIn' => self::ACCESS_TOKEN_TTL,
        ];
    }

    /**
     * Valide un token et retourne l'identifiant utilisateur (email), ou null
     * si le token est invalide, expire, ou du mauvais type.
     */
    public function validate(string $jwt, string $expectedType): ?string
    {
        try {
            $token = $this->configuration->parser()->parse($jwt);
        } catch (\Throwable) {
            return null;
        }

        if (!$token instanceof Plain) {
            return null;
        }

        $constraint = new SignedWith($this->configuration->signer(), $this->configuration->signingKey());
        if (!$this->configuration->validator()->validate($token, $constraint)) {
            return null;
        }

        if ($token->isExpired(new \DateTimeImmutable())) {
            return null;
        }

        if ($token->claims()->get('type') !== $expectedType) {
            return null;
        }

        $subject = $token->claims()->get('sub');

        return is_string($subject) && $subject !== '' ? $subject : null;
    }

    private function issue(User $user, string $type, int $ttl): string
    {
        $now = new \DateTimeImmutable();

        return $this->configuration->builder()
            ->issuedBy(self::ISSUER)
            ->issuedAt($now)
            ->expiresAt($now->modify(sprintf('+%d seconds', $ttl)))
            ->relatedTo($user->getUserIdentifier())
            ->withClaim('type', $type)
            ->getToken($this->configuration->signer(), $this->configuration->signingKey())
            ->toString();
    }
}
