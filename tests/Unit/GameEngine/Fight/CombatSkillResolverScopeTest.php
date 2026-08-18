<?php

namespace App\Tests\Unit\GameEngine\Fight;

use App\Entity\App\Player;
use App\Entity\Game\Domain;
use App\Entity\Game\Skill;
use App\Entity\Game\Spell;
use App\Enum\CombatRegister;
use App\Enum\Element;
use App\GameEngine\Fight\BuildDomainResolver;
use App\GameEngine\Fight\CombatScope;
use App\GameEngine\Fight\CombatSkillResolver;
use App\GameEngine\Fight\EquipmentSetResolver;
use App\GameEngine\Fight\StanceLeverReader;
use App\GameEngine\Progression\CombatLeverDefinitionLoader;
use App\GameEngine\Progression\CombatLeverScale;
use App\GameEngine\Progression\EquipmentPortCatalog;
use App\GameEngine\Progression\SkillLeverReader;
use App\GameEngine\Reputation\PatronageBonusResolver;
use App\GameEngine\Zone\LifeRegenManager;
use App\GameEngine\Zone\ManaRegenManager;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * La double borne des passifs (DOM-01).
 *
 * GAME_DOMAINS § 2 : « le critique +1 % du pyromancien ne s'applique qu'aux
 * sorts de feu, jamais au CaC ni a un sort d'eau ». Avant ce jalon, **tous** les
 * arbres s'exprimaient sur **toute** action : une vie de progression au
 * berserker ajoutait ses degats a un sort d'eau, et le build ne bornait rien.
 * Le defaut ne se voyait pas — il donnait simplement raison a qui montait tout.
 *
 * Ce qui se verrouille ici est la regle, pas un chiffre : la case
 * `element x registre`, la clause de retro-compatibilite qui laisse un nœud sans
 * domaine de combat se comporter comme avant, et la seule statistique qui
 * echappe a la borne.
 */
class CombatSkillResolverScopeTest extends TestCase
{
    private CombatSkillResolver $resolver;

    protected function setUp(): void
    {
        $equipmentSetResolver = $this->createMock(EquipmentSetResolver::class);
        $equipmentSetResolver->method('getSetBonuses')->willReturn([
            'damage' => 0, 'heal' => 0, 'hit' => 0, 'critical' => 0, 'life' => 0, 'protection' => 0,
        ]);

        // La borne du build (DOM-02) est neutralisee ici : ce fichier verrouille
        // la borne de l'action, et melanger les deux rendrait chaque echec
        // ambigu. `BuildDomainResolverTest` tient l'autre moitie.
        $buildDomainResolver = $this->createMock(BuildDomainResolver::class);
        $buildDomainResolver->method('isActive')->willReturn(true);

        $this->resolver = new CombatSkillResolver($buildDomainResolver, $equipmentSetResolver, $this->neutralPatronage(), $this->leverReader(), $this->leverScale(), $this->stanceReader(), $this->regen(LifeRegenManager::class), $this->regen(ManaRegenManager::class));
    }

    // =====================================================================
    // La case element x registre
    // =====================================================================

    /**
     * **Le malus d'un pacte arrive dans la formule** (ARC-18b).
     *
     * ARC-15 a livre le pacte comme *la seule mecanique du canon qui rende un
     * personnage mesurablement plus faible quelque part*. Il etait lu, valide
     * et compte au budget de l'arbre — et son malus s'arretait la : le
     * convertisseur refusait les totaux negatifs, si bien que **rien de ce qui
     * retire ne pouvait le traverser**. Invisible jusqu'ici parce qu'aucun nœud
     * livre ne porte encore de levier.
     *
     * Les deux comptes restent distincts, et c'est la lettre du § 6.5 : l'arbre
     * paie le **net** (un nœud a 19 pb dont 10 rendus pese 9), le personnage
     * porte le **brut** de chaque cote — +19 ici, −10 la.
     */
    public function testAPactMakesTheCharacterWeakerSomewhere(): void
    {
        $skill = $this->createMock(Skill::class);
        $skill->method('getDomains')->willReturn(new ArrayCollection([]));
        $skill->method('getLevers')->willReturn([
            ['lever' => 'power', 'points' => 19, 'pact' => ['lever' => 'life', 'points' => 10]],
        ]);

        $levers = $this->resolver->getCombatLevers($this->playerWith([$skill]));

        self::assertSame(19, $levers['power'], 'Le nœud ne rend plus ce qu\'il promet.');
        self::assertSame(-10, $levers['life'], 'Le pacte ne coute rien : ce n\'est plus un pacte, c\'est un cadeau.');
    }

    /**
     * Le passif du pyromancien sert le sort de feu.
     */
    public function testAPassiveAppliesOnItsOwnCell(): void
    {
        $player = $this->playerWith([$this->skill('fire', CombatRegister::Spell, damage: 7, critical: 3)]);

        $bonuses = $this->resolver->getCombatBonuses($player, new CombatScope(Element::Fire, CombatRegister::Spell));

        self::assertSame(7, $bonuses['damage']);
        self::assertSame(3, $bonuses['critical']);
    }

