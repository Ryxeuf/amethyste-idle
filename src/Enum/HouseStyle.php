<?php

namespace App\Enum;

/**
 * Ameublement d'une demeure (tache 129, HOU-05).
 *
 * Un **style** plutot qu'un catalogue de meubles a poser. La tache demandait
 * « meubles : personnalisation visible par les visiteurs » ; ce qui compte est
 * la seconde moitie de la phrase. Un vrai systeme de mobilier — objets,
 * emplacements, rendu — serait un chantier a lui seul, et il faudrait en
 * inventer tout le contenu. Le style rend la demeure reconnaissable des la
 * premiere visite, pour le prix d'un champ.
 */
enum HouseStyle: string
{
    case Bare = 'bare';
    case Rustic = 'rustic';
    case Bourgeois = 'bourgeois';
    case Workshop = 'workshop';
    case Garden = 'garden';

    public function label(): string
    {
        return match ($this) {
            self::Bare => 'Sans ameublement',
            self::Rustic => 'Rustique',
            self::Bourgeois => 'Bourgeois',
            self::Workshop => 'Atelier',
            self::Garden => 'Serre',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::Bare => 'Quatre murs et un toit.',
            self::Rustic => 'Bois brut, cheminee de pierre, odeur de resine.',
            self::Bourgeois => 'Tentures, argenterie et un fauteuil qu\'on n\'ose pas salir.',
            self::Workshop => 'Etabli encombre, outils au mur, copeaux partout.',
            self::Garden => 'Verriere, plantes en pot, lumiere du matin.',
        };
    }

    /**
     * Cout d'installation, en Gils.
     *
     * Un **gold sink cosmetique** : on paie pour etre vu, pas pour un avantage.
     * C'est la forme la plus saine de sink, puisqu'elle ne cree aucune pression
     * a depenser chez ceux que l'apparence n'interesse pas.
     */
    public function price(): int
    {
        return match ($this) {
            self::Bare => 0,
            self::Rustic => 2_000,
            self::Bourgeois => 8_000,
            self::Workshop => 5_000,
            self::Garden => 5_000,
        };
    }

    /**
     * @return list<self>
     */
    public static function purchasable(): array
    {
        return [self::Rustic, self::Workshop, self::Garden, self::Bourgeois];
    }
}
