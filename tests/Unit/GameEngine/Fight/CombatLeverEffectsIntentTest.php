<?php

namespace App\Tests\Unit\GameEngine\Fight;

use App\Enum\CombatLever;
use App\Enum\CombatRegister;
use App\Enum\SpellIntent;
use App\GameEngine\Fight\CombatLeverEffects;
use App\GameEngine\Progression\CombatLeverDefinitionLoader;
use App\GameEngine\Progression\CombatLeverScale;
use PHPUnit\Framework\TestCase;

/**
 * Le porteur ne contient que ce que le geste qualifie (ARC-11b-b).
 *
 * La loi est enoncee par `LeverIntentLaw` ; ce fichier verifie qu'elle est
 * **appliquee au porteur** et pas seulement disponible. C'est la difference qui
 * compte : un porteur qui ne contient que ce qui qualifie ne peut pas fuir,
 * quel que soit le consommateur qui viendra le lire ensuite.
 */
class CombatLeverEffectsIntentTest extends TestCase
{
    private function scale(): CombatLeverScale
    {
        return new CombatLeverScale(new CombatLeverDefinitionLoader(\dirname(__DIR__, 4)));
    }

    /**
     * Un soin ne porte pas `power`, un coup ne porte pas `mending`.
     */
    public function testTheCarrierDropsWhatTheGestureDoesNotExercise(): void
    {
        $points = ['power' => 10, 'mending' => 10];
        $scale = $this->scale();

        $onDamage = CombatLeverEffects::of($points, $scale, CombatRegister::Spell, SpellIntent::Damage);
        self::assertSame(1.1, round($onDamage->multiplierFor(CombatLever::Power, $scale), 4));
        self::assertSame(1.0, $onDamage->multiplierFor(CombatLever::Mending, $scale));

        $onHeal = CombatLeverEffects::of($points, $scale, CombatRegister::Spell, SpellIntent::Heal);
        self::assertSame(1.0, $onHeal->multiplierFor(CombatLever::Power, $scale));
        self::assertSame(1.1, round($onHeal->multiplierFor(CombatLever::Mending, $scale), 4));
    }

    /**
     * Sans intention, rien n'est ecarte — le comportement d'avant le jalon.
     */
    public function testNoIntentMeansNoIntentBound(): void
    {
        $scale = $this->scale();
        $levers = CombatLeverEffects::of(['power' => 10, 'mending' => 10], $scale, CombatRegister::Spell);

        self::assertSame(1.1, round($levers->multiplierFor(CombatLever::Power, $scale), 4));
        self::assertSame(1.1, round($levers->multiplierFor(CombatLever::Mending, $scale), 4));
    }

    /**
     * `aimedAt()` retrecit, jamais n'elargit.
     *
     * Le second bornage a lieu quand le type du statut a parle, donc **apres**
     * le premier. S'il pouvait rendre un levier ecarte en amont, l'ordre des
     * deux questions deviendrait une valeur de jeu.
     */
    public function testAimingNarrowsAndNeverWidens(): void
    {
        $scale = $this->scale();

        $onHeal = CombatLeverEffects::of(['power' => 10, 'grip' => 10], $scale, CombatRegister::Spell, SpellIntent::Heal);
        self::assertSame(1.0, $onHeal->multiplierFor(CombatLever::Power, $scale));

        // Le geste se revele etre une entrave : `grip` ne revient pas pour
        // autant, il n'avait pas ete achete sur ce chemin — et `power`, ecarte
        // en amont, ne revient pas non plus.
        $aimed = $onHeal->aimedAt(SpellIntent::Hinder);
        self::assertSame(1.0, $aimed->multiplierFor(CombatLever::Power, $scale));

        // Un porteur non borne, lui, se retrecit bien.
        $unbound = CombatLeverEffects::of(['power' => 10, 'grip' => 10], $scale, CombatRegister::Spell);
        self::assertSame(1.12, round($unbound->multiplierFor(CombatLever::Grip, $scale), 4));
        self::assertSame(1.0, $unbound->aimedAt(SpellIntent::Protection)->multiplierFor(CombatLever::Grip, $scale));
        self::assertSame(1.12, round($unbound->aimedAt(SpellIntent::Hinder)->multiplierFor(CombatLever::Grip, $scale), 4));
    }

    /**
     * `aimedAt(null)` rend le meme porteur.
     */
    public function testAimingAtNothingIsANoop(): void
    {
        $levers = CombatLeverEffects::of(['power' => 10], $this->scale(), CombatRegister::Spell);

        self::assertSame($levers, $levers->aimedAt(null));
    }

    /**
     * Le contrat du jalon : **aucune valeur de jeu ne bouge**.
     *
     * Aucun nœud livre ne porte de levier, donc le porteur est vide partout —
     * et un porteur vide, borne ou non, se lit exactement pareil.
     */
    public function testAnEmptyCarrierIsIndifferentToIntent(): void
    {
        $scale = $this->scale();

        foreach (SpellIntent::cases() as $intent) {
            $levers = CombatLeverEffects::of([], $scale, CombatRegister::Spell, $intent);
            self::assertTrue($levers->isEmpty(), $intent->label());
            self::assertTrue(CombatLeverEffects::none()->aimedAt($intent)->isEmpty(), $intent->label());
        }
    }
}
