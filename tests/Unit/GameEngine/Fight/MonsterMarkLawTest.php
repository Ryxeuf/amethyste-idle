<?php

namespace App\Tests\Unit\GameEngine\Fight;

use App\Entity\Game\Monster;
use App\Entity\Game\Spell;
use App\Entity\Game\StatusEffect;
use App\Enum\Element;
use App\Enum\TrainingMode;
use App\GameEngine\Fight\ElementalMark;
use App\GameEngine\Fight\MonsterMarkLaw;
use PHPUnit\Framework\TestCase;

/**
 * La marque, du cote du monstre (ARC-13b-b).
 *
 * GAME_ARCHETYPES § 1.1, correction du § 9 ter : *les marques se portent des
 * deux cotes*. Sans le cote monstre, `ward` — qui figure dans deux palettes sur
 * quatre — n'a rien a quoi resister.
 */
class MonsterMarkLawTest extends TestCase
{
    private function monster(Element $element, ?TrainingMode $training = null): Monster
    {
        $monster = new Monster();
        $monster->setName('Cible');
        $monster->setElement($element);
        $monster->setTrainingMode($training);

        return $monster;
    }

    private function gesture(?int $damage, ?string $statusEffectSlug = null): Spell
    {
        $spell = new Spell();
        $spell->setSlug('geste');
        $spell->setName('Geste');
        $spell->setElement(Element::None);
        $spell->setDamage($damage);
        $spell->setHeal(null);
        $spell->setStatusEffectSlug($statusEffectSlug);

        return $spell;
    }

    /**
     * Un monstre laisse la marque de **son** element, pas celle de son geste.
     *
     * *Un joueur porte son element dans ses gestes ; un monstre le porte dans
     * sa peau.* Les gestes des monstres sont partages — `none_attack_1` sert
     * des especes de sept elements —, donc l'ecrire sur le geste obligerait a
     * dupliquer chaque attaque par element, ou a mentir.
     */
    public function testAMonsterLeavesTheMarkOfItsOwnElement(): void
    {
        foreach (ElementalMark::markedElements() as $element) {
            self::assertSame(
                ElementalMark::forElement($element),
                MonsterMarkLaw::markFor($this->monster($element), $this->gesture(3)),
                $element->value
            );
        }
    }

    /**
     * Un geste qui ne blesse pas ne marque pas (§ 1.1).
     *
     * C'est la meme loi que le cote joueur, et elle est arithmetique : une
     * entrave qui coute un tour plein pour un tour vole est nulle en duel
     * (§ 9 quinquies). Une marque voyage avec un coup.
     */
    public function testAGestureThatDoesNotWoundDoesNotMark(): void
    {
        self::assertNull(MonsterMarkLaw::markFor($this->monster(Element::Fire), $this->gesture(0)));
        self::assertNull(MonsterMarkLaw::markFor($this->monster(Element::Fire), $this->gesture(null)));
    }

    /**
     * `None` n'a pas de marque : ce n'est pas un element, c'est son absence.
     */
    public function testTheNeutralElementNeverMarks(): void
    {
        self::assertNull(MonsterMarkLaw::markFor($this->monster(Element::None), $this->gesture(5)));
    }

    /**
     * Un mannequin ne marque jamais (ONB-11).
     *
     * Ils sont deja d'element neutre, donc le refus precedent suffirait — mais
     * la clemence des mannequins se pose a **chaque** chemin plutot qu'a un
     * seul : il suffit d'un chemin oublie pour abimer un debutant.
     */
    public function testATrainingDummyNeverMarks(): void
    {
        foreach ([TrainingMode::Inert, TrainingMode::Capped] as $mode) {
            // Meme d'un element qui marque : c'est le mannequin qui refuse,
            // pas son element.
            self::assertNull(
                MonsterMarkLaw::markFor($this->monster(Element::Fire, $mode), $this->gesture(5)),
                $mode->value
            );
        }
    }

    /**
     * Le cote monstre ne pose qu'une marque **pure**.
     *
     * ARC-13a a decide que la mark-ness vit dans un catalogue et non dans le
     * type : la Brulure est **les deux**, un DOT et la marque du feu. Poser la
     * marque du feu depuis chaque monstre de feu ne leur donnerait donc pas une
     * marque, cela leur donnerait des **degats sur la duree** qu'ils n'avaient
     * pas — plus les 25 % de degats retires a leur cible par
     * `applyBurnReduction()`. C'est une decision d'equilibrage, pas de
     * marquage, et le § 0.2 interdit de la prendre a la main.
     *
     * C'est ce refus qui garantit la propriete du jalon : **aucune valeur de
     * combat ne bouge**.
     */
    public function testOnlyAPureMarkIsPosedByAMonster(): void
    {
        $pure = new StatusEffect();
        $pure->setSlug('soaked');
        $pure->setType(StatusEffect::TYPE_MARK);
        self::assertTrue(MonsterMarkLaw::poses($pure));

        // La Brulure est la marque du feu **et** un DOT. Elle ne passe pas.
        $burn = new StatusEffect();
        $burn->setSlug('burn');
        $burn->setType(StatusEffect::TYPE_BURN);
        self::assertFalse(MonsterMarkLaw::poses($burn));
    }

    /**
     * Un geste qui porte deja cette marque ne la pose pas deux fois.
     *
     * La reposer par l'autre chemin ne ferait que rafraichir une duree qui
     * vient d'etre posee, et gaspillerait un jet de `ward` que la cible a le
     * droit de ne subir qu'une fois par geste.
     */
    public function testAGestureThatAlreadyCarriesTheMarkIsNotDoubled(): void
    {
        $mark = ElementalMark::forElement(Element::Fire);
        self::assertNotNull($mark);

        self::assertNull(MonsterMarkLaw::markFor($this->monster(Element::Fire), $this->gesture(4, $mark)));

        // Mais un geste qui porte un **autre** statut marque quand meme : le
        // poison et la marque de la bete ne s'excluent pas.
        self::assertSame(
            ElementalMark::forElement(Element::Beast),
            MonsterMarkLaw::markFor($this->monster(Element::Beast), $this->gesture(4, 'poison'))
        );
    }
}
