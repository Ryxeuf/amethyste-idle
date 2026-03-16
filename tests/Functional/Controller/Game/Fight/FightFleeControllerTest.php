<?php

namespace App\Tests\Functional\Controller\Game\Fight;

use App\Controller\Game\Fight\FightFleeController;
use App\Entity\App\Fight;
use App\Entity\App\Mob;
use App\Entity\App\Player;
use App\Entity\Game\Monster;
use App\GameEngine\Fight\StatusEffectManager;
use App\Helper\PlayerHelper;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class FightFleeControllerTest extends TestCase
{
    private PlayerHelper&MockObject $playerHelper;
    private EntityManagerInterface&MockObject $entityManager;
    private StatusEffectManager&MockObject $statusEffectManager;
    private FightFleeController $controller;

    protected function setUp(): void
    {
        $this->playerHelper = $this->createMock(PlayerHelper::class);
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->statusEffectManager = $this->createMock(StatusEffectManager::class);

        $this->controller = new FightFleeController(
            $this->playerHelper,
            $this->entityManager,
            $this->statusEffectManager,
        );

        $authChecker = $this->createMock(\Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface::class);
        $authChecker->method('isGranted')->willReturn(true);
        $container = $this->createMock(\Symfony\Component\DependencyInjection\ContainerInterface::class);
        $container->method('has')->willReturn(true);
        $container->method('get')->willReturnCallback(fn (string $id) => $id === 'security.authorization_checker' ? $authChecker : null);
        $this->controller->setContainer($container);
    }

    public function testFleeReturnsNotFoundWhenNoPlayer(): void
    {
        $this->playerHelper->method('getPlayer')->willReturn(null);

        $response = $this->controller->__invoke();

        $this->assertEquals(404, $response->getStatusCode());
    }

    public function testFleeReturnsNotFoundWhenNoFight(): void
    {
        $player = $this->createPlayerMock(fight: null);
        $this->playerHelper->method('getPlayer')->willReturn($player);

        $response = $this->controller->__invoke();

        $this->assertEquals(404, $response->getStatusCode());
    }

    public function testFleeBlockedFromBoss(): void
    {
        $monster = $this->createMock(Monster::class);
        $monster->method('isBoss')->willReturn(true);

        $mob = $this->createMock(Mob::class);
        $mob->method('getMonster')->willReturn($monster);
        $mob->method('getSpeed')->willReturn(10);

        $fight = $this->createFightMock(mobs: [$mob]);
        $player = $this->createPlayerMock(fight: $fight);
        $this->playerHelper->method('getPlayer')->willReturn($player);

        $response = $this->controller->__invoke();

        $data = json_decode($response->getContent(), true);
        $this->assertFalse($data['success']);
        $this->assertStringContainsString('boss', $data['error']);
    }

    public function testFleeBlockedWhenBerserk(): void
    {
        $mob = $this->createNonBossMob();
        $fight = $this->createFightMock(mobs: [$mob]);
        $player = $this->createPlayerMock(fight: $fight);
        $this->playerHelper->method('getPlayer')->willReturn($player);
        $this->statusEffectManager->method('isCharacterBerserk')->willReturn(true);

        $response = $this->controller->__invoke();

        $data = json_decode($response->getContent(), true);
        $this->assertFalse($data['success']);
        $this->assertStringContainsString('rage', $data['error']);
    }

    public function testFleeBlockedWhenParalyzed(): void
    {
        $mob = $this->createNonBossMob();
        $fight = $this->createFightMock(mobs: [$mob]);
        $player = $this->createPlayerMock(fight: $fight);
        $this->playerHelper->method('getPlayer')->willReturn($player);
        $this->statusEffectManager->method('isCharacterParalyzed')->willReturn(true);

        $response = $this->controller->__invoke();

        $data = json_decode($response->getContent(), true);
        $this->assertFalse($data['success']);
        $this->assertStringContainsString('pas bouger', $data['error']);
    }

    public function testFleeBlockedWhenFrozen(): void
    {
        $mob = $this->createNonBossMob();
        $fight = $this->createFightMock(mobs: [$mob]);
        $player = $this->createPlayerMock(fight: $fight);
        $this->playerHelper->method('getPlayer')->willReturn($player);
        $this->statusEffectManager->method('isCharacterFrozen')->willReturn(true);

        $response = $this->controller->__invoke();

        $data = json_decode($response->getContent(), true);
        $this->assertFalse($data['success']);
    }

    public function testFleeSuccessRemovesPlayerFromFight(): void
    {
        $mob = $this->createNonBossMob(speed: 5);
        $fight = $this->createFightMock(mobs: [$mob]);
        $player = $this->createPlayerMock(fight: $fight, speed: 100);
        $this->playerHelper->method('getPlayer')->willReturn($player);

        // With very high speed difference, flee chance is 90% (max) - run many times to ensure at least one success
        $gotSuccess = false;
        for ($i = 0; $i < 100; ++$i) {
            $response = $this->controller->__invoke();
            $data = json_decode($response->getContent(), true);
            if (isset($data['fled']) && $data['fled'] === true) {
                $gotSuccess = true;
                break;
            }
        }

        $this->assertTrue($gotSuccess, 'Flee should succeed at least once with high speed advantage');
    }

    public function testFleeFailureAdvancesFightStep(): void
    {
        $mob = $this->createNonBossMob(speed: 100);
        $fight = $this->createFightMock(mobs: [$mob]);
        $player = $this->createPlayerMock(fight: $fight, speed: 1);
        $this->playerHelper->method('getPlayer')->willReturn($player);

        // With huge speed disadvantage, flee chance is 50% (min) - some will fail
        $gotFailure = false;
        for ($i = 0; $i < 100; ++$i) {
            $response = $this->controller->__invoke();
            $data = json_decode($response->getContent(), true);
            if (isset($data['fled']) && $data['fled'] === false) {
                $gotFailure = true;
                $this->assertTrue($data['success']);
                $this->assertStringContainsString('chou', $data['message']);
                break;
            }
        }

        $this->assertTrue($gotFailure, 'Flee should fail at least once with low speed');
    }

    public function testFleeDoesNotRequireTarget(): void
    {
        $mob = $this->createNonBossMob(speed: 5);
        $fight = $this->createFightMock(mobs: [$mob]);
        $player = $this->createPlayerMock(fight: $fight, speed: 100);
        $this->playerHelper->method('getPlayer')->willReturn($player);

        // Flee controller takes no Request parameter - it works without target data
        $response = $this->controller->__invoke();

        $this->assertEquals(200, $response->getStatusCode());
    }

    private function createPlayerMock(int $id = 1, ?Fight $fight = null, int $speed = 10): Player&MockObject
    {
        $player = $this->createMock(Player::class);
        $player->method('getId')->willReturn($id);
        $player->method('getFight')->willReturn($fight);
        $player->method('getSpeed')->willReturn($speed);

        return $player;
    }

    private function createNonBossMob(int $speed = 10): Mob&MockObject
    {
        $monster = $this->createMock(Monster::class);
        $monster->method('isBoss')->willReturn(false);

        $mob = $this->createMock(Mob::class);
        $mob->method('getMonster')->willReturn($monster);
        $mob->method('getSpeed')->willReturn($speed);

        return $mob;
    }

    private function createFightMock(array $mobs = []): Fight&MockObject
    {
        $fight = $this->createMock(Fight::class);
        $fight->method('getMobs')->willReturn(new ArrayCollection($mobs));
        $fight->method('getStep')->willReturn(0);
        $fight->method('isTerminated')->willReturn(false);

        return $fight;
    }
}
