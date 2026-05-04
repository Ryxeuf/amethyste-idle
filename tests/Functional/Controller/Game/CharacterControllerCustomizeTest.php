<?php

namespace App\Tests\Functional\Controller\Game;

use App\Controller\Game\CharacterController;
use App\Helper\PlayerHelper;
use App\Service\Avatar\AvatarHashRecalculator;
use App\Service\ForbiddenNameChecker;
use App\Service\PlayerFactory;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class CharacterControllerCustomizeTest extends TestCase
{
    private PlayerHelper&MockObject $playerHelper;
    private EntityManagerInterface&MockObject $entityManager;
    private AvatarHashRecalculator&MockObject $recalculator;
    private CharacterController $controller;

    protected function setUp(): void
    {
        $this->playerHelper = $this->createMock(PlayerHelper::class);
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->recalculator = $this->createMock(AvatarHashRecalculator::class);

        $this->controller = new CharacterController(
            $this->createMock(PlayerFactory::class),
            $this->playerHelper,
            $this->entityManager,
            $this->createMock(ForbiddenNameChecker::class),
            $this->recalculator,
            1,
        );

        $this->controller->setContainer($this->createContainer());
    }

    public function testCustomizeRedirectsWhenNoActivePlayer(): void
    {
        $this->playerHelper->expects($this->once())->method('getPlayer')->willReturn(null);

        $this->entityManager->expects($this->never())->method('flush');
        $this->recalculator->expects($this->never())->method('recalculate');

        $response = $this->controller->customize(new Request());

        $this->assertEquals(Response::HTTP_FOUND, $response->getStatusCode());
        $this->assertSame('/game', $response->headers->get('Location'));
    }

    private function createContainer(): ContainerInterface&MockObject
    {
        $authChecker = $this->createMock(AuthorizationCheckerInterface::class);
        $authChecker->method('isGranted')->willReturn(true);

        $router = $this->createMock(UrlGeneratorInterface::class);
        $router->method('generate')->willReturn('/game');

        $services = [
            'security.authorization_checker' => $authChecker,
            'router' => $router,
        ];

        $container = $this->createMock(ContainerInterface::class);
        $container->method('has')->willReturnCallback(fn (string $id) => isset($services[$id]));
        $container->method('get')->willReturnCallback(fn (string $id) => $services[$id] ?? null);

        return $container;
    }
}
