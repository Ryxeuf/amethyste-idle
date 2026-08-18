<?php

namespace App\Tests\Unit\GameEngine\Fight;

use App\Entity\Game\StatusEffect;
use App\Enum\SpellScope;
use App\GameEngine\Fight\StanceLaw;
use PHPUnit\Framework\TestCase;

/**
 * La loi de la posture (ARC-18b).
 *
 * GAME_ARCHETYPES § 13.1, forme n° 7. Ce qui est verifie ici n'est pas
 * l'existence d'un champ mais **les quatre proprietes qui font qu'une posture
 * est une decision** : elle ne se pose que sur soi, elle ne vieillit pas, elle
 * deplace le budget sans l'ajouter, et elle ne deplace pas plus qu'un capstone.
 */
class StanceLawTest extends TestCase
{
    /**
     * Une posture se reconnait a son type, et a lui seul.
     *
     * Le type est le porteur de la forme parce qu'il est **ferme** : ajouter
     * une posture est une decision de moteur (une constante, une entree dans
     * `TYPES`, une intention), jamais une ligne de fixture.
     */
    public function testOnlyAStanceIsAStance(): void
    {
        self::assertTrue(StanceLaw::isStance($this->effect(StatusEffect::TYPE_STANCE)));
        self::assertFalse(StanceLaw::isStance($this->effect(StatusEffect::TYPE_BERSERK)));
        self::assertFalse(StanceLaw::isStance($this->effect(StatusEffect::TYPE_SHIELD)));
        self::assertFalse(StanceLaw::isStance($this->effect(StatusEffect::TYPE_RIPOSTE)));
    }

    /**
     * **Une posture ne vieillit pas.**.
     *
     * C'est ce qui la separe du depot, dont la duree *etale* une valeur totale
     * (ARC-11b). Une posture n'etale rien : elle vaut la meme chose a chaque
     * tour ou elle est la, et elle finit sur un evenement — on en pose une
     * autre, ou la rencontre s'acheve.
     */
    public function testAStanceIsTheOnlyEffectThatDoesNotAge(): void
    {
        self::assertTrue(StanceLaw::holdsThroughTheTurn($this->effect(StatusEffect::TYPE_STANCE)));

        foreach ([StatusEffect::TYPE_POISON, StatusEffect::TYPE_SHIELD, StatusEffect::TYPE_BERSERK, StatusEffect::TYPE_MARK, StatusEffect::TYPE_RIPOSTE] as $type) {
            self::assertFalse(StanceLaw::holdsThroughTheTurn($this->effect($type)), $type);
        }

        // Elle est posee strictement positive, sans quoi le moteur la lirait
        // comme expiree des le tour ou elle est prise.
        self::assertGreaterThan(0, StanceLaw::HELD);
    }

    /**
     * Une posture ne se pose que sur soi.
     *
     * Posee sur un allie, elle serait une amelioration de groupe — c'est-a-dire
     * un depot, qui a deja sa loi et sa borne. *Deux formes qui occupent la
     * meme place sont une seule forme sous deux noms.*
     */
    public function testAStanceOnlyEverLandsOnItsOwner(): void
    {
        self::assertTrue(StanceLaw::scopeIsLegal(SpellScope::SelfOnly));

        foreach ([SpellScope::Ally, SpellScope::Group, SpellScope::Target, SpellScope::Targets, null] as $scope) {
            self::assertFalse(StanceLaw::scopeIsLegal($scope), $scope?->value ?? 'null');
        }
    }

    /**
     * **Elle deplace le budget, elle ne l'ajoute pas.**.
     *
     * Le garde-fou du canon — *en changer coute le tour* — borne la
     * **frequence** des changements, jamais la **valeur** de ce qu'on prend :
     * une posture qui donnerait sans rien reprendre se paierait un tour et
     * rapporterait dix tours de bonus sur une rencontre ordinaire. La borne de
     * valeur se derive de la phrase qui definit la forme : une fourche
     * **repartit** les 50 points de l'arbre, donc une posture — la fourche a
     * l'echelle de la rencontre — repartit aussi.
     */
    public function testAStanceGivesNothingItDoesNotTakeBack(): void
    {
        self::assertTrue(StanceLaw::isBalanced(['power' => 6, 'guard' => -6]));
        self::assertTrue(StanceLaw::isBalanced(['power' => 6, 'guard' => -4, 'dodge' => -2]));

        // Le sacrifice reste legal : une posture qui rend plus qu'elle ne donne
        // est simplement mauvaise, et un mauvais choix n'a pas besoin d'etre
        // interdit. La regle ferme la porte au bouton gratuit, pas au pari.
        self::assertTrue(StanceLaw::isBalanced(['power' => 3, 'guard' => -9]));

        self::assertFalse(StanceLaw::isBalanced(['power' => 6]));
        self::assertFalse(StanceLaw::isBalanced(['power' => 6, 'guard' => -5]));
    }

    /**
     * Ce qu'elle pese est ce qu'elle **prend**, pas sa somme algebrique.
     *
     * Une posture a somme nulle qui deplacerait quarante points serait
     * equilibree au sens precedent et pourtant hors d'echelle : elle
     * transformerait un personnage en un autre le temps d'un tour. Les deux
     * mesures repondent a deux questions differentes, et les confondre
     * laisserait passer la seconde.
     */
    public function testItsWeightIsWhatItTakesNotWhatItNets(): void
    {
        self::assertSame(6, StanceLaw::weightOf(['power' => 6, 'guard' => -6]));
        self::assertSame(20, StanceLaw::weightOf(['power' => 14, 'critical' => 6, 'guard' => -20]));
        self::assertSame(0, StanceLaw::weightOf(['guard' => -6]));
        self::assertSame(0, StanceLaw::weightOf([]));
    }

    private function effect(string $type): StatusEffect
    {
        $effect = new StatusEffect();
        $effect->setSlug('stance-test');
        $effect->setType($type);

        return $effect;
    }
}
