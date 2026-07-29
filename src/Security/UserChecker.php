<?php

namespace App\Security;

use App\Entity\User;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAccountStatusException;
use Symfony\Component\Security\Core\User\UserCheckerInterface;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * ONB-03 — le bannissement cesse d'etre decoratif.
 *
 * `User::isBanned` etait ecrit par deux ecrans d'administration et lu **nulle
 * part** : bannir quelqu'un ne l'empechait pas de se connecter le lendemain.
 *
 * Le refus passe par un message **identique** a celui d'un mauvais mot de passe
 * (voir `LoginFormAuthenticator::onAuthenticationFailure`) : distinguer les deux
 * transformerait l'ecran de connexion en oracle — « cette adresse existe, et
 * elle est bannie ». Le banni deja connecte, lui, a droit a la verite : il est
 * authentifie, il n'y a plus rien a lui apprendre (`BannedUserSubscriber`).
 */
class UserChecker implements UserCheckerInterface
{
    public function checkPreAuth(UserInterface $user): void
    {
        if ($user instanceof User && $user->isBanned()) {
            throw new CustomUserMessageAccountStatusException(LoginFormAuthenticator::GENERIC_FAILURE_MESSAGE);
        }
    }

    public function checkPostAuth(UserInterface $user): void
    {
    }
}
