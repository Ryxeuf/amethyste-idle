<?php

declare(strict_types=1);

namespace App\Tests\Unit\EventListener;

use App\Entity\User;
use App\EventListener\BannedUserSubscriber;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * ONB-03 — un bannissement prend effet tout de suite.
 *
 * `UserChecker` ferme la porte d'entree ; il ne voit rien de la session deja
 * ouverte, qui survivrait jusqu'a un mois avec le « se souvenir de moi ».
 */
class BannedUserSubscriberTest extends TestCase
{
    public function testBannedSessionIsClosedAndSentBackToLogin(): void
    {
        $security = $this->securityFor((new User())->setIsBanned(true));
        $security->expects($this->once())->method('logout')->with(false);

        $event = $this->requestEvent('app_game_zone');
        (new BannedUserSubscriber($security, $this->router()))->onKernelRequest($event);

        $response = $event->getResponse();
        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame('/login', $response->getTargetUrl());
    }

    public function testBanMessageIsShownOnceTheSessionIsClosed(): void
    {
        $event = $this->requestEvent('app_game_zone');

        (new BannedUserSubscriber($this->securityFor((new User())->setIsBanned(true)), $this->router()))
            ->onKernelRequest($event);

        /** @var Session $session */
        $session = $event->getRequest()->getSession();
        $this->assertSame([BannedUserSubscriber::BAN_MESSAGE], $session->getFlashBag()->get('error'));
    }

    public function testRegularUserIsLeftAlone(): void
    {
        $security = $this->securityFor((new User())->setIsBanned(false));
        $security->expects($this->never())->method('logout');

        $event = $this->requestEvent('app_game_zone');
        (new BannedUserSubscriber($security, $this->router()))->onKernelRequest($event);

        $this->assertNull($event->getResponse());
    }

    public function testAnonymousVisitorIsLeftAlone(): void
    {
        $security = $this->securityFor(null);
        $security->expects($this->never())->method('logout');

        $event = $this->requestEvent('app_home');
        (new BannedUserSubscriber($security, $this->router()))->onKernelRequest($event);

        $this->assertNull($event->getResponse());
    }

    /**
     * Sans ces exemptions la redirection tourne en rond, et la session bannie ne
     * peut jamais se fermer.
     *
     * @return iterable<string, array{string}>
     */
    public static function routesThatMustStayReachable(): iterable
    {
        yield 'connexion' => ['app_login'];
        yield 'deconnexion' => ['app_logout'];
        yield 'accueil public' => ['app_home'];
    }

    #[DataProvider('routesThatMustStayReachable')]
    public function testTheWayOutStaysOpen(string $route): void
    {
        $event = $this->requestEvent($route);

        (new BannedUserSubscriber($this->securityFor((new User())->setIsBanned(true)), $this->router()))
            ->onKernelRequest($event);

        $this->assertNull($event->getResponse());
    }

    public function testSubRequestsAreIgnored(): void
    {
        $security = $this->securityFor((new User())->setIsBanned(true));
        $security->expects($this->never())->method('logout');

        $event = $this->requestEvent('app_game_zone', HttpKernelInterface::SUB_REQUEST);
        (new BannedUserSubscriber($security, $this->router()))->onKernelRequest($event);

        $this->assertNull($event->getResponse());
    }

    private function securityFor(?User $user): Security
    {
        $security = $this->createMock(Security::class);
        $security->method('getUser')->willReturn($user);

        return $security;
    }

    private function router(): UrlGeneratorInterface
    {
        $router = $this->createMock(UrlGeneratorInterface::class);
        $router->method('generate')->willReturn('/login');

        return $router;
    }

    private function requestEvent(string $route, int $type = HttpKernelInterface::MAIN_REQUEST): RequestEvent
    {
        $request = new Request();
        $request->attributes->set('_route', $route);
        $request->setSession(new Session(new MockArraySessionStorage()));

        return new RequestEvent($this->createMock(HttpKernelInterface::class), $request, $type);
    }
}
