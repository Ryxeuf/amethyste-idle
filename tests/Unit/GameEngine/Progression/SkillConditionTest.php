<?php

namespace App\Tests\Unit\GameEngine\Progression;

use App\Enum\SkillConditionKind;
use App\GameEngine\Progression\CombatLeverDefinitionException;
use App\GameEngine\Progression\SkillCondition;
use PHPUnit\Framework\TestCase;

/**
 * Les conditions de passif, leur vocabulaire et leur prix (ARC-12).
 *
 * GAME_ARCHETYPES § 4.3. C'est ce qui fait que **l'equipement est le build** au
 * lieu d'etre un total — la promesse de GAME_DOMAINS § 3, qui n'avait jamais eu
 * de quoi la tenir. `LeverGrant` portait une condition depuis ARC-03a en
 * annoncant que *ce qu'elle vaut est ARC-12* : jusqu'ici, une chaine que rien
 * n'interpretait.
 */
class SkillConditionTest extends TestCase
{
    /**
     * Un passif sans condition vaut ce qu'il affiche.
     */
    public function testAnUnconditionalPassiveIsWorthWhatItShows(): void
    {
        self::assertSame(1.0, SkillCondition::multiplierFor(null));
    }

    /**
     * Les conditions de build se paient x1,4, et portent une famille.
     *
     * *La famille, jamais la piece* (garde-fou 4) : un passif indexe sur un
     * objet precis vieillit avec lui, et un passif indexe sur la rarete
     * transforme l'arbre en prime au butin.
     */
    public function testBuildConditionsCostOnePointFourAndCarryAFamily(): void
    {
        $dagger = SkillCondition::parse('weapon:dagger');

        self::assertSame(SkillConditionKind::Build, $dagger->kind);
        self::assertSame('dagger', $dagger->subject);
        self::assertSame(1.4, $dagger->multiplier());
        self::assertTrue($dagger->isBuild());

        self::assertSame('plate', SkillCondition::parse('armor:plate')->subject);

        foreach ([SkillCondition::SHIELD, SkillCondition::OFFHAND_FREE, SkillCondition::DUAL_WIELD] as $bare) {
            $condition = SkillCondition::parse($bare);
            self::assertSame(SkillConditionKind::Build, $condition->kind);
            self::assertNull($condition->subject, $bare);
            self::assertSame(1.4, $condition->multiplier(), $bare);
        }
    }

    /**
     * Une condition de combat qui peut manquer se paie x2,0.
     */
    public function testACombatConditionThatCanBeAbsentCostsTwice(): void
    {
        foreach (SkillCondition::RARE_COMBAT as $raw) {
            $condition = SkillCondition::parse($raw);

            self::assertSame(SkillConditionKind::Combat, $condition->kind, $raw);
            self::assertSame(2.0, $condition->multiplier(), $raw);
            self::assertFalse($condition->isBuild(), $raw);
        }
    }

    /**
     * Une condition de combat frequente se paie au tarif d'un build.
     *
     * **C'est la correction du § 9 bis, et elle vaut 43 % de puissance.** Le
     * § 4.3 accorde x2,0 au combat *parce que la condition peut manquer* ; une
     * condition vraie plus des deux tiers du temps ne manque pas. « Vous avez
     * encaisse au tour precedent » est vraie des le tour 2 pour qui se bat au
     * contact, et `target_marked` l'est des le tour 1, la marque de son propre
     * element etant posee par un accord **gratuit** (§ 1.1).
     */
    public function testAFrequentCombatConditionIsPaidAtBuildRate(): void
    {
        foreach (SkillCondition::FREQUENT_COMBAT as $raw) {
            $condition = SkillCondition::parse($raw);

            self::assertSame(SkillConditionKind::Combat, $condition->kind, $raw);
            self::assertSame(
                SkillConditionKind::Build->defaultMultiplier(),
                $condition->multiplier(),
                sprintf('"%s" est frequente : la payer x2,0 offrirait 43 %% de puissance.', $raw),
            );
        }
    }

    /**
     * Le multiplicateur suit la frequence, pas la famille.
     *
     * Le test qui dit la regle : deux conditions de **meme nature** n'ont pas
     * le meme prix. Si la nature suffisait, cette assertion serait fausse.
     */
    public function testTheMultiplierFollowsFrequencyNotFamily(): void
    {
        $frequent = SkillCondition::parse(SkillCondition::FREQUENT_COMBAT[0]);
        $rare = SkillCondition::parse(SkillCondition::RARE_COMBAT[0]);

        self::assertSame($frequent->kind, $rare->kind);
        self::assertNotSame($frequent->multiplier(), $rare->multiplier());
    }

    /**
     * Une condition inconnue est refusee a la lecture.
     *
     * Meme discipline que `CombatLever` : un passif conditionne a une chaine
     * mal orthographiee serait **toujours inactif**, et un bonus silencieusement
     * mort est le pire des defauts — il se lit comme un choix de build.
     */
    public function testAnUnknownConditionIsRefusedOnRead(): void
    {
        foreach (['', '   ', 'weapon:', 'rarity:legendary', 'item:excalibur', 'quand-il-pleut'] as $bad) {
            try {
                SkillCondition::parse($bad);
                self::fail(sprintf('"%s" aurait du etre refusee.', $bad));
            } catch (CombatLeverDefinitionException) {
                self::assertTrue(true);
            }
        }
    }

    /**
     * La rarete et la piece nommee sont refusees par construction.
     *
     * Garde-fou 4 du § 4.3, tenu par le vocabulaire lui-meme plutot que par une
     * relecture : seuls `weapon:` et `armor:` prennent un sujet.
     */
    public function testRarityAndNamedPiecesAreRefusedByConstruction(): void
    {
        $this->expectException(CombatLeverDefinitionException::class);

        SkillCondition::parse('rarity:epic');
    }

    /**
     * Les deux listes de combat ne se recouvrent pas.
     *
     * Une condition presente des deux cotes aurait deux prix, et le premier
     * `in_array` gagnerait — un equilibrage decide par l'ordre des lignes.
     */
    public function testTheTwoCombatListsDoNotOverlap(): void
    {
        self::assertSame(
            [],
            array_intersect(SkillCondition::FREQUENT_COMBAT, SkillCondition::RARE_COMBAT),
        );
        self::assertSame(
            \count(SkillCondition::combatConditions()),
            \count(array_unique(SkillCondition::combatConditions())),
        );
    }

    /**
     * Aucun multiplicateur ne descend sous 1.
     *
     * Une condition **recompense**, elle ne punit jamais (garde-fou 5) : un
     * multiplicateur inferieur a 1 ferait d'une condition un malus deguise, et
     * le malus a deja sa mecanique — le pacte (§ 6.5, ARC-15).
     */
    public function testNoConditionEverPunishes(): void
    {
        foreach (SkillCondition::combatConditions() as $raw) {
            self::assertGreaterThanOrEqual(1.0, SkillCondition::parse($raw)->multiplier(), $raw);
        }

        foreach ([SkillCondition::SHIELD, SkillCondition::OFFHAND_FREE, SkillCondition::DUAL_WIELD] as $raw) {
            self::assertGreaterThanOrEqual(1.0, SkillCondition::parse($raw)->multiplier(), $raw);
        }

        self::assertGreaterThanOrEqual(1.0, SkillCondition::multiplierFor(null));
    }
}
