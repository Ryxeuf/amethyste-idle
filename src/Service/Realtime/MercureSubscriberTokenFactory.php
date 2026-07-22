<?php

namespace App\Service\Realtime;

use Lcobucci\JWT\Configuration;
use Lcobucci\JWT\Signer\Hmac\Sha256;
use Lcobucci\JWT\Signer\Key\InMemory;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Emet des JWT subscriber Mercure (claim mercure.subscribe) signes avec la
 * meme cle que le hub (MERCURE_JWT_SECRET). Utilise par l'API v1 pour les
 * clients natifs qui ne peuvent pas s'appuyer sur le cookie web
 * (migration API-first, phase 0.4).
 */
class MercureSubscriberTokenFactory
{
    public const DEFAULT_TTL = 3600;

    public function __construct(
        #[Autowire('%env(MERCURE_JWT_SECRET)%')]
        private readonly string $mercureSecret,
    ) {
    }

    /**
     * Retourne null si la cle configuree ne permet pas de signer (ex: cle
     * plus courte que le minimum HMAC 256 bits de lcobucci) — le hub
     * autorisant les abonnes anonymes, l'absence de token n'est pas bloquante.
     *
     * @param list<string> $topics
     */
    public function create(array $topics, int $ttl = self::DEFAULT_TTL): ?string
    {
        try {
            $configuration = Configuration::forSymmetricSigner(new Sha256(), InMemory::plainText($this->mercureSecret));
            $now = new \DateTimeImmutable();

            return $configuration->builder()
                ->issuedAt($now)
                ->expiresAt($now->modify(sprintf('+%d seconds', $ttl)))
                ->withClaim('mercure', ['subscribe' => $topics])
                ->getToken($configuration->signer(), $configuration->signingKey())
                ->toString();
        } catch (\Throwable) {
            return null;
        }
    }
}
