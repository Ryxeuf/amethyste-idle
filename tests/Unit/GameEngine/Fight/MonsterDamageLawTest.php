<?php

namespace App\Tests\Unit\GameEngine\Fight;

use App\Entity\Game\Monster;
use App\Entity\Game\Spell;
use App\Enum\MonsterRank;
use App\GameEngine\Bestiary\MonsterStatTemplate;
use App\GameEngine\Fight\MonsterDamageLaw;
use PHPUnit\Framework\TestCase;

/**
 * Ce qu'un monstre retire de vie, mesure (ARC-17b).
 *
 * ARC-17a a derive ce qu'un monstre frappe de sa case et s'est arrete la ; ce
 * test verifie que la branche fait ce qu'elle annonce, **par le comportement**
 * et non par la presence d'un nom dans un fichier.
 */
class MonsterDamageLawTest extends TestCase
{
    /**
     * **Le defaut d'ARC-17a, mesure a l'endroit ou il faisait mal.**.
     *
     * Le meme geste — `none_attack_1` sert 38 des 65 especes — porte par un
     * commun de palier 1 et par un boss de palier 4 retirait *exactement la
     * meme chose*. C'est ce qui faisait que monter de palier rendait les
     * combats plus longs sans les rendre plus dangereux.
     */
    public function testTheSameGestureHitsDifferentlyDependingOnWhoCarriesIt(): void
    {
        $gesture = $this->gesture(1);

        $weakest = MonsterDamageLaw::damageFor($this->monster(1, MonsterRank::Common), $gesture, 1);
        $strongest = MonsterDamageLaw::damageFor($this->monster(4, MonsterRank::Boss), $gesture, 1);

        self::assertGreaterThan(
            $weakest,
            $strongest,
            'Un boss de palier 4 et un commun de palier 1 frappent pareil : la case ne decide de rien.',
        );

        // Le geste declare 1 des deux cotes ; ce qui separe les deux nombres est
        // la case, et rien d'autre.
        self::assertSame(MonsterStatTemplate::attackFor(1, MonsterRank::Common), $weakest);
        self::assertSame(MonsterStatTemplate::attackFor(4, MonsterRank::Boss), $strongest);
    }

    /**
     * **La derivation remplace un nombre, elle n'en cree pas.**.
     *
     * Sans ce refus, chaque soin et chaque entrave pure du bestiaire se
     * mettrait a frapper au palier de son porteur — un geste de soin de boss
     * retirerait 171 points de vie a sa cible. C'est l'ordre des questions
     * d'ARC-11a, lu ici : *le degat d'abord, et seulement s'il y en a un.*
     */
    public function testAGestureThatDoesNotWoundNeverStartsTo(): void
    {
        $boss = $this->monster(4, MonsterRank::Boss);

        self::assertFalse(MonsterDamageLaw::derives($this->gesture(null)));
        self::assertSame(0, MonsterDamageLaw::damageFor($boss, $this->gesture(null), 0));
        self::assertSame(0, MonsterDamageLaw::damageFor($boss, $this->gesture(0), 0));
    }

    /**
     * Un geste en pourcentage porte deja une echelle : on ne lui en impose pas
     * une seconde.
     */
    public function testAPercentGestureKeepsWhatItDeclared(): void
    {
        $gesture = $this->gesture(20);
        $gesture->setValueType(Spell::VALUE_TYPE_PERCENT);

        self::assertFalse(MonsterDamageLaw::derives($gesture));
        self::assertSame(37, MonsterDamageLaw::damageFor($this->monster(4, MonsterRank::Boss), $gesture, 37));
    }

    /**
     * **L'asymetrie survit a la branche** — c'est ce que le § 0.2 range parmi
     * les rapports qui tiennent quelles que soient les valeurs.
     *
     * Une elite frappe pres de trois fois un commun de son palier pour moins de
     * deux fois ses PV : l'asymetrie qui fait qu'elle tue un joueur seul. Une
     * branche qui la perdrait rendrait l'elite inoffensive sans rien dire.
     */
    public function testTheEliteAsymmetrySurvivesTheBranch(): void
    {
        $gesture = $this->gesture(1);

        foreach ([1, 2, 3, 4] as $tier) {
            $common = MonsterDamageLaw::damageFor($this->monster($tier, MonsterRank::Common), $gesture, 1);
            $elite = MonsterDamageLaw::damageFor($this->monster($tier, MonsterRank::Elite), $gesture, 1);

            self::assertGreaterThanOrEqual(
                2.5,
                $elite / $common,
                sprintf('T%d : l\'elite ne frappe plus pres de trois fois le commun.', $tier),
            );
        }
    }

    /**
     * Le coup sans geste — la riposte du donjon — est le meme nombre.
     *
     * Les deux chemins doivent rendre la **meme** valeur pour la meme case,
     * sinon le simulateur d'ARC-17c mesurerait deux lois selon qu'il joue une
     * zone ou un donjon.
     */
    public function testTheGestureLessStrikeIsTheSameNumber(): void
    {
        foreach ([1, 2, 3, 4] as $tier) {
            foreach (MonsterRank::cases() as $rank) {
                $monster = $this->monster($tier, $rank);

                self::assertSame(
                    MonsterDamageLaw::damageFor($monster, $this->gesture(1), 1),
                    MonsterDamageLaw::strikeFor($monster),
                    sprintf('T%d %s : la zone et le donjon ne frappent pas pareil.', $tier, $rank->value),
                );
            }
        }
    }

    private function monster(int $tier, MonsterRank $rank): Monster
    {
        $monster = new Monster();
        $monster->setTier($tier);
        $monster->setRank($rank);

        return $monster;
    }

    private function gesture(?int $damage): Spell
    {
        $spell = new Spell();
        $spell->setDamage($damage);

        return $spell;
    }
}
