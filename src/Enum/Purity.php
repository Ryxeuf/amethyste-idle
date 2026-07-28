<?php

namespace App\Enum;

/**
 * Bande de purete d'un lot (ECO-21).
 *
 * Emprunt a Star Wars Galaxies, **corrige de son defaut** : chez eux, toutes les
 * ressources portaient des statistiques continues, ce qui a transforme
 * l'artisanat en tableur. Ici, deux garde-fous. La ligne du cristal
 * **uniquement** — herbes, poissons, cuirs et bois restent fongibles. Et
 * **quatre bandes** au lieu d'une note : une note continue eclaterait
 * l'inventaire en autant de lots que de tirages, et rendrait toute comparaison
 * arithmetique plutot que lisible.
 *
 * Le `parfait` a une charge particuliere (GAME_WORLD § 5.4) : il sera le seul a
 * permettre l'eveil d'une materia. La rarete de la materia devient alors
 * structurelle, sans table de drop — mais c'est ECO-22 qui posera ce gate, pas
 * ce jalon.
 */
enum Purity: string
{
    case Trouble = 'trouble';
    case Clair = 'clair';
    case Pur = 'pur';
    case Parfait = 'parfait';

    /**
     * Position dans l'echelle, de 0 (trouble) a 3 (parfait).
     *
     * Sert aux comparaisons — « cette commande exige au moins du pur » — sans
     * exposer l'ordre de declaration de l'enum, qui n'est pas un contrat.
     */
    public function level(): int
    {
        return match ($this) {
            self::Trouble => 0,
            self::Clair => 1,
            self::Pur => 2,
            self::Parfait => 3,
        };
    }

    public function isAtLeast(self $other): bool
    {
        return $this->level() >= $other->level();
    }

    /**
     * Bande immediatement superieure, ou `null` au sommet.
     *
     * Sert a l'Affleurement de la semaine (RET-06), qui fait monter d'un cran la
     * bande maximale d'un filon.
     */
    public function next(): ?self
    {
        return match ($this) {
            self::Trouble => self::Clair,
            self::Clair => self::Pur,
            self::Pur => self::Parfait,
            self::Parfait => null,
        };
    }

    /**
     * Sceau du systeme de design a employer pour cette bande.
     *
     * La correspondance vit ici et non dans les gabarits pour une raison de
     * coherence : la bande s'affiche sur l'inventaire, l'hotel des ventes et les
     * commandes, et trois gabarits qui choisissent chacun leur sceau finiraient
     * par diverger. Aucune couleur n'est declaree — on **reutilise** les sceaux
     * du systeme de design, comme la regle du projet l'exige.
     */
    public function sealClass(): string
    {
        return match ($this) {
            self::Trouble => 'ds-seal-common',
            self::Clair => 'ds-seal-uncommon',
            self::Pur => 'ds-seal-rare',
            self::Parfait => 'ds-seal-amethyst',
        };
    }

    /**
     * Bandes ordonnees, de la plus basse a la plus haute.
     *
     * @return list<self>
     */
    public static function ordered(): array
    {
        return [self::Trouble, self::Clair, self::Pur, self::Parfait];
    }
}
