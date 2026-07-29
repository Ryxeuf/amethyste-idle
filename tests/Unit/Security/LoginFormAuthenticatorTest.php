<?php

namespace App\Tests\Unit\Security;

use App\Entity\App\Player;
use App\Entity\User;
use App\Security\LoginFormAuthenticator;
use Doctrine\Common\Collections\ArrayCollection;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAccountStatusException;
use Symfony\Component\Security\Core\Exception\TooManyLoginAttemptsAuthenticationException;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Http\SecurityRequestAttributes;

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

    /**
     * ONB-03 — le hub n'a rien a raconter a qui n'a pas fini son acte I.
     */
    public function testRedirectsToTheZoneScreenWhenTheIntroIsUnfinished(): void
    {
        $player = new Player();
        $player->setTutorialStep(2);

        $user = $this->createMock(User::class);
        $user->method('getPlayers')->willReturn(new ArrayCollection([$player]));

        $this->urlGenerator->method('generate')
            ->with('app_game_zone')
            ->willReturn('/game/zone');

        /** @var RedirectResponse $response */
        $response = $this->authenticator->onAuthenticationSuccess(
            $this->createRequestWithSession(),
            $this->createTokenWithUser($user),
            'main',
        );

        $this->assertSame('/game/zone', $response->getTargetUrl());
    }

    /**
     * ONB-03 — mauvais mot de passe, adresse inconnue et compte banni se
     * ressemblent : sinon l'ecran de connexion devient un oracle d'existence.
     *
     * @return iterable<string, array{AuthenticationException}>
     */
    public static function indistinguishableFailures(): iterable
    {
        yield 'mauvais mot de passe' => [new BadCredentialsException()];
        yield 'adresse inconnue' => [new UserNotFoundException()];
        yield 'compte banni' => [new CustomUserMessageAccountStatusException('Suspendu depuis le 3 mars')];
    }

    #[DataProvider('indistinguishableFailures')]
    public function testEveryCauseOfFailureReadsTheSame(AuthenticationException $exception): void
    {
        $session = new Session(new MockArraySessionStorage());
        $request = new Request();
        $request->setSession($session);

        $this->urlGenerator->method('generate')->willReturn('/login');
        $this->authenticator->onAuthenticationFailure($request, $exception);

        /** @var AuthenticationException $stored */
        $stored = $session->get(SecurityRequestAttributes::AUTHENTICATION_ERROR);
        $this->assertSame(LoginFormAuthenticator::GENERIC_FAILURE_MESSAGE, $stored->getMessageKey());
    }

    /**
     * Seule exception : taire l'attente ferait reessayer sans fin, ce que le
     * limiteur cherche justement a eviter.
     */
    public function testThrottlingKeepsItsOwnMessage(): void
    {
        $session = new Session(new MockArraySessionStorage());
        $request = new Request();
        $request->setSession($session);
        $exception = new TooManyLoginAttemptsAuthenticationException(15);

        $this->urlGenerator->method('generate')->willReturn('/login');
        $this->authenticator->onAuthenticationFailure($request, $exception);

        $this->assertSame($exception, $session->get(SecurityRequestAttributes::AUTHENTICATION_ERROR));
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
