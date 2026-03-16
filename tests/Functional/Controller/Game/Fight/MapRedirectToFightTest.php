<?php

namespace App\Tests\Functional\Controller\Game\Fight;

use App\Controller\Game\Map\IndexController;
use App\Entity\App\Fight;
use App\Entity\App\Map;
use App\Entity\App\Player;
use App\Helper\PlayerHelper;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class MapRedirectToFightTest extends TestCase
{
    private PlayerHelper&MockObject $playerHelper;
    private IndexController $controller;

    protected function setUp(): void
    {
        $this->playerHelper = $this->createMock(PlayerHelper::class);
        $this->controller = new IndexController($this->playerHelper);

        $authChecker = $this->createMock(\Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface::class);
        $authChecker->method('isGranted')->willReturn(true);

        $urlGenerator = $this->createMock(UrlGeneratorInterface::class);
        $urlGenerator->method('generate')->willReturnCallback(function (string $route) {
            return match ($route) {
                'app_game_fight' => '/game/fight',
                'app_dashboard' => '/dashboard',
                default => '/' . $route,
            };
        });

        $twig = $this->createMock(\Twig\Environment::class);
        $twig->method('render')->willReturn('<html>map</html>');

        $container = $this->createMock(\Symfony\Component\DependencyInjection\ContainerInterface::class);
        $container->method('has')->willReturnCallback(fn (string $id) => in_array($id, [
            'security.authorization_checker', 'router', 'twig',
        ]));
        $container->method('get')->willReturnCallback(function (string $id) use ($authChecker, $urlGenerator, $twig) {
            return match ($id) {
                'security.authorization_checker' => $authChecker,
                'router' => $urlGenerator,
                'twig' => $twig,
                default => null,
            };
        });
        $this->controller->setContainer($container);
    }

    public function testMapRedirectsToFightWhenPlayerHasActiveFight(): void
    {
        $fight = $this->createMock(Fight::class);
        $player = $this->createMock(Player::class);
        $player->method('getFight')->willReturn($fight);

        $this->playerHelper->method('getPlayer')->willReturn($player);

        $response = $this->controller->__invoke();

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertEquals('/game/fight', $response->getTargetUrl());
    }

    public function testMapDoesNotRedirectWhenNoFight(): void
    {
        $map = $this->createMock(Map::class);
        $map->method('getId')->willReturn(1);

        $player = $this->createMock(Player::class);
        $player->method('getFight')->willReturn(null);
        $player->method('getCoordinates')->willReturn('85.34');
        $player->method('getMap')->willReturn($map);
        $player->method('getId')->willReturn(1);

        $this->playerHelper->method('getPlayer')->willReturn($player);

        $response = $this->controller->__invoke();

        $this->assertNotInstanceOf(RedirectResponse::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testMapRedirectsToDashboardWhenNoPlayer(): void
    {
        $this->playerHelper->method('getPlayer')->willReturn(null);

        $response = $this->controller->__invoke();

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertEquals('/dashboard', $response->getTargetUrl());
    }
}