    /**
     * Le meme element, un autre registre : le berserker ne sert pas un sort.
     *
     * C'est le cas que le jalon existe pour fermer, et le plus courant : les
     * trois domaines de feu partageaient jusqu'ici leurs degats.
     */
    public function testTheSameElementInAnotherRegisterDoesNotApply(): void
    {
        $player = $this->playerWith([$this->skill('fire', CombatRegister::Melee, damage: 7)]);

        $bonuses = $this->resolver->getCombatBonuses($player, new CombatScope(Element::Fire, CombatRegister::Spell));

        self::assertSame(0, $bonuses['damage']);
    }

    /**
     * Le meme registre, un autre element : l'hydromancien ne sert pas le feu.
     */
    public function testTheSameRegisterInAnotherElementDoesNotApply(): void
    {
        $player = $this->playerWith([$this->skill('water', CombatRegister::Spell, damage: 7)]);

        $bonuses = $this->resolver->getCombatBonuses($player, new CombatScope(Element::Fire, CombatRegister::Spell));

        self::assertSame(0, $bonuses['damage']);
    }

    /**
     * Un nœud a cheval sur deux domaines s'applique des qu'**un** convient.
     *
     * Les competences inter-domaines existent dans les fixtures livrees ; exiger
     * que tous leurs domaines conviennent les rendrait inertes partout.
     */
    public function testACrossDomainSkillAppliesWhenOneOfItsDomainsFits(): void
    {
        $skill = $this->skillWithDomains([
            $this->domain('water', CombatRegister::Melee),
            $this->domain('fire', CombatRegister::Spell),
        ], damage: 5);

        $bonuses = $this->resolver->getCombatBonuses(
            $this->playerWith([$skill]),
            new CombatScope(Element::Fire, CombatRegister::Spell),
        );

        self::assertSame(5, $bonuses['damage']);
    }

    // =====================================================================
    // Ce qui echappe a la borne
    // =====================================================================

    /**
     * Sans portee, rien n'est borne — la fiche d'inventaire affiche un total,
     * pas une action.
     */
    public function testWithoutAScopeNothingIsBounded(): void
    {
        $player = $this->playerWith([
            $this->skill('fire', CombatRegister::Melee, damage: 4),
            $this->skill('water', CombatRegister::Spell, damage: 6),
        ]);

        self::assertSame(10, $this->resolver->getCombatBonuses($player)['damage']);
    }

    /**
     * Les points de vie maximum ne sont pas un geste.
     *
     * Les borner ferait varier la barre de vie d'un tour a l'autre selon le sort
     * choisi — un joueur verrait son maximum changer en changeant de cible.
     */
    public function testMaxLifeNeverDependsOnTheActionInProgress(): void
    {
        $player = $this->playerWith([$this->skill('water', CombatRegister::Melee, life: 25)]);

        $bounded = $this->resolver->getCombatBonuses($player, new CombatScope(Element::Fire, CombatRegister::Spell));

        self::assertSame(25, $bounded['life'], 'La vie a ete bornee : la barre de vie changerait selon le sort lance.');
        self::assertSame(25, $this->resolver->getCombatBonuses($player)['life']);
    }

    /**
     * Un nœud sans domaine de combat reste global.
     *
     * C'est la clause de retro-compatibilite : un nœud de recolte, d'artisanat
     * ou sans domaine n'a pas de case a comparer. Le borner reviendrait a
     * supprimer son passif partout, et c'est elle qui permet de typer les
     * domaines sans relire les 524 nœuds.
     */
    public function testASkillWithoutACombatDomainStaysGlobal(): void
    {
        $miner = $this->skill('earth', null, hit: 3);
        $orphan = $this->skillWithDomains([], hit: 2);

        $bonuses = $this->resolver->getCombatBonuses(
            $this->playerWith([$miner, $orphan]),
            new CombatScope(Element::Fire, CombatRegister::Spell),
        );

        self::assertSame(5, $bonuses['hit']);
    }

    /**
     * Un sort neutre n'est borne que par son registre.
     *
     * Aucun domaine ne porte l'element « aucun » : appliquer la borne
     * d'element ferait du sort neutre le seul du jeu a ne rien gagner d'une vie
     * de progression.
     */
    public function testANeutralSpellIsBoundedByItsRegisterOnly(): void
    {
        $player = $this->playerWith([
            $this->skill('fire', CombatRegister::Spell, damage: 4),
            $this->skill('water', CombatRegister::Spell, damage: 6),
            $this->skill('fire', CombatRegister::Melee, damage: 100),
        ]);

        $bonuses = $this->resolver->getCombatBonuses($player, new CombatScope(Element::None, CombatRegister::Spell));

        self::assertSame(10, $bonuses['damage']);
    }

