<?php

namespace App\Tests\Functional\Controller\Game\Fight;

use App\Controller\Game\Fight\FightSpellController;
use App\Entity\App\Fight;
use App\Entity\App\Mob;
use App\Entity\App\Player;
use App\Entity\App\PlayerItem;
use App\Entity\App\Slot;
use App\Entity\Game\Spell;
use App\Enum\Element;
use App\GameEngine\Fight\CombatCapacityResolver;
use App\GameEngine\Fight\CombatLogger;
use App\GameEngine\Fight\CombatSkillResolver;
use App\GameEngine\Fight\ElementalSynergyCalculator;
use App\GameEngine\Fight\FightTurnResolver;
use App\GameEngine\Fight\MobActionHandler;
use App\GameEngine\Fight\SpellApplicator;
use App\GameEngine\Fight\StatusEffectManager;
use App\Helper\GearHelper;
use App\Helper\PlayerHelper;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

class FightSpellControllerTest extends TestCase
{
    private PlayerHelper&MockObject $playerHelper;
    private EntityManagerInterface&MockObject $entityManager;
    private CombatSkillResolver&MockObject $combatSkillResolver;
    private CombatCapacityResolver&MockObject $combatCapacityResolver;
    private SpellApplicator&MockObject $spellApplicator;
    private ElementalSynergyCalculator&MockObject $synergyCalculator;
    private StatusEffectManager&MockObject $statusEffectManager;
    private MobActionHandler&MockObject $mobActionHandler;
    private CombatLogger&MockObject $combatLogger;
    private FightTurnResolver&MockObject $turnResolver;
    private FightSpellController $controller;

    protected function setUp(): void
    {
        $this->playerHelper = $this->createMock(PlayerHelper::class);
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->combatSkillResolver = $this->createMock(CombatSkillResolver::class);
        $this->combatCapacityResolver = $this->createMock(CombatCapacityResolver::class);
        $this->spellApplicator = $this->createMock(SpellApplicator::class);
        $this->synergyCalculator = $this->createMock(ElementalSynergyCalculator::class);
        $this->statusEffectManager = $this->createMock(StatusEffectManager::class);
        $this->mobActionHandler = $this->createMock(MobActionHandler::class);
        $this->combatLogger = $this->createMock(CombatLogger::class);
        $this->turnResolver = $this->createMock(FightTurnResolver::class);
        $this->turnResolver->method('isMobFirst')->willReturn(false);
        $gearHelper = $this->createMock(GearHelper::class);
        $gearHelper->method('getEquippedElementalDamageBonus')->willReturn(0.0);

        $this->controller = new FightSpellController(
            $this->playerHelper,
            $this->entityManager,
            $this->combatSkillResolver,
            $this->combatCapacityResolver,
            $this->spellApplicator,
            $this->synergyCalculator,
            $this->statusEffectManager,
            $this->mobActionHandler,
            $this->combatLogger,
            $this->turnResolver,
            $gearHelper,
        );

        $authChecker = $this->createMock(\Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface::class);
        $authChecker->method('isGranted')->willReturn(true);
        $container = $this->createMock(\Symfony\Component\DependencyInjection\ContainerInterface::class);
        $container->method('has')->willReturn(true);
        $container->method('get')->willReturnCallback(fn (string $id) => $id === 'security.authorization_checker' ? $authChecker : null);
        $this->controller->setContainer($container);
    }

    public function testSpellReturnsNotFoundWhenNoPlayer(): void
    {
        $this->playerHelper->method('getPlayer')->willReturn(null);

        $response = $this->controller->__invoke($this->createJsonRequest([
            'spellSlug' => 'fireball', 'targetId' => 1, 'targetType' => 'mob',
        ]));

        $this->assertEquals(404, $response->getStatusCode());
    }

    public function testSpellReturnsNotFoundWhenNoFight(): void
    {
        $player = $this->createPlayerMock(fight: null);
        $this->playerHelper->method('getPlayer')->willReturn($player);

        $response = $this->controller->__invoke($this->createJsonRequest([
            'spellSlug' => 'fireball', 'targetId' => 1, 'targetType' => 'mob',
        ]));

        $this->assertEquals(404, $response->getStatusCode());
    }

