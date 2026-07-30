<?php

namespace App\Security;

use App\Entity\App\Player;
use App\Entity\User;
use App\GameEngine\Tutorial\TutorialManager;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Core\Exception\TooManyLoginAttemptsAuthenticationException;
use Symfony\Component\Security\Http\Authenticator\AbstractLoginFormAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\CsrfTokenBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\RememberMeBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Credentials\PasswordCredentials;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\SecurityRequestAttributes;
use Symfony\Component\Security\Http\Util\TargetPathTrait;

class LoginFormAuthenticator extends AbstractLoginFormAuthenticator
{
    use TargetPathTrait;

    public const LOGIN_ROUTE = 'app_login';

    /**
     * ONB-03 — le seul message d'echec d'authentification.
     *
     * Mauvais mot de passe, adresse inconnue, compte banni : la reponse est la
     * meme. Un message par cause transforme l'ecran de connexion en oracle
     * d'existence de comptes, ce qui sert autant le harcelement que le bourrage
     * d'identifiants.
     */
    public const GENERIC_FAILURE_MESSAGE = 'Identifiants invalides.';

    public function __construct(
        private UrlGeneratorInterface $urlGenerator,
        private TutorialManager $tutorialManager,
    ) {
    }

    public function authenticate(Request $request): Passport
    {
        $email = $request->request->get('email', '');
        $request->getSession()->set(SecurityRequestAttributes::LAST_USERNAME, $email);

        return new Passport(
            new UserBadge($email),
            new PasswordCredentials($request->request->get('password', '')),
            [
                new CsrfTokenBadge('authenticate', $request->request->get('_csrf_token')),
                new RememberMeBadge(),
            ]
        );
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        if ($targetPath = $this->getTargetPath($request->getSession(), $firewallName)) {
            return new RedirectResponse($targetPath);
        }

        return new RedirectResponse($this->urlGenerator->generate($this->routeForState($token->getUser())));
    }

    /**
     * Toutes les causes d'echec se ressemblent, sauf l'attente.
     *
     * Le throttling fait exception : il ne dit rien du compte vise, et le taire
     * ferait ressembler un blocage temporaire a un mot de passe oublie — le
     * joueur reessaierait sans fin, ce que le limiteur cherche justement a
     * eviter.
     */
    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): Response
    {
        $public = $exception instanceof TooManyLoginAttemptsAuthenticationException
            ? $exception
            : new CustomUserMessageAuthenticationException(self::GENERIC_FAILURE_MESSAGE);

        if ($request->hasSession()) {
            $request->getSession()->set(SecurityRequestAttributes::AUTHENTICATION_ERROR, $public);
        }

        return new RedirectResponse($this->getLoginUrl($request));
    }

    protected function getLoginUrl(Request $request): string
    {
        return $this->urlGenerator->generate(self::LOGIN_ROUTE);
    }

    /**
     * ONB-03 — la connexion mene la ou le joueur en est, pas a un menu.
     *
     * Aucun personnage : le tunnel. Plusieurs : le choix. Un seul, mais l'acte I
     * n'est pas fini : l'ecran de zone, la ou l'acte se joue — le hub n'a rien a
     * raconter a quelqu'un qui n'a pas termine son introduction.
     */
    private function routeForState(mixed $user): string
    {
        if (!$user instanceof User) {
            return 'app_game';
        }

        $players = $user->getPlayers();

        if ($players->count() === 0) {
            return 'app_character_create';
        }

        if ($players->count() > 1) {
            return 'app_character_select';
        }

        $player = $players->first();

        // ONB-14 : l'etat d'onboarding se lit a un seul endroit. Le tester ici
        // sur une colonne revenait a en dependre sans passer par la source.
        if ($player instanceof Player && $this->tutorialManager->isInTutorial($player)) {
            return 'app_game_zone';
        }

        return 'app_game';
    }
}
