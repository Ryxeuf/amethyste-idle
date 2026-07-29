<?php

namespace App\Enum;

/**
 * La doctrine d'un foyer (FOY-13).
 *
 * L'axe qui divise tout le monde — Extraire / Preserver (GAME_WORLD § 6.2) —
 * cesse d'etre une couleur de faction pour devenir un **batiment qu'on voit sur
 * l'ecran de zone**.
 *
 * Contrairement au **type** (FOY-01), qui se deduit de la frequentation et que
 * personne ne choisit, la doctrine se **decide** : une guilde la paie sur son
 * tresor. C'est la seule chose qu'une guilde choisit explicitement pour un
 * foyer, et c'est pour ca qu'elle doit couter, s'afficher, et ne pas se cumuler.
 *
 * **Exclusives par construction.** Un foyer porte une doctrine ou aucune, jamais
 * les deux : une seule colonne, donc aucun chemin de code ne peut les additionner.
 * Le plan disait « la guilde choisit, elle ne cumule pas » — un booleen par
 * atelier aurait laisse la porte ouverte, une colonne la ferme.
 */
enum SettlementDoctrine: string
{
    case Foundry = 'foundry';
    case Readers = 'readers';

    public function label(): string
    {
        return match ($this) {
            self::Foundry => 'Atelier de la Fonderie',
            self::Readers => 'Atelier des Lecteurs',
        };
    }
}
