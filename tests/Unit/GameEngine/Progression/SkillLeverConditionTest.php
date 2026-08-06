<?php

namespace App\Tests\Unit\GameEngine\Progression;

use App\Enum\CombatLever;
use App\GameEngine\Progression\CombatLeverDefinitionException;
use App\GameEngine\Progression\CombatLeverDefinitionLoader;
use App\GameEngine\Progression\CombatLeverScale;
use App\GameEngine\Progression\EquipmentPortCatalog;
use App\GameEngine\Progression\LeverGrant;
use App\GameEngine\Progression\SkillLeverPresenter;
use App\GameEngine\Progression\SkillLeverReader;
use PHPUnit\Framework\TestCase;

/**
 * Ce qu'une condition vaut, et ce qu'elle dit a l'ecran (ARC-12b).
 *
 * GAME_ARCHETYPES § 4.3. Deux choses se jouent ici, et c'est ce qui fait que
 * **l'equipement est le build** au lieu d'etre un total (GAME_DOMAINS § 3) :
 * une condition doit etre **refusee** si elle ne designe rien, et elle doit
 * **rapporter plus** que ce que ses points paient, puisque le budget compte
 * l'effet moyen.
 */
class SkillLeverConditionTest extends TestCase
{
    private function reader(): SkillLeverReader
    {
        $root = \dirname(__DIR__, 4);

        return new SkillLeverReader(
            new CombatLeverScale(new CombatLeverDefinitionLoader($root)),
            new EquipmentPortCatalog($root),
        );
    }

    private function presenter(): SkillLeverPresenter
    {
        $root = \dirname(__DIR__, 4);

        return new SkillLeverPresenter(
            $this->reader(),
            new CombatLeverScale(new CombatLeverDefinitionLoader($root)),
            new EquipmentPortCatalog($root),
        );
    }

    /**
     * Une condition inconnue est refusee **a la lecture**.
     *
     * ARC-12a avait pose la grammaire en annoncant ce refus ; le lecteur, lui,
     * ne verifiait que « non vide ». Une chaine mal orthographiee entrait donc
     * sans bruit et laissait son passif **toujours inactif** — et un bonus
     * silencieusement mort est le pire des defauts, parce qu'il se lit comme
     * un choix de build.
     */
    public function testAnUnknownConditionIsRefusedWhenRead(): void
    {
        $this->expectException(CombatLeverDefinitionException::class);

        $this->reader()->read([['lever' => 'power', 'points' => 3, 'condition' => 'wielding_a_katana']]);
    }

    /**
     * Une famille que l'echelle de port ne connait pas est refusee.
     *
     * **Le croisement OBJ** : la grammaire d'ARC-12a accepte `weapon:<n'importe
     * quoi>`, puisqu'elle ne verifie que le prefixe. L'echelle de port est deja
     * la table qui enumere les familles — la relire evite qu'une famille
     * renommee laisse derriere elle un passif mort.
     */
    public function testAFamilyThePortLadderDoesNotKnowIsRefused(): void
    {
        $this->expectException(CombatLeverDefinitionException::class);

        $this->reader()->read([['lever' => 'power', 'points' => 3, 'condition' => 'weapon:katana']]);
    }

    /**
     * Une famille du **mauvais cote** est refusee.
     *
     * `weapon:plate` nomme une famille qui existe — mais c'est une ligne
     * d'armure. L'echelle sait les separer (`line`), et c'est elle qui tranche :
     * une condition nomme la famille, la table decide de quel cote elle est.
     */
    public function testAFamilyOnTheWrongSideIsRefused(): void
    {
        $this->expectException(CombatLeverDefinitionException::class);

        $this->reader()->read([['lever' => 'power', 'points' => 3, 'condition' => 'weapon:plate']]);
    }

    /**
     * Les familles reelles passent, des deux cotes.
     */
    public function testTheRealFamiliesAreAccepted(): void
    {
        $grants = $this->reader()->read([
            ['lever' => 'power', 'points' => 3, 'condition' => 'weapon:dagger'],
            ['lever' => 'guard', 'points' => 3, 'condition' => 'armor:plate'],
            ['lever' => 'critical', 'points' => 3, 'condition' => 'shield'],
        ]);

        self::assertCount(3, $grants);
    }

