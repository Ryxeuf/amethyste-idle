<?php

namespace App\Tests\Unit\GameEngine\Dungeon;

use App\Entity\App\GroupDungeonMember;
use App\Entity\App\GroupDungeonRun;
use App\Entity\App\Parameter;
use App\Entity\App\Player;
use App\Entity\App\Zone;
use App\Entity\Game\Dungeon;
use App\Entity\Game\Monster;
use App\Enum\MonsterRank;
use App\GameEngine\Balance\VitalityLaw;
use App\GameEngine\Bestiary\MonsterStatTemplate;
use App\GameEngine\Dungeon\DungeonActionResolver;
use App\GameEngine\Dungeon\DungeonEncounterPicker;
use App\GameEngine\Dungeon\GroupDungeonCombatService;
use App\GameEngine\Dungeon\GroupDungeonException;
use App\GameEngine\Dungeon\GroupDungeonRewardService;
use App\GameEngine\Fight\ArmorMitigationResolver;
use App\GameEngine\Realtime\Dungeon\GroupDungeonCombatPublisher;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * DON-02 : la boucle semi-synchrone reste (tour, echeance, action par
 * defaut), mais l'action est celle du build (via `DungeonActionResolver`,
 * mocke ici — son contrat propre a son test), la rencontre riposte sur le
 * membre qui agit, et un donjon peut etre perdu.
 */
class GroupDungeonCombatServiceTest extends TestCase
{
    private EntityManagerInterface&MockObject $entityManager;
    private EntityRepository&MockObject $parameterRepository;
    private EntityRepository&MockObject $playerRepository;
    private GroupDungeonRewardService&MockObject $rewardService;
    private DungeonActionResolver&MockObject $actionResolver;
    private GroupDungeonCombatService $service;
    public \DateTimeImmutable $now;

    /** @var array<int, int> degat par id de joueur (defaut 100) */
    public array $damageByPlayer = [];

    /** @var array<int, array{share: float, turns: int}> transfert pose, par id de joueur (ARC-18d) */
    public array $transferByPlayer = [];

    /**
     * DON-03 : la faune servie par le tireur, par rang.
     *
     * **ARC-17b a retire leur coup declare.** Les scenarios DON-02 tenaient
     * leurs chiffres d'un `hit` pose a la main (10, 25, 30) — c'est-a-dire de
     * la *precision* du monstre, que la riposte depensait comme des degats. Ce
     * qu'ils frappent se derive maintenant de leur case, et les trois
     * constantes ci-dessous le disent une fois pour que les scenarios cessent
     * de recopier des nombres.
     *
     * @var array<string, ?Monster>
     */
    public array $monstersByRank = [];

    /** @var list<int> paliers demandes au tireur */
    public array $pickedTiers = [];

    /** Ce que la rencontre de chaque rang retire, derive de la case (ARC-17b). */
    private int $commonStrike = 0;
    private int $eliteStrike = 0;
    private int $bossStrike = 0;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->parameterRepository = $this->createMock(EntityRepository::class);
        $this->playerRepository = $this->createMock(EntityRepository::class);
        $this->parameterRepository->method('findOneBy')->willReturn(null);
        $this->entityManager->method('getRepository')->willReturnMap([
            [Parameter::class, $this->parameterRepository],
            [Player::class, $this->playerRepository],
        ]);
        $this->now = new \DateTimeImmutable('2026-07-25 12:00:00');

        $this->actionResolver = $this->createMock(DungeonActionResolver::class);
        $this->actionResolver->method('resolve')->willReturnCallback(
            function (Player $player, ?string $spellSlug = null): array {
                $action = [
                    'damage' => $this->damageByPlayer[$player->getId()] ?? 100,
                    'spellSlug' => $spellSlug,
                ];

                // ARC-18d — le geste de transfert, servi par le meme tireur que
                // le degat : le contrat du resolveur a son propre test, celui-ci
                // ne verifie que ce que le service en fait.
                if (isset($this->transferByPlayer[$player->getId()])) {
                    $action['transfer'] = $this->transferByPlayer[$player->getId()];
                }

                return $action;
            }
        );