    public function testSpellReturnsBadRequestWhenMissingData(): void
    {
        $fight = $this->createFightMock();
        $player = $this->createPlayerMock(fight: $fight);
        $this->playerHelper->method('getPlayer')->willReturn($player);

        $response = $this->controller->__invoke($this->createJsonRequest(['targetId' => 1]));

        $this->assertEquals(400, $response->getStatusCode());
    }

    public function testSpellBlockedWhenParalyzed(): void
    {
        $fight = $this->createFightMock();
        $player = $this->createPlayerMock(fight: $fight);
        $this->playerHelper->method('getPlayer')->willReturn($player);
        $this->statusEffectManager->method('isCharacterParalyzed')->willReturn(true);

        $response = $this->controller->__invoke($this->createJsonRequest([
            'spellSlug' => 'fireball', 'targetId' => 1, 'targetType' => 'mob',
        ]));

        $data = json_decode($response->getContent(), true);
        $this->assertFalse($data['success']);
        $this->assertStringContainsString('pas agir', $data['error']);
    }

    public function testSpellBlockedWhenFrozen(): void
    {
        $fight = $this->createFightMock();
        $player = $this->createPlayerMock(fight: $fight);
        $this->playerHelper->method('getPlayer')->willReturn($player);
        $this->statusEffectManager->method('isCharacterFrozen')->willReturn(true);

        $response = $this->controller->__invoke($this->createJsonRequest([
            'spellSlug' => 'fireball', 'targetId' => 1, 'targetType' => 'mob',
        ]));

        $data = json_decode($response->getContent(), true);
        $this->assertFalse($data['success']);
    }

    public function testSpellBlockedWhenSilenced(): void
    {
        $fight = $this->createFightMock();
        $player = $this->createPlayerMock(fight: $fight);
        $this->playerHelper->method('getPlayer')->willReturn($player);
        $this->statusEffectManager->method('isCharacterSilenced')->willReturn(true);

        $response = $this->controller->__invoke($this->createJsonRequest([
            'spellSlug' => 'fireball', 'targetId' => 1, 'targetType' => 'mob',
        ]));

        $data = json_decode($response->getContent(), true);
        $this->assertFalse($data['success']);
        $this->assertStringContainsString('silence', $data['error']);
    }

    public function testSpellForbiddenWhenNotUnlocked(): void
    {
        $fight = $this->createFightMock();
        $player = $this->createPlayerMock(fight: $fight);
        $this->playerHelper->method('getPlayer')->willReturn($player);
        $this->combatCapacityResolver->method('findMateriaSpell')->willReturn(null);

        $response = $this->controller->__invoke($this->createJsonRequest([
            'spellSlug' => 'fireball', 'targetId' => 1, 'targetType' => 'mob',
        ]));

        $this->assertEquals(403, $response->getStatusCode());
    }

    public function testSpellBlockedWhenOnCooldown(): void
    {
        $fight = $this->createFightMock(cooldown: ['player_1' => ['fireball' => 2]]);
        $player = $this->createPlayerMock(id: 1, fight: $fight);
        $spell = $this->createSpellMock('fireball');
        $this->playerHelper->method('getPlayer')->willReturn($player);
        $this->combatCapacityResolver->method('findMateriaSpell')->willReturn($this->createMateriaSpellEntry($spell));

        $response = $this->controller->__invoke($this->createJsonRequest([
            'spellSlug' => 'fireball', 'targetId' => 1, 'targetType' => 'mob',
        ]));

        $data = json_decode($response->getContent(), true);
        $this->assertFalse($data['success']);
        $this->assertStringContainsString('recharge', $data['error']);
    }

    public function testSpellBlockedWhenInsufficientEnergy(): void
    {
        $fight = $this->createFightMock();
        $player = $this->createPlayerMock(id: 1, fight: $fight);
        $spell = $this->createSpellMock('fireball');
        $this->playerHelper->method('getPlayer')->willReturn($player);
        $this->combatCapacityResolver->method('findMateriaSpell')->willReturn($this->createMateriaSpellEntry($spell));
        $this->combatSkillResolver->method('consumeEnergy')->willReturn(false);

        $response = $this->controller->__invoke($this->createJsonRequest([
            'spellSlug' => 'fireball', 'targetId' => 1, 'targetType' => 'mob',
        ]));

        $data = json_decode($response->getContent(), true);
        $this->assertFalse($data['success']);
        $this->assertStringContainsString('nergie insuffisante', $data['error']);
    }

