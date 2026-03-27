<?php

namespace App\Tests\Functional\Controller\Game\Fight;

use App\Controller\Game\Fight\FightItemController;
use App\Entity\App\Fight;
use App\Entity\App\Mob;
use App\Entity\App\Player;
use App\Entity\App\PlayerItem;
use App\Entity\Game\Item;
use App\Entity\Game\Spell;
use App\GameEngine\Fight\CombatLogger;
use App\GameEngine\Fight\FightTurnResolver;
use App\GameEngine\Fight\MobActionHandler;
use App\GameEngine\Fight\SpellApplicator;
use App\GameEngine\Realtime\Fight\FightTurnPublisher;
use App\Helper\PlayerHelper;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

class FightItemControllerTest extends TestCase
{
    private PlayerHelper&MockObject $playerHelper;
    private EntityManagerInterface&MockObject $entityManager;
    private SpellApplicator&MockObject $spellApplicator;
    private MobActionHandler&MockObject $mobActionHandler;
    private CombatLogger&MockObject $combatLogger;
    private FightItemController $controller;

    protected function setUp(): void
    {
        $this->playerHelper = $this->createMock(PlayerHelper::class);
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->spellApplicator = $this->createMock(SpellApplicator::class);
        $this->mobActionHandler = $this->createMock(MobActionHandler::class);
        $this->combatLogger = $this->createMock(CombatLogger::class);

        $turnResolver = $this->createMock(FightTurnResolver::class);
        $fightTurnPublisher = $this->createMock(FightTurnPublisher::class);

        $this->controller = new FightItemController(
            $this->playerHelper,
            $this->entityManager,
            $this->spellApplicator,
            $this->mobActionHandler,
            $this->combatLogger,
            $turnResolver,
            $fightTurnPublisher,
        );

        $authChecker = $this->createMock(\Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface::class);
        $authChecker->method('isGranted')->willReturn(true);
        $container = $this->createMock(\Symfony\Component\DependencyInjection\ContainerInterface::class);
        $container->method('has')->willReturn(true);
        $container->method('get')->willReturnCallback(fn (string $id) => $id === 'security.authorization_checker' ? $authChecker : null);
        $this->controller->setContainer($container);
    }

    public function testItemReturnsNotFoundWhenNoPlayer(): void
    {
        $this->playerHelper->method('getPlayer')->willReturn(null);

        $response = $this->controller->__invoke($this->createJsonRequest(['itemId' => 1]));

        $this->assertEquals(404, $response->getStatusCode());
    }

    public function testItemReturnsNotFoundWhenNoFight(): void
    {
        $player = $this->createPlayerMock(fight: null);
        $this->playerHelper->method('getPlayer')->willReturn($player);

        $response = $this->controller->__invoke($this->createJsonRequest(['itemId' => 1]));

        $this->assertEquals(404, $response->getStatusCode());
    }

    public function testItemReturnsBadRequestWhenMissingItemId(): void
    {
        $fight = $this->createFightMock();
        $player = $this->createPlayerMock(fight: $fight);
        $this->playerHelper->method('getPlayer')->willReturn($player);

        $response = $this->controller->__invoke($this->createJsonRequest([]));

        $this->assertEquals(400, $response->getStatusCode());
    }

    public function testItemReturnsNotFoundWhenItemDoesNotExist(): void
    {
        $fight = $this->createFightMock();
        $player = $this->createPlayerMock(fight: $fight);
        $this->playerHelper->method('getPlayer')->willReturn($player);

        $repo = $this->createMock(EntityRepository::class);
        $repo->method('find')->willReturn(null);
        $this->entityManager->method('getRepository')->willReturn($repo);

        $response = $this->controller->__invoke($this->createJsonRequest(['itemId' => 999]));

        $this->assertEquals(404, $response->getStatusCode());
        $this->assertStringContainsString('introuvable', $response->getContent());
    }

