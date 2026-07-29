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
use App\GameEngine\Progression\SynergyCalculator;
use App\GameEngine\Reputation\PatronageBonusResolver;
use Doctrine\Common\Collections\ArrayCollection;
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
        $synergyCalculator = $this->createMock(SynergyCalculator::class);
        $synergyCalculator->method('getSynergyBonuses')->willReturn([]);

        $equipmentSetResolver = $this->createMock(EquipmentSetResolver::class);
        $equipmentSetResolver->method('getSetBonuses')->willReturn([
            'damage' => 0, 'heal' => 0, 'hit' => 0, 'critical' => 0, 'life' => 0, 'protection' => 0,
        ]);

        // La borne du build (DOM-02) est neutralisee ici : ce fichier verrouille
        // la borne de l'action, et melanger les deux rendrait chaque echec
        // ambigu. `BuildDomainResolverTest` tient l'autre moitie.
        $buildDomainResolver = $this->createMock(BuildDomainResolver::class);
        $buildDomainResolver->method('isActive')->willReturn(true);

        $this->resolver = new CombatSkillResolver($buildDomainResolver, $synergyCalculator, $equipmentSetResolver, $this->neutralPatronage());
    }

    // =====================================================================
    // La case element x registre
    // =====================================================================

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
}
