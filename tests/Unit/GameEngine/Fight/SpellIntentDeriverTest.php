<?php

namespace App\Tests\Unit\GameEngine\Fight;

use App\Entity\Game\StatusEffect;
use App\Enum\SpellIntent;
use App\Enum\SpellScope;
use App\GameEngine\Fight\SpellIntentDeriver;
use PHPUnit\Framework\TestCase;

/**
 * L'intention et la portee, derivees de ce que le geste fait deja (ARC-11a).
 *
 * GAME_ARCHETYPES § 3.1. Les deux etiquettes decident quels leviers qualifient
 * un geste et quelle fonction a le droit de l'ouvrir — les poser, c'est fermer
 * la boucle « arbre x materia » que le § 0 affirme depuis le debut sans que
 * rien ne l'impose.
 */
class SpellIntentDeriverTest extends TestCase
{
    /**
     * Les huit types d'effet de statut se rangent sans reste.
     *
     * **C'est ce qui rend la derivation legitime.** Une table qui laisserait
     * des restes dirait qu'on invente une distinction ; celle-ci ne fait que
     * nommer une distinction que la donnee portait deja.
     */
    public function testEveryStatusEffectTypeMapsToAnIntent(): void
    {
        $expected = [
            StatusEffect::TYPE_POISON => SpellIntent::Hinder,
            StatusEffect::TYPE_PARALYSIS => SpellIntent::Hinder,
            StatusEffect::TYPE_BURN => SpellIntent::Hinder,
            StatusEffect::TYPE_FREEZE => SpellIntent::Hinder,
            StatusEffect::TYPE_SILENCE => SpellIntent::Hinder,
            StatusEffect::TYPE_REGENERATION => SpellIntent::Heal,
            StatusEffect::TYPE_SHIELD => SpellIntent::Protection,
            StatusEffect::TYPE_BERSERK => SpellIntent::Buff,
        ];

        foreach ($expected as $type => $intent) {
            self::assertSame($intent, SpellIntent::fromStatusEffectType($type), $type);
        }
    }

    /**
     * Un type inconnu ne recoit pas d'intention par defaut.
     *
     * Deviner ferait entrer un geste muet dans une palette, et une palette
     * n'est utile que si elle refuse.
     */
    public function testAnUnknownStatusTypeYieldsNothing(): void
    {
        self::assertNull(SpellIntent::fromStatusEffectType('mirage'));
        self::assertNull(SpellIntentDeriver::deriveIntent(null, null, null));
        self::assertNull(SpellIntentDeriver::deriveIntent(0, 0, null));
        self::assertNull(SpellIntentDeriver::deriveScope(null, 1));
    }

    /**
     * Un geste qui blesse **et** marque reste un geste de degat.
     *
     * C'est l'ordre des questions, et il porte une regle du canon : une marque
     * doit etre portee par un geste de degat (§ 1.1, correction du § 9
     * quinquies), sans quoi une entrave d'un tour serait arithmetiquement nulle
     * en duel. Classer un tel geste en « entrave » le sortirait de la palette
     * de l'assaut, qui est pourtant celui qui le lance.
     */
    public function testADamagingGestureThatAlsoMarksStaysDamage(): void
    {
        self::assertSame(
            SpellIntent::Damage,
            SpellIntentDeriver::deriveIntent(12, null, StatusEffect::TYPE_BURN),
        );
    }

    /**
     * Le soin se lit avant l'effet, et ne se confond avec rien.
     */
    public function testHealingIsReadBeforeTheStatusEffect(): void
    {
        self::assertSame(SpellIntent::Heal, SpellIntentDeriver::deriveIntent(null, 20, null));
        self::assertSame(
            SpellIntent::Heal,
            SpellIntentDeriver::deriveIntent(null, 20, StatusEffect::TYPE_REGENERATION),
        );
    }

    /**
     * La portee suit l'intention, et l'aire decide du nombre.
     */
    public function testScopeFollowsTheIntentAndTheAreaDecidesTheCount(): void
    {
        self::assertSame(SpellScope::Target, SpellIntentDeriver::deriveScope(SpellIntent::Damage, 1));
        self::assertSame(SpellScope::Targets, SpellIntentDeriver::deriveScope(SpellIntent::Damage, 3));
        self::assertSame(SpellScope::Target, SpellIntentDeriver::deriveScope(SpellIntent::Hinder, 1));

        foreach ([SpellIntent::Heal, SpellIntent::Protection, SpellIntent::Buff] as $friendly) {
            self::assertSame(SpellScope::Ally, SpellIntentDeriver::deriveScope($friendly, 1));
        }
    }

    /**
     * **La portee `Group` ne se derive jamais.**
     *
     * Aucune colonne ne pourrait la faire apparaitre : c'est une decision
     * d'auteur, pas une propriete des chiffres. Un soin de groupe et un soin
     * d'allie ont exactement les memes valeurs — ce qui les separe est ce que
     * l'auteur a voulu, et la duree qu'ARC-11b y attache.
     */
    public function testTheGroupScopeIsNeverDerived(): void
    {
        foreach (SpellIntent::cases() as $intent) {
            foreach ([1, 2, 8] as $targets) {
                self::assertNotSame(
                    SpellScope::Group,
                    SpellIntentDeriver::deriveScope($intent, $targets),
                    sprintf('%s sur %d cibles.', $intent->label(), $targets),
                );
            }
        }
    }

    /**
     * Hostile et amical se repartissent les cinq intentions sans reste.
     *
     * C'est l'asymetrie du § 9 quinquies en germe : ce qui se pose sur les
     * allies se multiplie par leur nombre, ce qui se pose sur l'ennemi non.
     * Une intention qui ne serait ni l'un ni l'autre casserait le partage.
     */
    public function testEveryIntentIsEitherHostileOrFriendly(): void
    {
        $hostile = array_filter(SpellIntent::cases(), static fn (SpellIntent $i): bool => $i->isHostile());
        $friendly = array_filter(SpellIntent::cases(), static fn (SpellIntent $i): bool => !$i->isHostile());

        self::assertCount(2, $hostile);
        self::assertCount(3, $friendly);
        self::assertCount(5, SpellIntent::cases());
    }

    /**
     * Seule la portee `Group` se multiplie par la taille du groupe.
     *
     * La regle qui interdit d'equilibrer le controle comme un soutien : un
     * effet pose sur l'adversaire ne profite qu'au tour ou il est lance,
     * puisqu'un seul joueur agit par tour.
     */
    public function testOnlyTheGroupScopeMultipliesWithGroupSize(): void
    {
        foreach (SpellScope::cases() as $scope) {
            self::assertSame(
                SpellScope::Group === $scope,
                $scope->multipliesWithGroupSize(),
                $scope->label(),
            );
        }
    }

    /**
     * Les portees amicales et hostiles se repartissent sans reste.
     */
    public function testEveryScopeIsEitherFriendlyOrHostile(): void
    {
        $friendly = array_filter(SpellScope::cases(), static fn (SpellScope $s): bool => $s->isFriendly());

        self::assertCount(3, $friendly);
        self::assertCount(5, SpellScope::cases());
    }
}
