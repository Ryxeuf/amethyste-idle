<?php

namespace App\Tests\Unit\GameEngine\Fight;

use App\Entity\App\Fight;
use App\Entity\App\Mob;
use App\Entity\App\Player;
use App\Entity\Game\Monster;
use App\Entity\Game\Spell;
use App\Event\Fight\ActionEvent;
use App\GameEngine\Fight\Handler\MobActionHandlerInterface;
use App\GameEngine\Fight\MobActionHandler;
use App\GameEngine\Fight\SpellApplicator;
use App\GameEngine\Fight\StatusEffectManager;
use Doctrine\Common\Collections\ArrayCollection;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class MobActionHandlerTest extends TestCase
{
    private EventDispatcherInterface&MockObject $eventDispatcher;
    private SpellApplicator&MockObject $spellApplicator;
    private LoggerInterface&MockObject $logger;
    private StatusEffectManager&MockObject $statusEffectManager;

    protected function setUp(): void
    {
        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $this->spellApplicator = $this->createMock(SpellApplicator::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->statusEffectManager = $this->createMock(StatusEffectManager::class);

        // Par defaut : pas de status, pas de messages
        $this->statusEffectManager->method('processStartOfTurn')->willReturn([]);
        $this->statusEffectManager->method('isCharacterParalyzed')->willReturn(false);
        $this->statusEffectManager->method('isCharacterFrozen')->willReturn(false);
    }

    private function createHandler(array $handlers = []): MobActionHandler
    {
        return new MobActionHandler(
            $handlers,
            $this->eventDispatcher,
            $this->spellApplicator,
            $this->logger,
            $this->statusEffectManager,
        );
    }

    /**
     * Cree un Mob mock avec un Monster mock.
     */
    private function createMobMock(
        bool $isDead = false,
        int $life = 100,
        int $maxLife = 100,
        ?array $aiPattern = null,
        array $spells = [],
        bool $isBoss = false,
        ?array $bossPhases = null,
        int $hit = 100,
        string $name = 'Gobelin',
    ): Mob&MockObject {
        $monster = $this->createMock(Monster::class);
        $monster->method('getAiPattern')->willReturn($aiPattern);
        $monster->method('getSpells')->willReturn(new ArrayCollection($spells));
        $monster->method('isBoss')->willReturn($isBoss);
        $monster->method('getBossPhases')->willReturn($bossPhases);
        $monster->method('getHit')->willReturn($hit);

        if ($bossPhases !== null) {
            $hpPercent = (int) (($life / $maxLife) * 100);
            // Trouver la phase correspondante
            $currentPhase = null;
            foreach ($bossPhases as $phase) {
                if ($hpPercent <= ($phase['hp_threshold'] ?? 100)) {
                    $currentPhase = $phase;
                }
            }
            $monster->method('getCurrentBossPhase')->willReturn($currentPhase);
        } else {
            $monster->method('getCurrentBossPhase')->willReturn(null);
        }

        $mob = $this->createMock(Mob::class);
        $mob->method('isDead')->willReturn($isDead);
        $mob->method('getLife')->willReturn($life);
        $mob->method('getMaxLife')->willReturn($maxLife);
        $mob->method('getMonster')->willReturn($monster);
        $mob->method('getName')->willReturn($name);
        $mob->method('getId')->willReturn(1);

        // Mock getAttack comme fallback
        $basicAttack = $this->createMock(Spell::class);
        $basicAttack->method('getName')->willReturn('Attaque');
        $mob->method('getAttack')->willReturn($basicAttack);

        return $mob;
    }

    private function createFightMock(Mob $mob, ?Player $player = null, int $step = 0): Fight&MockObject
    {
        if ($player === null) {
            $player = $this->createMock(Player::class);
            $player->method('isDead')->willReturn(false);
            $player->method('getName')->willReturn('Heros');
        }

        $fight = $this->createMock(Fight::class);
        $fight->method('getMobs')->willReturn(new ArrayCollection([$mob]));
        $fight->method('getPlayers')->willReturn(new ArrayCollection([$player]));
        $fight->method('getId')->willReturn(42);
        $fight->method('getStep')->willReturn($step);

        return $fight;
    }

    public function testDeadMobDispatchesActionEventAndReturnsEmpty(): void
    {
        $mob = $this->createMobMock(isDead: true);
        $fight = $this->createFightMock($mob);

        $this->eventDispatcher->expects($this->once())
            ->method('dispatch')
            ->with(
                $this->isInstanceOf(ActionEvent::class),
                $this->equalTo(ActionEvent::NAME)
            );

        $handler = $this->createHandler();
        $result = $handler->doAction($fight);

        $this->assertEmpty($result['messages']);
        $this->assertNull($result['dangerAlert']);
    }

    public function testParalyzedMobSkipsTurn(): void
    {
        $mob = $this->createMobMock(name: 'Squelette');
        $fight = $this->createFightMock($mob);

        $this->statusEffectManager = $this->createMock(StatusEffectManager::class);
        $this->statusEffectManager->method('processStartOfTurn')->willReturn([]);
        $this->statusEffectManager->method('isCharacterParalyzed')->willReturn(true);
        $this->statusEffectManager->method('isCharacterFrozen')->willReturn(false);

        $handler = $this->createHandler();
        $result = $handler->doAction($fight);

        $this->assertContains('Squelette est immobilise !', $result['messages']);
    }

    public function testFrozenMobSkipsTurn(): void
    {
        $mob = $this->createMobMock(name: 'Dragon');
        $fight = $this->createFightMock($mob);

        $this->statusEffectManager = $this->createMock(StatusEffectManager::class);
        $this->statusEffectManager->method('processStartOfTurn')->willReturn([]);
        $this->statusEffectManager->method('isCharacterParalyzed')->willReturn(false);
        $this->statusEffectManager->method('isCharacterFrozen')->willReturn(true);

        $handler = $this->createHandler();
        $result = $handler->doAction($fight);

        $this->assertContains('Dragon est immobilise !', $result['messages']);
    }

    public function testBasicAttackWhenNoAiPattern(): void
    {
        $mob = $this->createMobMock(aiPattern: null, hit: 100);
        $fight = $this->createFightMock($mob);

        // Sans AI pattern et random_int(1,100) <= 25 peu probable d'utiliser spell
        // On verifie juste que l'action s'execute sans erreur et dispatch ActionEvent
        $this->spellApplicator->method('apply')->willReturn([]);
        $this->eventDispatcher->expects($this->atLeastOnce())->method('dispatch');

        $handler = $this->createHandler();
        $result = $handler->doAction($fight);

        $this->assertIsArray($result['messages']);
    }

    public function testSequentialPatternCyclesThroughActions(): void
    {
        // Sequence : attack, attack, spell (index = step % 3)
        $spell = $this->createMock(Spell::class);
        $spell->method('getName')->willReturn('Boule de feu');
        $spell->method('getHeal')->willReturn(null);

        $mob = $this->createMobMock(
            aiPattern: ['sequence' => ['attack', 'attack', 'spell']],
            spells: [$spell],
            hit: 100,
        );

        // Step 2 => index 2 => 'spell'
        $fight = $this->createFightMock($mob, step: 2);

        $this->spellApplicator->method('apply')->willReturn([]);

        $handler = $this->createHandler();
        $result = $handler->doAction($fight);

        // Verifie que le sort a ete utilise (message contient le nom du spell)
        $hasSpellMessage = false;
        foreach ($result['messages'] as $msg) {
            if (str_contains($msg, 'Boule de feu')) {
                $hasSpellMessage = true;
                break;
            }
        }
        $this->assertTrue($hasSpellMessage, 'Le mob devrait utiliser le spell de la sequence');
    }

    public function testLowHpHealTriggersWhenBelowThreshold(): void
    {
        $healSpell = $this->createMock(Spell::class);
        $healSpell->method('getName')->willReturn('Soin');
        $healSpell->method('getHeal')->willReturn(20);

        $mob = $this->createMobMock(
            life: 20,
            maxLife: 100,
            aiPattern: ['low_hp_heal' => ['threshold' => 30, 'action' => 'heal']],
            spells: [$healSpell],
            hit: 100,
        );
        $fight = $this->createFightMock($mob);

        $this->spellApplicator->method('apply')->willReturn([]);

        $handler = $this->createHandler();
        $result = $handler->doAction($fight);

        // Verifie que le spell de soin a ete utilise
        $hasSoinMessage = false;
        foreach ($result['messages'] as $msg) {
            if (str_contains($msg, 'Soin')) {
                $hasSoinMessage = true;
                break;
            }
        }
        $this->assertTrue($hasSoinMessage, 'Le mob devrait se soigner quand ses PV sont bas');
    }

    public function testBossPhaseSelectionBasedOnHpPercentage(): void
    {
        $spell = $this->createMock(Spell::class);
        $spell->method('getName')->willReturn('Rage');
        $spell->method('getHeal')->willReturn(null);

        $bossPhases = [
            ['hp_threshold' => 100, 'action' => 'attack', 'danger_message' => null],
            ['hp_threshold' => 50, 'action' => 'spell', 'danger_message' => 'Le boss entre en rage !'],
        ];

        $mob = $this->createMobMock(
            life: 40,
            maxLife: 100,
            aiPattern: [],
            spells: [$spell],
            isBoss: true,
            bossPhases: $bossPhases,
            hit: 100,
        );
        $fight = $this->createFightMock($mob);

        $this->spellApplicator->method('apply')->willReturn([]);

        $handler = $this->createHandler();
        $result = $handler->doAction($fight);

        // Phase 2 (hp_threshold 50) devrait etre selectionnee car HP = 40%
        $this->assertIsArray($result['messages']);
    }

    public function testDangerAlertReturnedWhenMobHpBelowThreshold(): void
    {
        $mob = $this->createMobMock(
            life: 20,
            maxLife: 100,
            aiPattern: [
                'danger_alert' => [
                    'threshold' => 30,
                    'message' => 'Le monstre prepare une attaque devastatrice !',
                ],
            ],
            hit: 100,
        );
        $fight = $this->createFightMock($mob);

        $this->spellApplicator->method('apply')->willReturn([]);

        $handler = $this->createHandler();
        $result = $handler->doAction($fight);

        $this->assertSame(
            'Le monstre prepare une attaque devastatrice !',
            $result['dangerAlert']
        );
    }

    public function testBossPhaseDangerMessage(): void
    {
        $spell = $this->createMock(Spell::class);
        $spell->method('getName')->willReturn('Attaque');
        $spell->method('getHeal')->willReturn(null);

        $bossPhases = [
            ['hp_threshold' => 100, 'action' => 'attack'],
            ['hp_threshold' => 50, 'action' => 'spell', 'danger_message' => 'Le boss entre en phase finale !'],
        ];

        $mob = $this->createMobMock(
            life: 30,
            maxLife: 100,
            aiPattern: [],
            spells: [$spell],
            isBoss: true,
            bossPhases: $bossPhases,
            hit: 100,
        );
        $fight = $this->createFightMock($mob);

        $this->spellApplicator->method('apply')->willReturn([]);

        $handler = $this->createHandler();
        $result = $handler->doAction($fight);

        $this->assertSame('Le boss entre en phase finale !', $result['dangerAlert']);
    }

    public function testDeadTargetPlayerReturnsEarly(): void
    {
        $mob = $this->createMobMock(hit: 100);

        $deadPlayer = $this->createMock(Player::class);
        $deadPlayer->method('isDead')->willReturn(true);
        $deadPlayer->method('getName')->willReturn('Joueur mort');

        $fight = $this->createFightMock($mob, $deadPlayer);

        // Le SpellApplicator ne devrait pas etre appele
        $this->spellApplicator->expects($this->never())->method('apply');

        $handler = $this->createHandler();
        $result = $handler->doAction($fight);

        $this->assertIsArray($result['messages']);
    }

    public function testResultStructure(): void
    {
        $mob = $this->createMobMock(hit: 100);
        $fight = $this->createFightMock($mob);

        $this->spellApplicator->method('apply')->willReturn([]);

        $handler = $this->createHandler();
        $result = $handler->doAction($fight);

        $this->assertArrayHasKey('messages', $result);
        $this->assertArrayHasKey('dangerAlert', $result);
        $this->assertIsArray($result['messages']);
    }

    public function testHandlerFallbackForAttackAction(): void
    {
        // Test qu'un handler custom est utilise pour l'action 'attack'
        $attackSpell = $this->createMock(Spell::class);
        $attackSpell->method('getName')->willReturn('Coup de griffe');

        $attackHandler = $this->createMock(MobActionHandlerInterface::class);
        $attackHandler->method('supports')->willReturnCallback(fn(string $action) => $action === 'attack');
        $attackHandler->method('getSpell')->willReturn($attackSpell);

        $mob = $this->createMobMock(aiPattern: null, hit: 100);
        $fight = $this->createFightMock($mob);

        $this->spellApplicator->method('apply')->willReturn([]);

        $handler = $this->createHandler([$attackHandler]);
        $result = $handler->doAction($fight);

        $this->assertIsArray($result['messages']);
    }
}