    // =====================================================================
    // La portee du sort
    // =====================================================================

    public function testTheScopeOfASpellIsItsElementCastAsASpell(): void
    {
        $spell = $this->createMock(Spell::class);
        $spell->method('getElement')->willReturn(Element::Dark);

        $scope = CombatScope::ofSpell($spell);

        self::assertSame(Element::Dark, $scope->element);
        self::assertSame(CombatRegister::Spell, $scope->register);
    }

    /**
     * Un domaine hors combat n'est jamais admis, quelle que soit l'action.
     */
    public function testACraftDomainIsNeverAdmitted(): void
    {
        $scope = new CombatScope(Element::Metal, CombatRegister::Melee);

        self::assertFalse($scope->admits($this->domain('metal', null)));
        self::assertTrue($scope->admits($this->domain('metal', CombatRegister::Melee)));
    }

    // =====================================================================
    // Fabrique
    // =====================================================================

    private function domain(?string $element, ?CombatRegister $register): Domain&MockObject
    {
        $domain = $this->createMock(Domain::class);
        $domain->method('getElement')->willReturn($element);
        $domain->method('getRegister')->willReturn($register);
        $domain->method('isCombatDomain')->willReturn($register !== null);

        return $domain;
    }

    private function skill(
        ?string $element,
        ?CombatRegister $register,
        int $damage = 0,
        int $heal = 0,
        int $hit = 0,
        int $critical = 0,
        int $life = 0,
    ): Skill&MockObject {
        return $this->skillWithDomains([$this->domain($element, $register)], $damage, $heal, $hit, $critical, $life);
    }

    /**
     * @param list<Domain&MockObject> $domains
     */
    private function skillWithDomains(
        array $domains,
        int $damage = 0,
        int $heal = 0,
        int $hit = 0,
        int $critical = 0,
        int $life = 0,
    ): Skill&MockObject {
        $skill = $this->createMock(Skill::class);
        $skill->method('getDamage')->willReturn($damage);
        $skill->method('getHeal')->willReturn($heal);
        $skill->method('getHit')->willReturn($hit);
        $skill->method('getCritical')->willReturn($critical);
        $skill->method('getLife')->willReturn($life);
        $skill->method('getDomains')->willReturn(new ArrayCollection($domains));

        return $skill;
    }

    /**
     * @param list<Skill&MockObject> $skills
     */
    private function playerWith(array $skills): Player&MockObject
    {
        $player = $this->createMock(Player::class);
        $player->method('getSkills')->willReturn(new ArrayCollection($skills));

        return $player;
    }

    /**
     * Un patronage neutre : aucune couleur portee, rien d'amplifie.
     *
     * FAC-01 a ajoute une derniere etape a l'agregation des bonus. Ces tests
     * portent sur les bornes de domaine, pas sur les factions : un patronage
     * qui rendrait les bonus tels quels est la seule facon de les garder
     * lisibles — sans quoi chaque attendu chiffre porterait un pourcentage sans
     * rapport avec ce qu'il verifie.
     */
    private function neutralPatronage(): PatronageBonusResolver
    {
        $patronage = $this->createMock(PatronageBonusResolver::class);
        $patronage->method('amplify')->willReturnCallback(
            static fn (Player $player, array $bonuses): array => $bonuses,
        );

        return $patronage;
    }

    /**
     * Le lecteur de leviers reel, sur la configuration reelle (ARC-03b).
     *
     * Un double rendrait le test aveugle a la seule chose qui compte ici : les
     * leviers suivent la meme borne que les statistiques plates.
     */
    /**
     * Le lecteur de postures, sans combat a lire (ARC-18b).
     *
     * `heldBy()` rend `[]` des que le combat est `null`, ce qui est le cas de
     * tous les appels de ce fichier : une posture ne survit pas a la rencontre,
     * et ces tests portent sur les bornes de domaine.
     */
    /**
     * Un gestionnaire de regeneration muet (ARC-18c).
     *
     * Il ne sert qu'a la conversion, et aucun geste de ce fichier n'en est une.
     * Le doubler evite d'aller chercher un parametre en base pour une question
     * qu'on ne pose pas.
     *
     * @template T of LifeRegenManager|ManaRegenManager
     *
     * @param class-string<T> $class
     *
     * @return T
     */
    private function regen(string $class): LifeRegenManager|ManaRegenManager
    {
        return $this->createMock($class);
    }

    private function stanceReader(): StanceLeverReader
    {
        return new StanceLeverReader($this->leverScale(), $this->createMock(EntityManagerInterface::class));
    }

    private function leverScale(): CombatLeverScale
    {
        return new CombatLeverScale(new CombatLeverDefinitionLoader(\dirname(__DIR__, 4)));
    }

    private function leverReader(): SkillLeverReader
    {
        return new SkillLeverReader($this->leverScale(), new EquipmentPortCatalog(\dirname(__DIR__, 4)));
    }
}
