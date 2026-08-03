<?php

namespace App\Tests\Unit\Entity\Game;

use App\Entity\Game\Item;
use App\Entity\Game\Spell;
use App\Enum\CombatRegister;
use App\Enum\MateriaSlotType;
use PHPUnit\Framework\TestCase;

/**
 * Le registre du geste, et ce dont la materia herite (ARC-02).
 *
 * GAME_ARCHETYPES § 3 : avant ce jalon, les accords des arbres n'ouvraient
 * que des **sorts**. Un arbre de melee ou de distance n'ouvrait donc que des
 * sorts, et ses passifs — bornes a son registre — ne s'appliquaient a aucune
 * action : *un arbre d'archer etait un arbre de mage avec un arc*.
 *
 * Le registre repare cela en une ligne de modele, et la materia en herite
 * comme elle herite de l'element.
 */
class SpellRegisterTest extends TestCase
{
    /**
     * Un geste est un sort par defaut : tout ce qui est livre avant ce jalon
     * en est un, et la valeur par defaut le dit sans qu'on reprenne les 254
     * entrees une par une.
     */
    public function testAGestureIsASpellUnlessItSaysOtherwise(): void
    {
        $spell = new Spell();

        self::assertSame(CombatRegister::Spell, $spell->getRegister());
        self::assertFalse($spell->isTechnique());
    }

    /**
     * Tout ce qui n'est pas du registre des sorts est une **technique** — un
     * geste d'arme, qu'on l'abatte ou qu'on le decoche.
     */
    public function testEveryNonSpellRegisterIsATechnique(): void
    {
        foreach ([CombatRegister::Melee, CombatRegister::Ranged] as $register) {
            $spell = new Spell();
            $spell->setRegister($register);

            self::assertTrue($spell->isTechnique(), sprintf('Le registre "%s" est un geste d\'arme.', $register->value));
        }
    }

    /**
     * **Le genre de la materia suit le registre du geste qu'elle porte.**
     *
     * C'est ce qui fait qu'un emplacement de technique cesse d'etre un mur
     * sans porte (DOM-03) : une piece de plaque peut enfin declarer ce
     * qu'elle accepte, parce que quelque chose existe pour le remplir.
     */
    public function testTheMateriaInheritsTheRegisterOfItsGesture(): void
    {
        $technique = new Spell();
        $technique->setRegister(CombatRegister::Melee);

        $materia = new Item();
        $materia->setSpell($technique);

        self::assertSame(MateriaSlotType::Technique, $materia->getMateriaKind());

        $sort = new Spell();
        $spellMateria = new Item();
        $spellMateria->setSpell($sort);

        self::assertSame(MateriaSlotType::Spell, $spellMateria->getMateriaKind());
    }

    /**
     * La derivation lisait auparavant la seule presence d'un sort, donc toute
     * materia **sans** geste tombait en « technique » par defaut — une materia
     * vide n'est pourtant pas une technique. Elle est desormais `Free` : elle
     * n'exige rien de la piece qui l'accueille.
     */
    public function testAMateriaWithoutAGestureDemandsNothingOfItsSocket(): void
    {
        $materia = new Item();

        self::assertSame(MateriaSlotType::Free, $materia->getMateriaKind());
        self::assertFalse(MateriaSlotType::Spell->accepts($materia->getMateriaKind()));
        // Un emplacement libre accepte tout, y compris ce qui n'exige rien.
        self::assertTrue(MateriaSlotType::Free->accepts($materia->getMateriaKind()));
    }

    /**
     * Le registre est un vocabulaire **ferme** : trois valeurs, celles de
     * `CombatRegister` — les memes que celles du domaine (DOM-01). Un geste et
     * l'arbre qui l'ouvre parlent donc la meme langue, ce qui est la condition
     * de l'invariant « tout arbre ouvre au moins un geste de son registre ».
     */
    public function testTheRegisterVocabularyIsTheOneDomainsAlreadySpeak(): void
    {
        self::assertSame(
            ['spell', 'melee', 'ranged'],
            array_map(static fn (CombatRegister $r): string => $r->value, CombatRegister::cases()),
        );
    }
}