    public function testSpellSuccessfullyApplied(): void
    {
        $mob = $this->createMobMock(id: 5);
        $fight = $this->createFightMock(mobs: [$mob]);
        $player = $this->createPlayerMock(id: 1, fight: $fight, energy: 50, maxEnergy: 100);
        $spell = $this->createSpellMock('fireball', element: Element::Fire, cooldown: 0);

        $this->playerHelper->method('getPlayer')->willReturn($player);
        $this->combatCapacityResolver->method('findMateriaSpell')->willReturn($this->createMateriaSpellEntry($spell));
        $this->combatSkillResolver->method('consumeEnergy')->willReturn(true);
        $this->combatSkillResolver->method('getCombatBonuses')->willReturn([
            'damage' => 0, 'heal' => 0, 'hit' => 0, 'critical' => 0, 'life' => 0,
        ]);
        $this->statusEffectManager->method('processStartOfTurn')->willReturn([]);
        $this->spellApplicator->method('apply')->willReturn(['Fireball inflige 5 degats !']);
        $this->mobActionHandler->method('doAction')->willReturn(['messages' => [], 'dangerAlert' => null]);

        $response = $this->controller->__invoke($this->createJsonRequest([
            'spellSlug' => 'fireball', 'targetId' => 5, 'targetType' => 'mob',
        ]));

        $data = json_decode($response->getContent(), true);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertTrue($data['success']);
    }

    public function testSpellTargetNotFound(): void
    {
        $fight = $this->createFightMock(mobs: []);
        $player = $this->createPlayerMock(id: 1, fight: $fight, energy: 50, maxEnergy: 100);
        $spell = $this->createSpellMock('fireball');

        $this->playerHelper->method('getPlayer')->willReturn($player);
        $this->combatCapacityResolver->method('findMateriaSpell')->willReturn($this->createMateriaSpellEntry($spell));
        $this->combatSkillResolver->method('consumeEnergy')->willReturn(true);

        $response = $this->controller->__invoke($this->createJsonRequest([
            'spellSlug' => 'fireball', 'targetId' => 999, 'targetType' => 'mob',
        ]));

        $this->assertEquals(404, $response->getStatusCode());
    }

    public function testSpellTriggersMobAction(): void
    {
        $mob = $this->createMobMock(id: 5);
        $fight = $this->createFightMock(mobs: [$mob]);
        $player = $this->createPlayerMock(id: 1, fight: $fight, energy: 50, maxEnergy: 100);
        $spell = $this->createSpellMock('fireball', element: Element::Fire);

        $this->playerHelper->method('getPlayer')->willReturn($player);
        $this->combatCapacityResolver->method('findMateriaSpell')->willReturn($this->createMateriaSpellEntry($spell));
        $this->combatSkillResolver->method('consumeEnergy')->willReturn(true);
        $this->combatSkillResolver->method('getCombatBonuses')->willReturn([
            'damage' => 0, 'heal' => 0, 'hit' => 0, 'critical' => 0, 'life' => 0,
        ]);
        $this->statusEffectManager->method('processStartOfTurn')->willReturn([]);
        $this->spellApplicator->method('apply')->willReturn([]);

        $this->mobActionHandler->expects($this->once())->method('doAction')
            ->willReturn(['messages' => ['Slime attaque !'], 'dangerAlert' => null]);

        $response = $this->controller->__invoke($this->createJsonRequest([
            'spellSlug' => 'fireball', 'targetId' => 5, 'targetType' => 'mob',
        ]));

        $data = json_decode($response->getContent(), true);
        $this->assertContains('Slime attaque !', $data['messages']);
    }

    public function testSpellWithStringTargetIdWorks(): void
    {
        $mob = $this->createMobMock(id: 5);
        $fight = $this->createFightMock(mobs: [$mob]);
        $player = $this->createPlayerMock(id: 1, fight: $fight, energy: 50, maxEnergy: 100);
        $spell = $this->createSpellMock('fireball', element: Element::None);

        $this->playerHelper->method('getPlayer')->willReturn($player);
        $this->combatCapacityResolver->method('findMateriaSpell')->willReturn($this->createMateriaSpellEntry($spell, elementMatch: false));
        $this->combatSkillResolver->method('consumeEnergy')->willReturn(true);
        $this->combatSkillResolver->method('getCombatBonuses')->willReturn([
            'damage' => 0, 'heal' => 0, 'hit' => 0, 'critical' => 0, 'life' => 0,
        ]);
        $this->statusEffectManager->method('processStartOfTurn')->willReturn([]);
        $this->spellApplicator->method('apply')->willReturn([]);
        $this->mobActionHandler->method('doAction')->willReturn(['messages' => [], 'dangerAlert' => null]);

        // Send targetId as string
        $request = Request::create('/game/fight/spell', 'POST', [], [], [], [], json_encode([
            'spellSlug' => 'fireball', 'targetId' => '5', 'targetType' => 'mob',
        ]));
        $response = $this->controller->__invoke($request);

        $this->assertEquals(200, $response->getStatusCode());
    }

