<?php

declare(strict_types=1);

namespace App\Tests\Unit\Security;

use App\Entity\User;
use App\Security\LoginFormAuthenticator;
use App\Security\UserChecker;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAccountStatusException;
use Symfony\Component\Security\Core\User\InMemoryUser;

/**
 * ONB-03 — `isBanned` etait ecrit par l'administration et lu nulle part.
 */
class UserCheckerTest extends TestCase
{
    public function testBannedUserIsRefused(): void
    {
        $user = (new User())->setIsBanned(true);

        $this->expectException(CustomUserMessageAccountStatusException::class);

        (new UserChecker())->checkPreAuth($user);
    }

    public function testRegularUserPasses(): void
    {
        $user = (new User())->setIsBanned(false);

        (new UserChecker())->checkPreAuth($user);

        $this->addToAssertionCount(1);
    }

    /**
     * Le refus ne doit rien apprendre : c'est le message d'un mauvais mot de
     * passe, mot pour mot.
     */
    public function testRefusalReusesTheGenericFailureMessage(): void
    {
        $user = (new User())->setIsBanned(true);

        try {
            (new UserChecker())->checkPreAuth($user);
            $this->fail('Un compte banni doit etre refuse.');
        } catch (CustomUserMessageAccountStatusException $exception) {
            $this->assertSame(LoginFormAuthenticator::GENERIC_FAILURE_MESSAGE, $exception->getMessageKey());
        }
    }

    public function testCheckerIgnoresUsersItDoesNotOwn(): void
    {
        (new UserChecker())->checkPreAuth(new InMemoryUser('someone', null));

        $this->addToAssertionCount(1);
    }
}