    public function testItemWithSpellAppliesHeal(): void
    {
        $fight = $this->createFightMock();
        $player = $this->createPlayerMock(fight: $fight);
        $this->playerHelper->method('getPlayer')->willReturn($player);

        $spell = $this->createMock(Spell::class);
        $spell->method('getHeal')->willReturn(10);
        $spell->method('getDamage')->willReturn(0);

        $item = $this->createMock(Item::class);
        $item->method('getSpell')->willReturn($spell);
        $item->method('getName')->willReturn('Potion');
        $item->method('getNbUsages')->willReturn(1);

        $playerItem = $this->createMock(PlayerItem::class);
        $playerItem->method('getGenericItem')->willReturn($item);

        $repo = $this->createMock(EntityRepository::class);
        $repo->method('find')->willReturn($playerItem);
        $this->entityManager->method('getRepository')->willReturn($repo);

        $this->spellApplicator->expects($this->once())->method('apply')
            ->with($spell, $player, $player, $this->anything())
            ->willReturn(['Vous recuperez 10 PV']);

        $this->mobActionHandler->method('doAction')->willReturn(['messages' => [], 'dangerAlert' => null]);

        $response = $this->controller->__invoke($this->createJsonRequest(['itemId' => 1]));

        $data = json_decode($response->getContent(), true);
        $this->assertTrue($data['success']);
        $this->assertNotEmpty($data['messages']);
    }

    public function testItemWithoutSpellReturnsBadRequest(): void
    {
        $fight = $this->createFightMock();
        $player = $this->createPlayerMock(fight: $fight);
        $this->playerHelper->method('getPlayer')->willReturn($player);

        $item = $this->createMock(Item::class);
        $item->method('getSpell')->willReturn(null);

        $playerItem = $this->createMock(PlayerItem::class);
        $playerItem->method('getGenericItem')->willReturn($item);

        $repo = $this->createMock(EntityRepository::class);
        $repo->method('find')->willReturn($playerItem);
        $this->entityManager->method('getRepository')->willReturn($repo);

        $response = $this->controller->__invoke($this->createJsonRequest(['itemId' => 1]));

        $this->assertEquals(400, $response->getStatusCode());
    }

    public function testLimitedUsageItemIsRemoved(): void
    {
        $fight = $this->createFightMock();
        $player = $this->createPlayerMock(fight: $fight);
        $this->playerHelper->method('getPlayer')->willReturn($player);

        $spell = $this->createMock(Spell::class);
        $spell->method('getHeal')->willReturn(5);
        $spell->method('getDamage')->willReturn(0);

        $item = $this->createMock(Item::class);
        $item->method('getSpell')->willReturn($spell);
        $item->method('getName')->willReturn('Potion');
        $item->method('getNbUsages')->willReturn(1);

        $playerItem = $this->createMock(PlayerItem::class);
        $playerItem->method('getGenericItem')->willReturn($item);

        $repo = $this->createMock(EntityRepository::class);
        $repo->method('find')->willReturn($playerItem);
        $this->entityManager->method('getRepository')->willReturn($repo);

        $this->spellApplicator->method('apply')->willReturn([]);
        $this->mobActionHandler->method('doAction')->willReturn(['messages' => [], 'dangerAlert' => null]);

        $this->entityManager->expects($this->once())->method('remove')->with($playerItem);

        $this->controller->__invoke($this->createJsonRequest(['itemId' => 1]));
    }

    public function testUnlimitedUsageItemIsNotRemoved(): void
    {
        $fight = $this->createFightMock();
        $player = $this->createPlayerMock(fight: $fight);
        $this->playerHelper->method('getPlayer')->willReturn($player);

        $spell = $this->createMock(Spell::class);
        $spell->method('getHeal')->willReturn(5);
        $spell->method('getDamage')->willReturn(0);

        $item = $this->createMock(Item::class);
        $item->method('getSpell')->willReturn($spell);
        $item->method('getName')->willReturn('Baton magique');
        $item->method('getNbUsages')->willReturn(-1);

        $playerItem = $this->createMock(PlayerItem::class);
        $playerItem->method('getGenericItem')->willReturn($item);

        $repo = $this->createMock(EntityRepository::class);
        $repo->method('find')->willReturn($playerItem);
        $this->entityManager->method('getRepository')->willReturn($repo);

        $this->spellApplicator->method('apply')->willReturn([]);
        $this->mobActionHandler->method('doAction')->willReturn(['messages' => [], 'dangerAlert' => null]);

        $this->entityManager->expects($this->never())->method('remove');

        $this->controller->__invoke($this->createJsonRequest(['itemId' => 1]));
    }