    private function createJsonRequest(array $data): Request
    {
        return Request::create('/game/fight/spell', 'POST', [], [], [], [], json_encode($data));
    }

    private function createPlayerMock(int $id = 1, ?Fight $fight = null, int $energy = 100, int $maxEnergy = 100, string $name = 'TestPlayer'): Player&MockObject
    {
        $player = $this->createMock(Player::class);
        $player->method('getId')->willReturn($id);
        $player->method('getFight')->willReturn($fight);
        $player->method('getEnergy')->willReturn($energy);
        $player->method('getMaxEnergy')->willReturn($maxEnergy);
        $player->method('getName')->willReturn($name);
        $player->method('getLife')->willReturn(50);
        $player->method('getMaxLife')->willReturn(100);

        return $player;
    }

    private function createMobMock(int $id = 1, int $life = 10): Mob&MockObject
    {
        $mob = $this->createMock(Mob::class);
        $mob->method('getId')->willReturn($id);
        $mob->method('getLife')->willReturn($life);
        $mob->method('isDead')->willReturn(false);
        $mob->method('isSummoned')->willReturn(false);

        return $mob;
    }

    private function createFightMock(array $mobs = [], array $players = [], array $cooldown = []): Fight&MockObject
    {
        $fight = $this->createMock(Fight::class);
        $fight->method('getMobs')->willReturn(new ArrayCollection($mobs));
        $fight->method('getPlayers')->willReturn(new ArrayCollection($players));
        $fight->method('getStep')->willReturn(0);
        $fight->method('isTerminated')->willReturn(false);
        $fight->method('isVictory')->willReturn(false);
        $fight->method('getLastElementUsed')->willReturn(null);

        if (!empty($cooldown)) {
            $fight->method('isSpellOnCooldown')->willReturnCallback(function (string $entityKey, string $spellSlug) use ($cooldown) {
                return isset($cooldown[$entityKey][$spellSlug]) && $cooldown[$entityKey][$spellSlug] > 0;
            });
            $fight->method('getSpellCooldown')->willReturnCallback(function (string $entityKey, string $spellSlug) use ($cooldown) {
                return $cooldown[$entityKey][$spellSlug] ?? 0;
            });
        } else {
            $fight->method('isSpellOnCooldown')->willReturn(false);
            $fight->method('getSpellCooldown')->willReturn(0);
        }

        return $fight;
    }

    /**
     * @return array{spell: Spell, materia: PlayerItem&MockObject, slot: Slot&MockObject, elementMatch: bool}
     */
    private function createMateriaSpellEntry(Spell&MockObject $spell, bool $elementMatch = false): array
    {
        $materia = $this->createMock(PlayerItem::class);
        $slot = $this->createMock(Slot::class);

        return [
            'spell' => $spell,
            'materia' => $materia,
            'slot' => $slot,
            'elementMatch' => $elementMatch,
        ];
    }

    private function createSpellMock(string $slug, Element $element = Element::None, int $cooldown = 0): Spell&MockObject
    {
        $spell = $this->createMock(Spell::class);
        $spell->method('getSlug')->willReturn($slug);
        $spell->method('getName')->willReturn(ucfirst($slug));
        $spell->method('getElement')->willReturn($element);
        $spell->method('getCooldown')->willReturn($cooldown);
        $spell->method('getHit')->willReturn(100);
        $spell->method('getDamage')->willReturn(5);
        $spell->method('getHeal')->willReturn(0);
        $spell->method('getCritical')->willReturn(0);
        $spell->method('getEnergyCost')->willReturn(10);
        $spell->method('getStatusEffectSlug')->willReturn(null);

        return $spell;
    }
}
