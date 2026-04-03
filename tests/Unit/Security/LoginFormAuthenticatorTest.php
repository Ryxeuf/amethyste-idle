<?php

namespace App\Tests\Unit\Security;

use App\Entity\App\Player;
use App\Entity\User;
use App\Security\LoginFormAuthenticator;
use Doctrine\Common\Collections\ArrayCollection;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

class LoginFormAuthenticatorTest extends TestCase
{
    private UrlGeneratorInterface $urlGenerator;
    private LoginFormAuthenticator $authenticator;

    protected function setUp(): void
    {
        $this->urlGenerator = $this->createMock(UrlGeneratorInterface::class);
        $this->authenticator = new LoginFormAuthenticator($this->urlGenerator);
    }

    public function testRedirectsToCharacterCreateWhenNoPlayers(): void
    {
        $user = $this->createUserWithPlayerCount(0);
        $request = $this->createRequestWithSession();
        $token = $this->createTokenWithUser($user);

        $this->urlGenerator->method('generate')
            ->with('app_character_create')
            ->willReturn('/game/character/create');

        /** @var RedirectResponse $response */
        $response = $this->authenticator->onAuthenticationSuccess($request, $token, 'main');

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame('/game/character/create', $response->getTargetUrl());
    }

    public function testRedirectsToGameWhenOnePlayer(): void
    {
        $user = $this->createUserWithPlayerCount(1);
        $request = $this->createRequestWithSession();
        $token = $this->createTokenWithUser($user);

        $this->urlGenerator->method('generate')
            ->with('app_game')
            ->willReturn('/game');

        /** @var RedirectResponse $response */
        $response = $this->authenticator->onAuthenticationSuccess($request, $token, 'main');

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame('/game', $response->getTargetUrl());
    }

    public function testRedirectsToCharacterSelectWhenMultiplePlayers(): void
    {
        $user = $this->createUserWithPlayerCount(2);
        $request = $this->createRequestWithSession();
        $token = $this->createTokenWithUser($user);

        $this->urlGenerator->method('generate')
            ->with('app_character_select')
            ->willReturn('/game/character/select');

        /** @var RedirectResponse $response */
        $response = $this->authenticator->onAuthenticationSuccess($request, $token, 'main');

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame('/game/character/select', $response->getTargetUrl());
    }

    private function createUserWithPlayerCount(int $count): User
    {
        $players = new ArrayCollection();
        for ($i = 0; $i < $count; ++$i) {
            $player = $this->createMock(Player::class);
            $players->add($player);
        }

        $user = $this->createMock(User::class);
        $user->method('getPlayers')->willReturn($players);

        return $user;
    }

    private function createRequestWithSession(): Request
    {
        $session = $this->createMock(SessionInterface::class);
        $session->method('get')->willReturn(null);

        $request = new Request();
        $request->setSession($session);

        return $request;
    }

    private function createTokenWithUser(User $user): TokenInterface
    {
        $token = $this->createMock(TokenInterface::class);
        $token->method('getUser')->willReturn($user);

        return $token;
    }
}
