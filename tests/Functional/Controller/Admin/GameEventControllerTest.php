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
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBag;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class GameEventControllerTest extends TestCase
{
    private EntityManagerInterface&MockObject $em;
    private AdminLogger&MockObject $adminLogger;
    private EventDispatcherInterface&MockObject $eventDispatcher;
    private CsrfTokenManagerInterface&MockObject $csrfManager;
    private RequestStack $requestStack;
    private GameEventController $controller;

    protected function setUp(): void
    {
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->adminLogger = $this->createMock(AdminLogger::class);
        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $this->csrfManager = $this->createMock(CsrfTokenManagerInterface::class);

        $this->requestStack = new RequestStack();
        $session = new Session(new MockArraySessionStorage());
        $session->setFlashBag(new FlashBag());

        $this->controller = new GameEventController($this->em, $this->adminLogger, $this->eventDispatcher);

        $container = $this->createMock(ContainerInterface::class);
        $container->method('has')->willReturnCallback(
            fn (string $id): bool => in_array($id, ['security.csrf.token_manager', 'router', 'request_stack'], true)
        );
        $container->method('get')->willReturnCallback(function (string $id) use ($session) {
            if ($id === 'security.csrf.token_manager') {
                return $this->csrfManager;
            }
            if ($id === 'request_stack') {
                return $this->requestStack;
            }
            if ($id === 'router') {
                $router = $this->createMock(\Symfony\Component\Routing\RouterInterface::class);
                $router->method('generate')->willReturn('/admin/events');

                return $router;
            }

            return null;
        });
        $this->controller->setContainer($container);

        $request = new Request();
        $request->setSession($session);
        $this->requestStack->push($request);
    }

    public function testLaunchNowShiftsTimesAndActivatesEvent(): void
    {
        $event = $this->makeScheduledEvent(durationSeconds: 3600);

        $this->csrfManager->method('isTokenValid')->willReturnCallback(
            fn (CsrfToken $t): bool => $t->getId() === 'launch_now42' && $t->getValue() === 'valid-token'
        );

        $this->em->expects($this->once())->method('flush');
        $this->eventDispatcher->expects($this->once())
            ->method('dispatch')
            ->with(
                $this->isInstanceOf(GameEventActivatedEvent::class),
                GameEventActivatedEvent::NAME
            );
        $this->adminLogger->expects($this->once())
            ->method('log')
            ->with('launch_now', 'GameEvent', 42, 'Festival test');

        $request = new Request(request: ['_token' => 'valid-token']);
        $request->setMethod('POST');

        $beforeTs = time();
        $response = $this->controller->launchNow($request, $event);
        $afterTs = time();

        $this->assertSame(302, $response->getStatusCode());
        $this->assertSame(GameEvent::STATUS_ACTIVE, $event->getStatus());
        $this->assertGreaterThanOrEqual($beforeTs, $event->getStartsAt()->getTimestamp());
        $this->assertLessThanOrEqual($afterTs, $event->getStartsAt()->getTimestamp());
        $this->assertSame(3600, $event->getEndsAt()->getTimestamp() - $event->getStartsAt()->getTimestamp());
    }

    public function testLaunchNowRejectsInvalidCsrf(): void
    {
        $event = $this->makeScheduledEvent();

        $this->csrfManager->method('isTokenValid')->willReturn(false);

        $this->em->expects($this->never())->method('flush');
        $this->eventDispatcher->expects($this->never())->method('dispatch');
        $this->adminLogger->expects($this->never())->method('log');

        $request = new Request(request: ['_token' => 'bad']);
        $request->setMethod('POST');

        $response = $this->controller->launchNow($request, $event);

        $this->assertSame(302, $response->getStatusCode());
        $this->assertSame(GameEvent::STATUS_SCHEDULED, $event->getStatus());
    }

    public function testLaunchNowRefusesAlreadyActiveEvent(): void
    {
        $event = $this->makeScheduledEvent();
        $event->setStatus(GameEvent::STATUS_ACTIVE);

        $this->csrfManager->method('isTokenValid')->willReturn(true);

        $this->em->expects($this->never())->method('flush');
        $this->eventDispatcher->expects($this->never())->method('dispatch');

        $request = new Request(request: ['_token' => 'valid-token']);
        $request->setMethod('POST');

        $response = $this->controller->launchNow($request, $event);

        $this->assertSame(302, $response->getStatusCode());
        $this->assertSame(GameEvent::STATUS_ACTIVE, $event->getStatus());
    }

    public function testLaunchNowFallsBackToOneHourWhenDurationNotPositive(): void
    {
        $event = $this->makeScheduledEvent(durationSeconds: 0);

        $this->csrfManager->method('isTokenValid')->willReturn(true);

        $this->em->expects($this->once())->method('flush');
        $this->eventDispatcher->expects($this->once())->method('dispatch');
        $this->adminLogger->expects($this->once())->method('log');

        $request = new Request(request: ['_token' => 'valid-token']);
        $request->setMethod('POST');

        $this->controller->launchNow($request, $event);

        $this->assertSame(3600, $event->getEndsAt()->getTimestamp() - $event->getStartsAt()->getTimestamp());
    }

    private function makeScheduledEvent(int $durationSeconds = 1800): GameEvent
    {
        $event = new GameEvent();
        $ref = new \ReflectionClass($event);
        $idProp = $ref->getProperty('id');
        $idProp->setAccessible(true);
        $idProp->setValue($event, 42);

        $event->setName('Festival test');
        $event->setType(GameEvent::TYPE_XP_BONUS);
        $event->setStatus(GameEvent::STATUS_SCHEDULED);
        $event->setStartsAt(new \DateTime('2030-01-01 10:00:00'));
        $event->setEndsAt((new \DateTime('2030-01-01 10:00:00'))->modify('+' . $durationSeconds . ' seconds'));
        $event->setCreatedAt(new \DateTime());
        $event->setUpdatedAt(new \DateTime());

        return $event;
    }
}
