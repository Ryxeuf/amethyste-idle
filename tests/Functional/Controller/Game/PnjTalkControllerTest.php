<?php

namespace App\Tests\Functional\Controller\Game;

use App\Controller\Game\PnjTalkController;
use App\Entity\App\Player;
use App\Entity\App\Pnj;
use App\Entity\App\Zone;
use App\Event\Game\PnjDialogEvent;
use App\Helper\PlayerHelper;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Twig\Environment as TwigEnvironment;

/**
 * ZON-27b : le dialogue PNJ est le seul emetteur de `PnjDialogEvent`, dont
 * depend la progression des objectifs de quete `talk_to`.
 */
class PnjTalkControllerTest extends TestCase
{
    private PlayerHelper&MockObject $playerHelper;
    private EntityManagerInterface&MockObject $entityManager;
    private EntityRepository&MockObject $pnjRepository;
    private EventDispatcherInterface&MockObject $eventDispatcher;
    private PnjTalkController $controller;

    /** @var array<string, mixed>|null */
    private ?array $capturedTemplateParams = null;

    /** @var list<object> */
    private array $dispatchedEvents = [];

    protected function setUp(): void
    {
        $this->playerHelper = $this->createMock(PlayerHelper::class);
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->pnjRepository = $this->createMock(EntityRepository::class);
        $this->entityManager->method('getRepository')->willReturn($this->pnjRepository);

        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $this->eventDispatcher->method('dispatch')->willReturnCallback(function (object $event): object {
            $this->dispatchedEvents[] = $event;

            return $event;
        });

        $this->controller = new PnjTalkController(
            $this->playerHelper,
            $this->entityManager,
            $this->eventDispatcher,
        );
        $this->controller->setContainer($this->createContainer());
    }

    public function testTalkingDispatchesDialogEventAndRendersFirstNode(): void
    {
        $zone = $this->buildZone(1);
        $player = $this->buildPlayerIn($zone);
        $pnj = $this->buildPnj($zone, [
            ['text' => 'Bonjour, voyageur.', 'choices' => [['text' => 'Continuer', 'action' => 'next']]],
            ['text' => 'Bonne route.'],
        ]);

        $this->playerHelper->method('getPlayer')->willReturn($player);
        $this->pnjRepository->method('find')->willReturn($pnj);

        $response = $this->controller->talk(1, new Request());

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('Bonjour, voyageur.', $this->capturedTemplateParams['node']['text']);
        $this->assertTrue($this->capturedTemplateParams['hasNextNode']);

        $dialogEvents = array_filter($this->dispatchedEvents, fn ($e) => $e instanceof PnjDialogEvent);
        $this->assertCount(1, $dialogEvents, 'Parler a un PNJ doit emettre exactement un PnjDialogEvent.');
        $this->assertSame($pnj, array_values($dialogEvents)[0]->getPnj());
        $this->assertSame($player, array_values($dialogEvents)[0]->getPlayer());
    }

    public function testTalkingToAPnjFromAnotherZoneIsRefused(): void
    {
        $player = $this->buildPlayerIn($this->buildZone(2));
        $pnj = $this->buildPnj($this->buildZone(1), [['text' => 'Bonjour.']]);

        $this->playerHelper->method('getPlayer')->willReturn($player);
        $this->pnjRepository->method('find')->willReturn($pnj);

        $this->expectException(NotFoundHttpException::class);
        $this->controller->talk(1, new Request());
    }

    public function testOutOfRangeNodeFallsBackToFirst(): void
    {
        $zone = $this->buildZone(1);
        $player = $this->buildPlayerIn($zone);
        $pnj = $this->buildPnj($zone, [['text' => 'Premier noeud.']]);

        $this->playerHelper->method('getPlayer')->willReturn($player);
        $this->pnjRepository->method('find')->willReturn($pnj);

        $this->controller->talk(1, new Request(['node' => '99']));

        $this->assertSame(0, $this->capturedTemplateParams['nodeIndex']);
        $this->assertSame('Premier noeud.', $this->capturedTemplateParams['node']['text']);
        $this->assertFalse($this->capturedTemplateParams['hasNextNode']);
    }

    public function testUnknownPnjIsRefused(): void
    {
        $this->playerHelper->method('getPlayer')->willReturn($this->buildPlayerIn($this->buildZone(1)));
        $this->pnjRepository->method('find')->willReturn(null);

        $this->expectException(NotFoundHttpException::class);
        $this->controller->talk(404, new Request());
    }

    private function buildZone(int $id): Zone&MockObject
    {
        $zone = $this->createMock(Zone::class);
        $zone->method('getId')->willReturn($id);

        return $zone;
    }

    private function buildPlayerIn(Zone $zone): Player&MockObject
    {
        $player = $this->createMock(Player::class);
        $player->method('getCurrentZone')->willReturn($zone);

        return $player;
    }

    /**
     * @param array<int, array<string, mixed>> $dialog
     */
    private function buildPnj(Zone $zone, array $dialog): Pnj&MockObject
    {
        $pnj = $this->createMock(Pnj::class);
        $pnj->method('getZone')->willReturn($zone);
        $pnj->method('getDialog')->willReturn($dialog);

        return $pnj;
    }

    private function createContainer(): ContainerInterface&MockObject
    {
        $authChecker = $this->createMock(AuthorizationCheckerInterface::class);
        $authChecker->method('isGranted')->willReturn(true);

        $twig = $this->createMock(TwigEnvironment::class);
        $twig->method('render')->willReturnCallback(function (string $view, array $params): string {
            $this->capturedTemplateParams = $params;

            return '<html></html>';
        });

        $services = [
            'security.authorization_checker' => $authChecker,
            'twig' => $twig,
        ];

        $container = $this->createMock(ContainerInterface::class);
        $container->method('has')->willReturnCallback(fn (string $id) => isset($services[$id]));
        $container->method('get')->willReturnCallback(fn (string $id) => $services[$id] ?? null);

        return $container;
    }
}
