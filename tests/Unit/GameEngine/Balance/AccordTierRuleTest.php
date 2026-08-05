<?php

namespace App\Tests\Unit\GameEngine\Balance;

use App\Enum\DomainRole;
use App\GameEngine\Balance\AccordTierRule;
use PHPUnit\Framework\TestCase;

/**
 * Le palier des accords suit la fonction (ARC-05b, correction 14).
 *
 * GAME_ARCHETYPES § 9 sexies : *le guerrier tue aussi vite que l'hydromancien,
 * survit mieux que tout le monde et ne paie rien*. La correction ne touche
 * aucun levier — elle deplace le **palier auquel une fonction ouvre ses gestes
 * de degat**, et l'ecart passe de « 9 tours contre 11 » a « 7 tours contre
 * 11-14 ».
 */
class AccordTierRuleTest extends TestCase
{
    /**
     * Seul l'assaut ouvre au palier plein.
     *
     * C'est le fond de la correction : la vitesse est ce que l'assaut achete
     * avec sa fragilite. La donner a une seconde fonction reviendrait a la lui
     * offrir en plus de ce qu'elle a deja.
     */
    public function testOnlyAssaultOpensAtFullTier(): void
    {
        self::assertTrue(AccordTierRule::opensAtFullTier(DomainRole::Assault));

        foreach ([DomainRole::Control, DomainRole::Upkeep, DomainRole::Bulwark] as $role) {
            self::assertFalse(
                AccordTierRule::opensAtFullTier($role),
                sprintf('%s ne peut pas ouvrir au palier plein.', $role->label()),
            );
        }
    }

    /**
     * Les trois autres fonctions descendent d'un cran, et d'un seul.
     */
    public function testTheOtherThreeFunctionsStepDownExactlyOneTier(): void
    {
        foreach ([2, 3, 4, 5] as $tier) {
            self::assertSame($tier, AccordTierRule::tierFor($tier, DomainRole::Assault));

            foreach ([DomainRole::Control, DomainRole::Upkeep, DomainRole::Bulwark] as $role) {
                self::assertSame(
                    $tier - 1,
                    AccordTierRule::tierFor($tier, $role),
                    sprintf('%s au palier %d.', $role->label(), $tier),
                );
            }
        }
    }

    /**
     * Le premier palier n'est jamais rabote.
     *
     * Un arbre dont l'accord d'entree n'ouvrirait rien ne se joue pas au jour 1
     * — ce que la regle du jour 1 de GAME_MATERIA § 3 interdit : les deux nœuds
     * `unlock` a 0 point sont le plancher du build, pour les quatre fonctions.
     */
    public function testTheEntryTierIsNeverShavedForAnyFunction(): void
    {
        foreach (DomainRole::cases() as $role) {
            self::assertSame(AccordTierRule::FLOOR, AccordTierRule::tierFor(1, $role));
            self::assertSame(AccordTierRule::FLOOR, AccordTierRule::tierFor(0, $role));
            self::assertSame(AccordTierRule::FLOOR, AccordTierRule::tierFor(-3, $role));
        }
    }

    /**
     * La regle couvre les quatre fonctions, sans trou.
     *
     * Une fonction ajoutee sans decision de palier heriterait du silence ; ce
     * test force le choix a etre ecrit.
     */
    public function testEveryFunctionHasARule(): void
    {
        foreach (DomainRole::cases() as $role) {
            $tier = AccordTierRule::tierFor(3, $role);

            self::assertGreaterThanOrEqual(AccordTierRule::FLOOR, $tier);
            self::assertLessThanOrEqual(3, $tier);
        }
    }
}
