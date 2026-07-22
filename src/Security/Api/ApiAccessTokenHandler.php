<?php

namespace App\Security\Api;

use Symfony\Component\Security\Core\Exception\BadCredentialsException;
use Symfony\Component\Security\Http\AccessToken\AccessTokenHandlerInterface;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;

/**
 * Handler de l'authenticator natif access_token (firewall main) : valide le
 * Bearer JWT de l'API v1 et resout l'utilisateur par email.
 * La session par cookie reste le mecanisme principal du client web ; le
 * Bearer s'ajoute pour les clients natifs (mobile/Steam).
 */
class ApiAccessTokenHandler implements AccessTokenHandlerInterface
{
    public function __construct(
        private readonly ApiJwtManager $jwtManager,
    ) {
    }

    public function getUserBadgeFrom(string $accessToken): UserBadge
    {
        $email = $this->jwtManager->validate($accessToken, 'access');
        if ($email === null) {
            throw new BadCredentialsException('Invalid or expired access token.');
        }

        return new UserBadge($email);
    }
}
