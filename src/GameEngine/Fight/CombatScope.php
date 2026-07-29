<?php

namespace App\GameEngine\Fight;

use App\Entity\Game\Domain;
use App\Entity\Game\Spell;
use App\Enum\CombatRegister;
use App\Enum\Element;

/**
 * L'action en cours, vue par les passifs (DOM-01).
 *
 * Un sort de feu lance depuis une matiere sertie, c'est `feu x sorts`. C'est la
 * seule chose que `CombatSkillResolver` a besoin de savoir pour decider quel
 * arbre s'exprime : le pyromancien (feu x sorts) s'applique, le berserker
 * (feu x melee) non, l'hydromancien (eau x sorts) non plus.
 *
 * **L'absence de portee n'est pas une portee vide.** `getCombatBonuses()` sans
 * portee rend le total non borne — ce que l'ecran d'inventaire affiche, et ce
 * que le code d'avant DOM-01 faisait partout. La borne s'applique la ou une
 * action reelle a lieu, jamais dans un affichage de fiche.
 *
 * **L'element neutre ne borne rien.** Un sort `Element::None` n'appartient a
 * aucune ecole : lui appliquer la borne d'element reviendrait a dire qu'aucun
 * arbre ne le sert, et un sort neutre deviendrait le seul du jeu a ne rien
 * gagner d'une vie de progression. Seul son registre borne.
 */
final readonly class CombatScope
{
    public function __construct(
        public Element $element,
        public CombatRegister $register,
    ) {
    }

    public static function ofSpell(Spell $spell): self
    {
        return new self($spell->getElement(), CombatRegister::Spell);
    }

    /**
     * Le domaine s'exprime-t-il sur cette action ?
     *
     * Un domaine sans registre est hors combat (recolte, artisanat) : il ne
     * s'exprime jamais ici, et ses passifs de metier ne sont pas le sujet.
     */
    public function admits(Domain $domain): bool
    {
        $register = $domain->getRegister();
        if ($register === null || $register !== $this->register) {
            return false;
        }

        if ($this->element === Element::None) {
            return true;
        }

        return $domain->getElement() === $this->element->value;
    }
}
