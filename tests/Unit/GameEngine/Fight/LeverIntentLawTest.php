<?php

namespace App\Tests\Unit\GameEngine\Fight;

use App\Enum\CombatLever;
use App\Enum\SpellIntent;
use App\GameEngine\Fight\LeverIntentLaw;
use PHPUnit\Framework\TestCase;

/**
 * Les leviers visent par intention (ARC-11b-b).
 *
 * GAME_ARCHETYPES § 3.1 : *`mending` ne touche que le soin, `grip` que
 * l'entrave.* Dit **une fois sur le geste**, plutot que quinze fois dans quinze
 * formules — et c'est la difference entre une regle et une habitude de code.
 */
class LeverIntentLawTest extends TestCase
{
    /**
     * Les deux exemples que le canon nomme, et ils suffisent a dire la loi.
     */
    public function testTheTwoLeversTheCanonNames(): void
    {
        self::assertTrue(LeverIntentLaw::qualifies(CombatLever::Mending, SpellIntent::Heal));
        self::assertFalse(LeverIntentLaw::qualifies(CombatLever::Mending, SpellIntent::Damage));

        self::assertTrue(LeverIntentLaw::qualifies(CombatLever::Grip, SpellIntent::Hinder));
        self::assertFalse(LeverIntentLaw::qualifies(CombatLever::Grip, SpellIntent::Protection));
    }

    /**
     * Le trou que le jalon ferme : `grip` sur une protection.
     *
     * `grip` porte la duree de **tout** statut applique — c'est la formule qui
     * le voulait, pas le canon. Un arbre d'entretien pouvait donc acheter le
     * levier principal du controle en teinte (10 pb hors palette) et rallonger
     * ses propres boucliers : un levier revendu a une autre fonction, sans
     * qu'aucun ecran ne le montre.
     */
    public function testGripNeverLengthensAProtectionOrAHeal(): void
    {
        foreach ([SpellIntent::Protection, SpellIntent::Heal, SpellIntent::Buff, SpellIntent::Damage] as $intent) {
            self::assertFalse(
                LeverIntentLaw::qualifies(CombatLever::Grip, $intent),
                sprintf('`grip` qualifie %s, alors que le § 3.1 le reserve a l\'entrave.', $intent->label())
            );
        }
    }

    /**
     * Cinq leviers ne sont pas des proprietes du geste.
     *
     * `life` et `recovery` sont deja hors double borne au canon (§ 4.2) — une
     * barre de vie ne change pas selon le geste choisi ; `tempo` precede le
     * geste ; `wind` rend par tour ; et `thrift` porte sur le cout, que **tout**
     * geste paie (GAME_MATERIA § 2.3 bis : *aucune exception par intention*).
     */
    public function testFiveLeversDoNotAim(): void
    {
        $doNotAim = [CombatLever::Life, CombatLever::Recovery, CombatLever::Tempo, CombatLever::Wind, CombatLever::Thrift];

        foreach (CombatLever::cases() as $lever) {
            self::assertSame(
                !\in_array($lever, $doNotAim, true),
                LeverIntentLaw::aims($lever),
                $lever->value
            );
        }

        foreach ($doNotAim as $lever) {
            foreach (SpellIntent::cases() as $intent) {
                self::assertTrue(LeverIntentLaw::qualifies($lever, $intent), $lever->value);
            }
        }
    }

    /**
     * Aucun levier ne qualifie les cinq intentions **en visant**.
     *
     * L'invariant qui empeche la loi de se vider : un levier qui vise doit
     * refuser au moins une intention, sinon il ne vise pas — il se declare
     * seulement.
     */
    public function testALeverThatAimsAlwaysRefusesSomething(): void
    {
        foreach (CombatLever::cases() as $lever) {
            if (!LeverIntentLaw::aims($lever)) {
                continue;
            }

            self::assertLessThan(
                \count(SpellIntent::cases()),
                \count(LeverIntentLaw::intentsOf($lever)),
                sprintf('%s pretend viser et n\'ecarte rien.', $lever->value)
            );
        }
    }

    /**
     * Toute intention qualifie au moins un levier.
     *
     * Le pendant du precedent : une intention qu'aucun levier ne sert serait
     * une intention qu'aucun arbre n'a de raison d'ouvrir. C'est vrai des cinq,
     * `thrift` et les quatre autres non-viseurs suffisant a le garantir — mais
     * la mesure vaut d'etre ecrite, parce que le jour ou l'on rendra `thrift`
     * conditionnel, elle tombera.
     */
    public function testEveryIntentIsServedBySomeLever(): void
    {
        foreach (SpellIntent::cases() as $intent) {
            $served = array_filter(
                CombatLever::cases(),
                fn (CombatLever $lever) => LeverIntentLaw::qualifies($lever, $intent)
            );

            self::assertNotEmpty($served, $intent->label());
        }
    }

    /**
     * Une intention illisible ne borne rien.
     *
     * On ne borne pas un geste qui ne dit pas ce qu'il fait : il reste a la
     * borne qu'il avait avant ce jalon — sa place dans la formule. Refuser par
     * defaut rendrait un passif silencieusement inactif, et un bonus mort se lit
     * comme un choix de build.
     */
    public function testAnUnreadableIntentBoundsNothing(): void
    {
        foreach (CombatLever::cases() as $lever) {
            self::assertTrue(LeverIntentLaw::qualifies($lever, null), $lever->value);
        }
    }
}