        // ARC-17b — la riposte ne se declare plus, elle se derive de la case du
        // monstre. Ces trois-la sont de palier 1, donc leurs coups valent 4, 12
        // et 18 (`MonsterStatTemplate::attackFor`) : la precision qu'ils
        // portaient tenait lieu de degats, et elle redevient une precision.
        $this->monstersByRank = [
            'common' => $this->buildMonster('Loup cendre', MonsterRank::Common, 120),
            'elite' => $this->buildMonster('Alpha cendre', MonsterRank::Elite, 200),
            'boss' => $this->buildMonster('Ancien des bois', MonsterRank::Boss, 300),
        ];
        $this->commonStrike = MonsterStatTemplate::attackFor(1, MonsterRank::Common);
        $this->eliteStrike = MonsterStatTemplate::attackFor(1, MonsterRank::Elite);
        $this->bossStrike = MonsterStatTemplate::attackFor(1, MonsterRank::Boss);
        $this->pickedTiers = [];
        $picker = $this->createMock(DungeonEncounterPicker::class);
        $picker->method('pick')->willReturnCallback(function (int $tier, MonsterRank $rank): ?Monster {
            $this->pickedTiers[] = $tier;

            return $this->monstersByRank[$rank->value] ?? null;
        });

        $publisher = $this->createMock(GroupDungeonCombatPublisher::class);
        $this->rewardService = $this->createMock(GroupDungeonRewardService::class);
        $test = $this;
        // ARC-19 : ces tests mesurent la boucle du donjon, pas l'armure — le
        // resolveur rend le coup intact.
        $mitigation = $this->createMock(ArmorMitigationResolver::class);
        $mitigation->method('mitigate')->willReturnCallback(fn (int $damage) => $damage);
        $this->service = new class($this->entityManager, $publisher, $this->rewardService, $this->actionResolver, $picker, $mitigation, $test) extends GroupDungeonCombatService {
            public function __construct(EntityManagerInterface $em, GroupDungeonCombatPublisher $publisher, GroupDungeonRewardService $rewardService, DungeonActionResolver $actionResolver, DungeonEncounterPicker $picker, ArmorMitigationResolver $mitigation, private $test)
            {
                parent::__construct($em, $publisher, $rewardService, $actionResolver, $picker, $mitigation);
            }

            protected function now(): \DateTimeImmutable
            {
                return $this->test->now;
            }
        };
    }

    /**
     * ARC-17b — plus de `hit` : ce que la rencontre frappe vient de sa case.
     *
     * Le parametre existait parce que la riposte lisait la **precision** du
     * monstre comme des degats ; le passer encore laisserait croire qu'il decide
     * de quelque chose.
     */
    private function buildMonster(string $name, MonsterRank $rank, int $life, int $tier = 1): Monster
    {
        $monster = new Monster();
        $monster->setName($name);
        $monster->setRank($rank);
        $monster->setLife($life);
        $monster->setTier($tier);

        return $monster;
    }

    private function buildPlayer(int $id, int $life = 50): Player
    {
        $player = new Player();
        $player->setLife($life);
        (new \ReflectionProperty(Player::class, 'id'))->setValue($player, $id);

        return $player;
    }

    /** @param list<Player> $players */
    private function buildRun(array $players): GroupDungeonRun
    {
        $dungeon = new Dungeon();
        $dungeon->setName('Caverne');
        $dungeon->setMaxPlayers(4);
        $run = new GroupDungeonRun($dungeon, $players[0], (new Zone())->setSlug('z')->setName('Z'));
        $run->setStatus(GroupDungeonRun::STATUS_IN_PROGRESS);
        foreach ($players as $p) {
            $run->addMember(new GroupDungeonMember($run, $p));
        }

        return $run;
    }

    public function testStateInitializesCombat(): void
    {
        $run = $this->buildRun([$this->buildPlayer(1), $this->buildPlayer(2)]);

        $state = $this->service->state($run);

        $this->assertTrue($run->isCombatInitialized());
        // 2 membres * 120 PV/membre = 240 PV de rencontre (recalibre DON-02 :
        // 200 etait le reglage d'une rencontre sans riposte).
        $this->assertSame(240, $state['encounterHpMax']);
        $this->assertSame(240, $state['encounterHpCurrent']);
        $this->assertSame(1, $state['activePlayerId']);
        $this->assertSame(45, $state['turnRemainingSeconds']);
    }

    public function testActReducesEncounterAndAdvancesTurn(): void
    {
        $p1 = $this->buildPlayer(1);
        $p2 = $this->buildPlayer(2);
        $run = $this->buildRun([$p1, $p2]);
        $this->service->state($run); // init

        $state = $this->service->act($p1, $run);

        $this->assertSame(140, $state['encounterHpCurrent']); // 240 - 100
        $this->assertSame(2, $state['activePlayerId']); // tour avance vers p2
    }

    /**
     * DON-02 — le build modifie le degat : deux joueurs aux actions
     * differentes n'entament pas la rencontre pareil.
     */
    public function testTheBuildChangesTheDamage(): void
    {
        $p1 = $this->buildPlayer(1);
        $p2 = $this->buildPlayer(2);
        $this->damageByPlayer = [1 => 30, 2 => 90];
        $run = $this->buildRun([$p1, $p2]);
        $this->service->state($run);

        $this->service->act($p1, $run);
        $state = $this->service->act($p2, $run);

        $this->assertSame(120, $state['encounterHpCurrent']); // 240 - 30 - 90
    }

    /**
     * DON-02 — la riposte : la rencontre frappe le membre qui vient d'agir, et
     * agir a enfin un cout. ARC-17b : ce coup est celui de sa case.
     */
    public function testTheEncounterStrikesBack(): void
    {
        $p1 = $this->buildPlayer(1, 50);
        $p2 = $this->buildPlayer(2, 50);
        $run = $this->buildRun([$p1, $p2]);
        $this->service->state($run);

        $this->service->act($p1, $run);

        $this->assertSame(50 - $this->commonStrike, $p1->getLife());
        $this->assertSame(50, $p2->getLife(), 'Seul le membre qui vient d\'agir encaisse.');
    }

    /**
     * **ARC-18d — le transfert deplace le coup, il ne le reduit pas.**.
     *
     * C'est ce qui repare le defaut le plus structurel des huit formes : *notre
     * modele ne peut pas avoir d'aggro*. La rencontre frappe le membre qui
     * vient d'agir, il n'y a personne a provoquer — le transfert decide donc
     * qui paie, sans jamais demander au monstre qui il vise.
     *
     * Le test verifie **les deux moities ensemble**, parce que verifier la
     * premiere seule laisserait passer la faute qu'on veut interdire : un
     * allie qui prend moins **et** un protecteur qui prend le reste, dont la
     * somme vaut exactement le coup d'origine.
     */
    public function testAProtectorTakesHalfTheBlowForHisAlly(): void
    {
        $protector = $this->buildPlayer(1, 50);
        $ally = $this->buildPlayer(2, 50);
        $this->damageByPlayer = [1 => 0, 2 => 1];
        $this->transferByPlayer = [1 => ['share' => 0.5, 'turns' => 3]];

        $run = $this->buildRun([$protector, $ally]);
        $this->service->state($run);

        $this->service->act($protector, $run);
        $afterPosting = $protector->getLife();

        $this->service->act($ally, $run);

        $moved = $afterPosting - $protector->getLife();
        $borne = 50 - $ally->getLife();

        $this->assertGreaterThan(0, $moved, 'Le protecteur ne prend rien : le transfert est inerte.');
        $this->assertSame(
            $this->commonStrike,
            $moved + $borne,
            'Le coup a change de taille en changeant de porteur : le transfert est devenu une reduction de degats.'
        );
        $this->assertLessThan($this->commonStrike, $borne, 'L\'allie encaisse tout : rien n\'a ete deplace.');
    }

    /**
     * DON-02 — l'echec est atteignable : quand plus un membre ne tient
     * debout, le run passe FAILED et rien n'est distribue.
     */
    public function testTheRunFailsWhenEveryMemberIsDown(): void
    {
        // Juste ce qu'il faut de vie pour que la riposte de son propre tour le
        // couche — derive plutot qu'ecrit, pour que le scenario survive a la
        // recalibration qu'ARC-17c fera de la grille.
        $p1 = $this->buildPlayer(1, $this->commonStrike);
        $run = $this->buildRun([$p1]);
        $this->service->state($run);

        $state = $this->service->act($p1, $run);

        $this->assertSame(GroupDungeonRun::STATUS_FAILED, $state['status']);
        $this->assertTrue($p1->isDead());
        $this->assertNull($run->getTurnDeadline());
    }

    /**
     * Un membre a terre ne joue plus : le tour le saute apres chaque action,
     * et s'il est tombe hors du donjon en etant actif, son action volontaire
     * est refusee.
     */
    public function testADownedMemberIsSkipped(): void
    {
        $p1 = $this->buildPlayer(1, $this->commonStrike);
        $p2 = $this->buildPlayer(2, 50);
        $this->damageByPlayer = [1 => 5, 2 => 5];
        $run = $this->buildRun([$p1, $p2]);
        $this->service->state($run);

        $this->service->act($p1, $run); // riposte : p1 tombe a 0
        $this->assertTrue($p1->isDead());
        $this->assertSame(2, $run->getActivePlayerId());

        $this->service->act($p2, $run); // p2 agit, le tour saute p1 et lui revient
        $this->assertSame(2, $run->getActivePlayerId());
    }

    /**
     * Un membre tombe hors du donjon en etant le joueur actif ne peut pas
     * agir : son action est refusee, le tour passera aux survivants.
     */
    public function testAMemberDownedWhileActiveCannotAct(): void
    {
        $p1 = $this->buildPlayer(1, 50);
        $p2 = $this->buildPlayer(2, 50);
        $run = $this->buildRun([$p1, $p2]);
        $this->service->state($run); // tour actif = p1

        // p1 meurt hors du donjon (un combat de zone, par exemple).
        $p1->setLife(0);
        $p1->setDiedAt(new \DateTime());

        $this->expectException(GroupDungeonException::class);
        $this->expectExceptionMessage('game.zone.dungeon.error.member_down');
        $this->service->act($p1, $run);
    }

    public function testActRejectsWhenNotYourTurn(): void
    {
        $p1 = $this->buildPlayer(1);
        $p2 = $this->buildPlayer(2);
        $run = $this->buildRun([$p1, $p2]);
        $this->service->state($run); // tour actif = p1

        $this->expectException(GroupDungeonException::class);
        $this->service->act($p2, $run);
    }

    public function testOverdueTurnResolvesWithDefaultAction(): void
    {
        $p1 = $this->buildPlayer(1);
        $p2 = $this->buildPlayer(2);
        $run = $this->buildRun([$p1, $p2]);
        $this->service->state($run); // init, deadline = now + 45s, tour = p1

        // Le repository renvoie le joueur actif pour la resolution auto.
        $this->playerRepository->method('find')->willReturnCallback(
            fn ($id): ?Player => 1 === $id ? $p1 : (2 === $id ? $p2 : null)
        );

        // On avance le temps au-dela de l'echeance : le tour de p1 rate -> action par defaut.
        $this->now = $this->now->modify('+50 seconds');
        $state = $this->service->state($run);

        $this->assertSame(140, $state['encounterHpCurrent']); // p1 a inflige 100 par defaut
        $this->assertSame(50 - $this->commonStrike, $p1->getLife()); // et la riposte l'a touche
        $this->assertSame(2, $state['activePlayerId']); // tour avance vers p2
    }

    // =====================================================================
    // DON-03 — les etapes et les vraies rencontres
    // =====================================================================

    /**
     * La rencontre tombee ouvre l'etape suivante — et ne riposte pas. Le run
     * ne se termine qu'au boss : Common -> Elite -> Boss, `currentStep`
     * avance reellement.
     */
    public function testTheThreeStepsAreCrossedToTheBoss(): void
    {
        $p1 = $this->buildPlayer(1, 200);
        $this->damageByPlayer = [1 => 1000]; // chaque geste couche l'etape
        $run = $this->buildRun([$p1]);
        $this->rewardService->expects($this->once())->method('award');

        $state = $this->service->state($run);
        $this->assertSame(1, $state['currentStep']);
        $this->assertSame('Loup cendre', $state['encounterName']);

        $state = $this->service->act($p1, $run); // le commun tombe
        $this->assertSame(GroupDungeonRun::STATUS_IN_PROGRESS, $state['status']);
        $this->assertSame(2, $state['currentStep']);
        $this->assertSame('Alpha cendre', $state['encounterName']);
        $this->assertSame('elite', $state['encounterRank']);
        $this->assertSame(200, $p1->getLife(), 'Une rencontre qui tombe ne riposte pas.');

        $state = $this->service->act($p1, $run); // l'elite tombe
        $this->assertSame(3, $state['currentStep']);
        $this->assertSame('Ancien des bois', $state['encounterName']);

        $state = $this->service->act($p1, $run); // le boss tombe -> victoire
        $this->assertSame(GroupDungeonRun::STATUS_COMPLETED, $state['status']);
        $this->assertSame(2, $run->getCurrentStep());
        $this->assertNull($run->getTurnDeadline());
    }

    /**
     * Aucun sac de PV : la barre est la vie du monstre de l'etape multipliee
     * par la taille du groupe, et la riposte est le coup du monstre — une
     * elite frappe plus fort qu'un commun, sans reglage special.
     */
    public function testTheEncounterIsTheMonsterNotAnHpBag(): void
    {
        $p1 = $this->buildPlayer(1, 200);
        $p2 = $this->buildPlayer(2, 200);
        $this->damageByPlayer = [1 => 240, 2 => 50];
        $run = $this->buildRun([$p1, $p2]);

        $state = $this->service->state($run);
        $this->assertSame(240, $state['encounterHpMax'], 'Etape 1 : la vie du commun (120) x 2 membres.');

        $state = $this->service->act($p1, $run); // le commun tombe d'un coup
        $this->assertSame(400, $state['encounterHpMax'], 'Etape 2 : la vie de l\'elite (200) x 2 membres.');

        $this->service->act($p2, $run); // entame l'elite : la riposte est son coup
        $this->assertSame(200 - $this->eliteStrike, $p2->getLife(), 'L\'elite frappe son coup, pas le curseur.');
        $this->assertGreaterThan(
            $this->commonStrike,
            $this->eliteStrike,
            'Une elite qui frappe comme un commun rend l\'etape suivante indolore.',
        );
    }

    /**
     * Les rencontres se tirent au palier de la zone du donjon — et a defaut,
     * au palier de la zone du run. Le donjon ne definit pas ses creatures.
     */
    public function testEncountersAreDrawnFromTheZoneTier(): void
    {
        $p1 = $this->buildPlayer(1);
        $run = $this->buildRun([$p1]);
        $run->getDungeon()->setZone((new Zone())->setSlug('mines')->setName('Mines')->setTier(3));

        $this->service->state($run);

        $this->assertSame([3], $this->pickedTiers);
    }

    /**
     * Sans faune (palier vide, monstre supprime), le **repli** reprend : un
     * donjon ne refuse jamais de s'ouvrir pour un accident de repartition.
     *
     * **ARC-19 le derive au lieu de le figer** : le repli vaut une barre de
     * `VitalityLaw` par membre — 96 au palier 1 — la ou 120 etait ecrit en dur.
     * C'est ce que 120 signifiait deja, du temps ou la barre du canon valait
     * 120 PV a tous les paliers ; depuis ARC-20 elle va de 96 a 880, et *une
     * rencontre de groupe se calibre sur le pool de PV du groupe, jamais sur un
     * multiple du nombre de joueurs*.
     */
    public function testWithoutFaunaTheLegacyCursorsApply(): void
    {
        $this->monstersByRank = [];
        $p1 = $this->buildPlayer(1, 50);
        $p2 = $this->buildPlayer(2, 50);
        $run = $this->buildRun([$p1, $p2]);

        $state = $this->service->state($run);
        $this->assertSame(
            2 * VitalityLaw::barFor(1),
            $state['encounterHpMax'],
            '2 membres x la barre de leur palier — le repli se derive, il ne se dose pas.',
        );
        $this->assertNull($state['encounterName']);

        $this->service->act($p1, $run);
        $this->assertSame(40, $p1->getLife(), 'La riposte retombe sur le curseur de 10.');
    }

    public function testEncounterDefeatCompletesRun(): void
    {
        // 1 membre, trois etapes (120, 200, 300 PV), degat 100 : sept
        // attaques — la derniere couche le boss, et une rencontre qui tombe
        // ne riposte jamais.
        $p1 = $this->buildPlayer(1, 200);
        $run = $this->buildRun([$p1]);
        $this->service->state($run);

        $state = null;
        for ($i = 0; $i < 7; ++$i) {
            $state = $this->service->act($p1, $run);
        }

        $this->assertSame(GroupDungeonRun::STATUS_COMPLETED, $state['status']);
        $this->assertSame(0, $state['encounterHpCurrent']);
        // 7 actions, 4 ripostes seulement : les chutes d'etape (2 communs ->
        // non, 1 chute par etape x 3) ne ripostent pas. Ripostes subies :
        // le commun (1 fois), l'elite (1 fois), le boss (2 fois) — chacun a son
        // coup de case depuis ARC-17b.
        $this->assertSame(
            200 - $this->commonStrike - $this->eliteStrike - 2 * $this->bossStrike,
            $p1->getLife(),
        );
        $this->assertNull($run->getTurnDeadline());
    }
}
