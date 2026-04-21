<?php

namespace App\Tests\Functional\Controller\Admin;

use App\Controller\Admin\GameEventController;
use App\Entity\App\GameEvent;
use App\Event\Game\GameEventActivatedEvent;
use App\Service\AdminLogger;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBag;
use Symfony\Component\HttpFoundation\Session\FlashBagAwareSessionInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * Verifie la chaine admin toggle -> dispatch GameEventActivatedEvent.
 *
 * Couvre la sous-phase 4 de la tache 131 : l'annonce Mercure globale
 * (topic `event/announce`) est publiee par `GameEventAnnouncementHandler`
 * qui souscrit a `GameEventActivatedEvent`. Ce test ferme le maillon
 * manquant : quand un admin bascule SCHEDULED -> ACTIVE via le bouton
 * "Activer", l'event est bien dispatche (et donc le subscriber declenche).
 */
class GameEventControllerToggleTest extends TestCase
{
    private EntityManagerInterface&MockObject $em;
    private AdminLogger&MockObject $adminLogger;
    private EventDispatcherInterface&MockObject $eventDispatcher;
    private CsrfTokenManagerInterface&MockObject $csrfTokenManager;
    private FlashBag $flashBag;
    private GameEventController $controller;

    protected function setUp(): void
    {
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->adminLogger = $this->createMock(AdminLogger::class);
        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $this->csrfTokenManager = $this->createMock(CsrfTokenManagerInterface::class);
        $this->flashBag = new FlashBag();

        $this->controller = new GameEventController(
            $this->em,
            $this->adminLogger,
            $this->eventDispatcher,
        );

        $this->controller->setContainer($this->createContainer());
    }

    public function testToggleFromScheduledToActiveDispatchesActivatedEvent(): void
    {
        $event = $this->makeEvent(42, GameEvent::STATUS_SCHEDULED);

        $this->csrfTokenManager->method('isTokenValid')
            ->willReturnCallback(fn (CsrfToken $token) => $token->getId() === 'toggle42' && $token->getValue() === 'valid-token');

        $this->em->expects($this->once())->method('flush');

        $this->eventDispatcher->expects($this->once())
            ->method('dispatch')
            ->with(
                $this->callback(fn (GameEventActivatedEvent $e) => $e->getGameEvent() === $event),
                GameEventActivatedEvent::NAME,
            );

        $this->adminLogger->expects($this->once())
            ->method('log')
            ->with('toggle', 'GameEvent', 42, $this->stringContains(GameEvent::STATUS_ACTIVE));

        $request = Request::create('/admin/events/42/toggle', 'POST', ['_token' => 'valid-token']);

        $response = $this->controller->toggle($request, $event);

        $this->assertSame(302, $response->getStatusCode());
        $this->assertSame(GameEvent::STATUS_ACTIVE, $event->getStatus());
    }

    public function testToggleFromActiveToCompletedDoesNotDispatchActivatedEvent(): void
    {
        $event = $this->makeEvent(7, GameEvent::STATUS_ACTIVE);

        $this->csrfTokenManager->method('isTokenValid')->willReturn(true);

        $this->em->expects($this->once())->method('flush');
        $this->eventDispatcher->expects($this->never())->method('dispatch');

        $request = Request::create('/admin/events/7/toggle', 'POST', ['_token' => 'valid-token']);

        $response = $this->controller->toggle($request, $event);

        $this->assertSame(302, $response->getStatusCode());
        $this->assertSame(GameEvent::STATUS_COMPLETED, $event->getStatus());
    }

    public function testToggleWithInvalidCsrfDoesNothing(): void
    {
        $event = $this->makeEvent(99, GameEvent::STATUS_SCHEDULED);

        $this->csrfTokenManager->method('isTokenValid')->willReturn(false);

        $this->em->expects($this->never())->method('flush');
        $this->eventDispatcher->expects($this->never())->method('dispatch');
        $this->adminLogger->expects($this->never())->method('log');

        $request = Request::create('/admin/events/99/toggle', 'POST', ['_token' => 'wrong']);

        $response = $this->controller->toggle($request, $event);

        $this->assertSame(302, $response->getStatusCode());
        $this->assertSame(GameEvent::STATUS_SCHEDULED, $event->getStatus());
    }

    private function makeEvent(int $id, string $status): GameEvent
    {
        $event = new GameEvent();
        $event->setName('Test event');
        $event->setType(GameEvent::TYPE_XP_BONUS);
        $event->setStatus($status);
        $event->setStartsAt(new \DateTime('now'));
        $event->setEndsAt(new \DateTime('+1 hour'));

        $ref = new \ReflectionProperty(GameEvent::class, 'id');
        $ref->setValue($event, $id);

        return $event;
    }

    private function createContainer(): ContainerInterface&MockObject
    {
        $router = $this->createMock(UrlGeneratorInterface::class);
        $router->method('generate')->willReturn('/admin/events');

        $session = $this->createMock(FlashBagAwareSessionInterface::class);
        $session->method('getFlashBag')->willReturn($this->flashBag);

        $requestStack = $this->createMock(\Symfony\Component\HttpFoundation\RequestStack::class);
        $requestStack->method('getSession')->willReturn($session);

        $services = [
            'router' => $router,
            'request_stack' => $requestStack,
            'security.csrf.token_manager' => $this->csrfTokenManager,
        ];

        $container = $this->createMock(ContainerInterface::class);
        $container->method('has')->willReturnCallback(fn (string $id) => isset($services[$id]));
        $container->method('get')->willReturnCallback(fn (string $id) => $services[$id] ?? null);

        return $container;
    }
}