    public function testItemTargetsMobWhenSpecified(): void
    {
        $mob = $this->createMock(Mob::class);
        $mob->method('getId')->willReturn(5);

        $fight = $this->createFightMock(mobs: [$mob]);
        $player = $this->createPlayerMock(fight: $fight);
        $this->playerHelper->method('getPlayer')->willReturn($player);

        $spell = $this->createMock(Spell::class);
        $spell->method('getHeal')->willReturn(0);
        $spell->method('getDamage')->willReturn(10);

        $item = $this->createMock(Item::class);
        $item->method('getSpell')->willReturn($spell);
        $item->method('getName')->willReturn('Bombe');
        $item->method('getNbUsages')->willReturn(1);

        $playerItem = $this->createMock(PlayerItem::class);
        $playerItem->method('getGenericItem')->willReturn($item);

        $repo = $this->createMock(EntityRepository::class);
        $repo->method('find')->willReturn($playerItem);
        $this->entityManager->method('getRepository')->willReturn($repo);

        // Spell should target the mob, not the player
        $this->spellApplicator->expects($this->once())->method('apply')
            ->with($spell, $player, $mob, $this->anything())
            ->willReturn(['10 degats infliges !']);

        $this->mobActionHandler->method('doAction')->willReturn(['messages' => [], 'dangerAlert' => null]);

        $response = $this->controller->__invoke($this->createJsonRequest([
            'itemId' => 1, 'targetId' => 5, 'targetType' => 'mob',
        ]));

        $data = json_decode($response->getContent(), true);
        $this->assertTrue($data['success']);
    }

    public function testItemTriggersMobAction(): void
    {
        $fight = $this->createFightMock();
        $player = $this->createPlayerMock(fight: $fight);
        $this->playerHelper->method('getPlayer')->willReturn($player);

        $spell = $this->createMock(Spell::class);
        $spell->method('getHeal')->willReturn(5);
        $spell->method('getDamage')->willReturn(0);

        $item = $this->createMock(Item::class);
        $item->method('getSpell')->willReturn($spell);
        $item->method('getName')->willReturn('Potion');
        $item->method('getNbUsages')->willReturn(-1);

        $playerItem = $this->createMock(PlayerItem::class);
        $playerItem->method('getGenericItem')->willReturn($item);

        $repo = $this->createMock(EntityRepository::class);
        $repo->method('find')->willReturn($playerItem);
        $this->entityManager->method('getRepository')->willReturn($repo);

        $this->spellApplicator->method('apply')->willReturn([]);

        $this->mobActionHandler->expects($this->once())->method('doAction')
            ->willReturn(['messages' => ['Mob attaque !'], 'dangerAlert' => null]);

        $response = $this->controller->__invoke($this->createJsonRequest(['itemId' => 1]));

        $data = json_decode($response->getContent(), true);
        $this->assertContains('Mob attaque !', $data['messages']);
    }

    private function createJsonRequest(array $data): Request
    {
        return Request::create('/game/fight/item', 'POST', [], [], [], [], json_encode($data));
    }

    private function createPlayerMock(int $id = 1, ?Fight $fight = null): Player&MockObject
    {
        $player = $this->createMock(Player::class);
        $player->method('getId')->willReturn($id);
        $player->method('getFight')->willReturn($fight);
        $player->method('getName')->willReturn('TestPlayer');

        return $player;
    }

    private function createFightMock(array $mobs = []): Fight&MockObject
    {
        $fight = $this->createMock(Fight::class);
        $fight->method('getMobs')->willReturn(new ArrayCollection($mobs));
        $fight->method('getStep')->willReturn(0);
        $fight->method('isTerminated')->willReturn(false);
        $fight->method('isVictory')->willReturn(false);

        return $fight;
    }
}
