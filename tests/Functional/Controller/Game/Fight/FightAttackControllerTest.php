<?php

namespace App\Tests\Functional\Controller\Game\Fight;

use App\Controller\Game\Fight\FightAttackController;
use App\Entity\App\Fight;
use App\Entity\App\Mob;
use App\Entity\App\Player;
use App\Entity\Game\Monster;
use App\Entity\Game\Spell;
use App\GameEngine\Fight\MobActionHandler;
use App\Helper\PlayerHelper;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class FightAttackControllerTest extends TestCase
{
    private PlayerHelper&MockObject $playerHelper;
    private MobActionHandler&MockObject $mobActionHandler;
    private EntityManagerInterface&MockObject $entityManager;
    private FightAttackController $controller;

    protected function setUp(): void
    {
        $this->playerHelper = $this->createMock(PlayerHelper::class);
        $this->mobActionHandler = $this->createMock(MobActionHandler::class);
        $this->entityManager = $this->createMock(EntityManagerInterface::class);

        $this->controller = new FightAttackController(
            $this->playerHelper,
            $this->mobActionHandler,
            $this->entityManager,
        );

        // Stub the container for security checks
        $authChecker = $this->createMock(AuthorizationCheckerInterface::class);
        $authChecker->method('isGranted')->willReturn(true);

        $container = $this->createMock(\Symfony\Component\DependencyInjection\ContainerInterface::class);
        $container->method('has')->willReturn(true);
        $container->method('get')->willReturnCallback(function (string $id) use ($authChecker) {
            if ($id === 'security.authorization_checker') {
                return $authChecker;
            }

            return null;
        });
        $this->controller->setContainer($container);
    }

    public function testAttackReturnsNotFoundWhenNoPlayer(): void
    {
        $this->playerHelper->method('getPlayer')->willReturn(null);

        $request = $this->createJsonRequest(['targetId' => 1, 'targetType' => 'mob']);
        $response = $this->controller->__invoke($request);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(404, $response->getStatusCode());
        $this->assertStringContainsString('Player not found', $response->getContent());
    }

    public function testAttackReturnsNotFoundWhenNoFight(): void
    {
        $player = $this->createPlayerMock(fight: null);
        $this->playerHelper->method('getPlayer')->willReturn($player);

        $request = $this->createJsonRequest(['targetId' => 1, 'targetType' => 'mob']);
        $response = $this->controller->__invoke($request);

        $this->assertEquals(404, $response->getStatusCode());
        $this->assertStringContainsString('Fight not found', $response->getContent());
    }

    public function testAttackReturnsBadRequestWhenInvalidData(): void
    {
        $fight = $this->createFightMock();
        $player = $this->createPlayerMock(fight: $fight);
        $this->playerHelper->method('getPlayer')->willReturn($player);

        $request = $this->createJsonRequest([]);
        $response = $this->controller->__invoke($request);

        $this->assertEquals(400, $response->getStatusCode());
    }

    public function testAttackReturnsBadRequestWhenMissingTargetType(): void
    {
        $fight = $this->createFightMock();
        $player = $this->createPlayerMock(fight: $fight);
        $this->playerHelper->method('getPlayer')->willReturn($player);

        $request = $this->createJsonRequest(['targetId' => 1]);
        $response = $this->controller->__invoke($request);

        $this->assertEquals(400, $response->getStatusCode());
    }

    public function testAttackReturnsNotFoundWhenTargetNotInFight(): void
    {
        $fight = $this->createFightMock(mobs: [], players: []);
        $player = $this->createPlayerMock(id: 1, fight: $fight);
        $this->playerHelper->method('getPlayer')->willReturn($player);

        $request = $this->createJsonRequest(['targetId' => 999, 'targetType' => 'mob']);
        $response = $this->controller->__invoke($request);

        $this->assertEquals(404, $response->getStatusCode());
        $this->assertStringContainsString('Target not found', $response->getContent());
    }

    public function testAttackDealsDamageToMob(): void
    {
        $mobLife = 20;
        $mob = $this->createMobMock(id: 5, life: $mobLife);
        $fight = $this->createFightMock(mobs: [$mob]);
        $player = $this->createPlayerMock(id: 1, fight: $fight, hit: 3);

        $this->playerHelper->method('getPlayer')->willReturn($player);
        $this->mobActionHandler->method('doAction')->willReturn(['messages' => [], 'dangerAlert' => null]);

        $mob->expects($this->once())->method('setLife')->with(17);

        $request = $this->createJsonRequest(['targetId' => 5, 'targetType' => 'mob']);
        $response = $this->controller->__invoke($request);

        $data = json_decode($response->getContent(), true);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertTrue($data['success']);
        $this->assertNotEmpty($data['messages']);
    }

    public function testAttackKillsMob(): void
    {
        $mob = $this->createMobMock(id: 5, life: 2);
        $fight = $this->createFightMock(mobs: [$mob], terminated: true, victory: true);
        $player = $this->createPlayerMock(id: 1, fight: $fight, hit: 5);

        $this->playerHelper->method('getPlayer')->willReturn($player);

        // When mob is killed, setLife(0) and setDiedAt are called
        $mob->expects($this->once())->method('setLife')->with(0);
        $mob->expects($this->once())->method('setDiedAt');

        // Mob doesn't play after death
        $this->mobActionHandler->expects($this->never())->method('doAction');

        $request = $this->createJsonRequest(['targetId' => 5, 'targetType' => 'mob']);
        $response = $this->controller->__invoke($request);

        $data = json_decode($response->getContent(), true);
        $this->assertTrue($data['success']);
        $this->assertTrue($data['fight']['terminated']);
        $this->assertTrue($data['fight']['victory']);
    }

    public function testAttackTargetIdCastToIntFromStringJson(): void
    {
        $mob = $this->createMobMock(id: 5, life: 20);
        $fight = $this->createFightMock(mobs: [$mob]);
        $player = $this->createPlayerMock(id: 1, fight: $fight, hit: 3);

        $this->playerHelper->method('getPlayer')->willReturn($player);
        $this->mobActionHandler->method('doAction')->willReturn(['messages' => [], 'dangerAlert' => null]);

        // Send targetId as string (like it comes from JSON when JS sends a string)
        $request = Request::create('/game/fight/attack', 'POST', [], [], [], [], json_encode([
            'targetId' => '5',
            'targetType' => 'mob',
        ]));
        $response = $this->controller->__invoke($request);

        $this->assertEquals(200, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertTrue($data['success']);
    }

    public function testMobPlaysAfterPlayerAttack(): void
    {
        $mob = $this->createMobMock(id: 5, life: 20);
        $fight = $this->createFightMock(mobs: [$mob]);
        $player = $this->createPlayerMock(id: 1, fight: $fight, hit: 3);

        $this->playerHelper->method('getPlayer')->willReturn($player);
        $this->mobActionHandler->expects($this->once())->method('doAction')
            ->with($fight)
            ->willReturn(['messages' => ['Slime attaque !'], 'dangerAlert' => null]);

        $request = $this->createJsonRequest(['targetId' => 5, 'targetType' => 'mob']);
        $response = $this->controller->__invoke($request);

        $data = json_decode($response->getContent(), true);
        $this->assertContains('Slime attaque !', $data['messages']);
    }

    public function testAttackAdvancesFightStep(): void
    {
        $mob = $this->createMobMock(id: 5, life: 20);
        $fight = $this->createFightMock(mobs: [$mob], step: 0);
        $player = $this->createPlayerMock(id: 1, fight: $fight, hit: 3);

        $this->playerHelper->method('getPlayer')->willReturn($player);
        $this->mobActionHandler->method('doAction')->willReturn(['messages' => [], 'dangerAlert' => null]);

        // Fight step should be incremented twice (player + mob turn)
        $fight->expects($this->exactly(2))->method('setStep');

        $request = $this->createJsonRequest(['targetId' => 5, 'targetType' => 'mob']);
        $this->controller->__invoke($request);
    }

    public function testAttackFlushesEntityManager(): void
    {
        $mob = $this->createMobMock(id: 5, life: 20);
        $fight = $this->createFightMock(mobs: [$mob]);
        $player = $this->createPlayerMock(id: 1, fight: $fight, hit: 3);

        $this->playerHelper->method('getPlayer')->willReturn($player);
        $this->mobActionHandler->method('doAction')->willReturn(['messages' => [], 'dangerAlert' => null]);

        $this->entityManager->expects($this->once())->method('flush');

        $request = $this->createJsonRequest(['targetId' => 5, 'targetType' => 'mob']);
        $this->controller->__invoke($request);
    }

    private function createJsonRequest(array $data): Request
    {
        return Request::create('/game/fight/attack', 'POST', [], [], [], [], json_encode($data));
    }

    private function createPlayerMock(int $id = 1, ?Fight $fight = null, int $hit = 5, int $life = 50, string $name = 'TestPlayer'): Player&MockObject
    {
        $player = $this->createMock(Player::class);
        $player->method('getId')->willReturn($id);
        $player->method('getFight')->willReturn($fight);
        $player->method('getHit')->willReturn($hit);
        $player->method('getLife')->willReturn($life);
        $player->method('getName')->willReturn($name);

        return $player;
    }

    private function createMobMock(int $id = 1, int $life = 10, string $name = 'TestMob'): Mob&MockObject
    {
        $currentLife = $life;
        $mob = $this->createMock(Mob::class);
        $mob->method('getId')->willReturn($id);
        $mob->method('getLife')->willReturnCallback(function () use (&$currentLife) {
            return $currentLife;
        });
        $mob->method('setLife')->willReturnCallback(function (int $newLife) use (&$currentLife) {
            $currentLife = $newLife;
        });
        $mob->method('getName')->willReturn($name);
        $mob->method('isDead')->willReturnCallback(function () use (&$currentLife) {
            return $currentLife <= 0;
        });

        return $mob;
    }

    private function createFightMock(
        array $mobs = [],
        array $players = [],
        int $step = 0,
        bool $terminated = false,
        bool $victory = false,
    ): Fight&MockObject {
        $fight = $this->createMock(Fight::class);
        $fight->method('getMobs')->willReturn(new ArrayCollection($mobs));
        $fight->method('getPlayers')->willReturn(new ArrayCollection($players));
        $fight->method('getStep')->willReturn($step);
        $fight->method('isTerminated')->willReturn($terminated);
        $fight->method('isVictory')->willReturn($victory);

        return $fight;
    }
}