    /**
     * Un passif conditionnel rapporte **plus** que ses points ne paient.
     *
     * C'est le cœur de la decision : le budget compte l'effet **moyen**, pas
     * l'effet affiche. Sans cet ecart, porter une dague ou une hache ne
     * changerait rien a ce qu'un arbre rend — et l'equipement resterait un
     * total.
     */
    public function testAConditionalNodeGrantsMoreThanItsPointsPayFor(): void
    {
        $reader = $this->reader();

        $plain = new LeverGrant(CombatLever::Power, 6);
        $build = new LeverGrant(CombatLever::Power, 6, 'weapon:dagger');

        self::assertSame($reader->averageEffectOf($plain), $reader->averageEffectOf($build), 'Le budget compte la meme chose des deux cotes.');
        self::assertGreaterThan($reader->effectOf($plain), $reader->effectOf($build), 'Un passif conditionnel doit rapporter davantage.');
    }

    /**
     * Deux conditions de meme nature n'ont pas le meme prix.
     *
     * La correction du § 9 bis, portee jusqu'a l'effet : une condition de
     * combat **frequente** (`target_marked` — la marque de son propre element
     * est posee des le tour 1 par un accord gratuit) se paie comme un build,
     * une condition qui peut reellement manquer se paie le double.
     */
    public function testTwoCombatConditionsOfTheSameNatureDoNotCostTheSame(): void
    {
        $reader = $this->reader();

        $frequent = new LeverGrant(CombatLever::Power, 6, 'target_marked');
        $rare = new LeverGrant(CombatLever::Power, 6, 'below_half_life');

        self::assertLessThan(
            $reader->effectOf($rare),
            $reader->effectOf($frequent),
            'Une condition vraie plus des deux tiers du temps ne peut pas valoir autant qu\'une qui manque.'
        );
    }

    /**
     * L'ecran dit ce qu'il faut porter, en clair.
     *
     * `weapon:dagger` est une cle de donnee ; « a la dague » est ce qu'un
     * joueur lit. Le libelle vient de l'echelle de port et jamais d'une table
     * parallele, pour qu'une famille renommee se renomme partout d'un coup.
     */
    public function testTheScreenSaysWhatToWearInPlainWords(): void
    {
        $readouts = $this->presenter()->readoutsOf($this->skillWithLevers([
            ['lever' => 'power', 'points' => 6, 'condition' => 'weapon:dagger'],
            ['lever' => 'guard', 'points' => 3, 'condition' => 'armor:plate'],
            ['lever' => 'critical', 'points' => 3],
        ]));

        self::assertCount(3, $readouts);
        self::assertSame('a la dague', $readouts[0]->requirement);
        self::assertSame('en plaque', $readouts[1]->requirement);
        self::assertNull($readouts[2]->requirement, 'Un nœud sans condition n\'exige rien.');
        self::assertTrue($readouts[0]->isConditional());
        self::assertFalse($readouts[2]->isConditional());
    }

    /**
     * L'ecran affiche l'effet **obtenu**, jamais l'effet moyen.
     *
     * Afficher l'effet moyen ferait croire qu'un passif conditionnel rend
     * moins qu'il ne rend, et personne ne le prendrait (§ 8 bis).
     */
    public function testTheScreenShowsTheEffectYouGetNotTheAverageTheBudgetCounts(): void
    {
        $reader = $this->reader();
        $grant = new LeverGrant(CombatLever::Power, 6, 'weapon:dagger');

        $readout = $this->presenter()->readoutsOf($this->skillWithLevers([$grant->toArray()]))[0];

        self::assertEqualsWithDelta($reader->effectOf($grant), $readout->effect, 0.001);
        self::assertGreaterThan($reader->averageEffectOf($grant), $readout->effect);
        self::assertStringStartsWith('+', $readout->formattedEffect());
    }

    /**
     * @param list<array<string, mixed>> $levers
     */
    private function skillWithLevers(array $levers): \App\Entity\Game\Skill
    {
        $skill = new \App\Entity\Game\Skill();
        $skill->setSlug('test-node');
        $skill->setLevers($levers);

        return $skill;
    }
}
