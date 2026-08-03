<?php

namespace App\Tests\Unit\GameEngine\Fight;

use App\Entity\App\Player;
use App\Entity\Game\Domain;
use App\Entity\Game\Skill;
use App\Enum\CombatRegister;
use App\Enum\Element;
use App\GameEngine\Fight\BuildDomainResolver;
use App\GameEngine\Fight\CombatScope;
use App\GameEngine\Fight\CombatSkillResolver;
use App\GameEngine\Progression\CombatLeverDefinitionLoader;
use App\GameEngine\Progression\CombatLeverScale;
use App\GameEngine\Progression\SkillLeverReader;
use App\GameEngine\Fight\EquipmentSetResolver;
use App\GameEngine\Progression\SynergyCalculator;
use App\GameEngine\Reputation\PatronageBonusResolver;
use Doctrine\Common\Collections\ArrayCollection;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Les deux bornes se cumulent (DOM-02).
 *
 * DOM-01 a borne par l'action : « ce geste-ci n'est pas celui de cet arbre ».
 * DOM-02 borne par ce qu'on porte : « rien sur toi n'exprime cet arbre ». Les
 * deux sont necessaires, et pour des raisons differentes — sans la seconde, un
 * joueur qui monte les trente-six arbres les exprime tous des qu'une action
 * tombe dans la bonne case, et le build ne decide de rien.
 *
 * L'invariant le plus important du jalon est celui qui **n'ajoute pas** de
 * borne : les accords. Une matiere apprise reste apprise, meme quand rien ne la
 * porte — « le savoir n'est jamais borne, seule l'expression l'est »
 * (GAME_DOMAINS § 1).
 */
class CombatSkillResolverBuildTest extends TestCase
{
    /**
     * Le passif est dans la bonne case, mais rien ne le porte.
     */
    public function testAPassiveInTheRightCellStillNeedsTheBuildToCarryIt(): void
    {
        $resolver = $this->resolver(carried: false);
        $player = $this->playerWith([$this->skill('fire', CombatRegister::Spell, damage: 9)]);

        $bonuses = $resolver->getCombatBonuses($player, new CombatScope(Element::Fire, CombatRegister::Spell));

        self::assertSame(0, $bonuses['damage'], 'Le build ne porte aucune source de ce domaine : il ne doit rien exprimer.');
    }

    /**
     * Et il s'applique des que le build le porte.
     */
    public function testTheSamePassiveAppliesOnceTheBuildCarriesIt(): void
    {
        $resolver = $this->resolver(carried: true);
        $player = $this->playerWith([$this->skill('fire', CombatRegister::Spell, damage: 9)]);

        $bonuses = $resolver->getCombatBonuses($player, new CombatScope(Element::Fire, CombatRegister::Spell));

        self::assertSame(9, $bonuses['damage']);
    }

    /**
     * Sans portee, la borne materielle ne s'applique pas non plus.
     *
     * La fiche d'inventaire montre ce qu'un joueur a **appris**, pas ce que sa
     * tenue du moment exprime ; l'y borner ferait baisser ses chiffres en
     * rangeant une arme, sans que rien ne l'explique.
     */
    public function testTheDisplayTotalIgnoresTheBuildToo(): void
    {
        $resolver = $this->resolver(carried: false);
        $player = $this->playerWith([$this->skill('fire', CombatRegister::Spell, damage: 9)]);

        self::assertSame(9, $resolver->getCombatBonuses($player)['damage']);
    }

    /**
     * La vie reste hors de toute borne, y compris materielle.
     */
    public function testMaxLifeIsNeverBoundedByTheBuildEither(): void
    {
        $resolver = $this->resolver(carried: false);
        $player = $this->playerWith([$this->skill('fire', CombatRegister::Spell, life: 30)]);

        $bonuses = $resolver->getCombatBonuses($player, new CombatScope(Element::Fire, CombatRegister::Spell));

        self::assertSame(30, $bonuses['life']);
    }

    /**
     * **Les accords ne sont jamais bornes par le build.**.
     *
     * C'est l'invariant central de la doctrine des trois couches : le savoir
     * reste acquis. Un joueur qui retire sa matiere de feu perd l'usage du sort,
     * pas la competence qui l'a debloque — et la remettre suffit.
     */
    public function testUnlockedMateriaAccordsSurviveAnEmptyBuild(): void
    {
        $resolver = $this->resolver(carried: false);

        $skill = $this->createMock(Skill::class);
        $skill->method('getActions')->willReturn(['materia' => ['unlock' => 'fire-bolt']]);

        $slugs = $resolver->getUnlockedMateriaSpellSlugs($this->playerWith([$skill]));

        self::assertSame(['fire-bolt'], array_values($slugs));
    }

    // =====================================================================
    // Fabrique
    // =====================================================================

    private function resolver(bool $carried): CombatSkillResolver
    {
        $synergyCalculator = $this->createMock(SynergyCalculator::class);
        $synergyCalculator->method('getSynergyBonuses')->willReturn([]);

        $equipmentSetResolver = $this->createMock(EquipmentSetResolver::class);
        $equipmentSetResolver->method('getSetBonuses')->willReturn([
            'damage' => 0, 'heal' => 0, 'hit' => 0, 'critical' => 0, 'life' => 0, 'protection' => 0,
        ]);

        $buildDomainResolver = $this->createMock(BuildDomainResolver::class);
        $buildDomainResolver->method('isActive')->willReturn($carried);

        return new CombatSkillResolver($buildDomainResolver, $synergyCalculator, $equipmentSetResolver, $this->neutralPatronage(), $this->leverReader(), $this->leverScale());
    }

    private function skill(
        ?string $element,
        ?CombatRegister $register,
        int $damage = 0,
        int $life = 0,
    ): Skill&MockObject {
        $domain = $this->createMock(Domain::class);
        $domain->method('getElement')->willReturn($element);
        $domain->method('getRegister')->willReturn($register);
        $domain->method('isCombatDomain')->willReturn($register !== null);

        $skill = $this->createMock(Skill::class);
        $skill->method('getDamage')->willReturn($damage);
        $skill->method('getHeal')->willReturn(0);
        $skill->method('getHit')->willReturn(0);
        $skill->method('getCritical')->willReturn(0);
        $skill->method('getLife')->willReturn($life);
        $skill->method('getDomains')->willReturn(new ArrayCollection([$domain]));

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
    private function leverScale(): CombatLeverScale
    {
        return new CombatLeverScale(new CombatLeverDefinitionLoader(\dirname(__DIR__, 4)));
    }

    private function leverReader(): SkillLeverReader
    {
        return new SkillLeverReader($this->leverScale());
    }
}
