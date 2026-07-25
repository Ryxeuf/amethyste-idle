<?php

namespace App\Enum;

/**
 * Type de liaison d'un objet (ECO-01).
 *
 * Fondation de l'economie de production joueur : distingue ce qui circule sur
 * les canaux d'echange de ce qui est immobilise sur un personnage. Un objet lie
 * ne peut plus etre vendu a l'hotel des ventes ni depose en echoppe.
 *
 * Voir docs/GAME_PRINCIPLES.md §4 et docs/roadmap/PLAN_PLAYER_ECONOMY.md.
 */
enum BindType: string
{
    /** Librement echangeable — cas par defaut, le plus courant. */
    case None = 'none';

    /**
     * Lie au premier equipement. L'objet circule tant qu'il n'a pas ete porte :
     * il peut etre craft, vendu, revendu, mais s'immobilise des qu'un joueur
     * s'en equipe.
     */
    case BindOnEquip = 'bind_on_equip';

    /**
     * Lie des l'obtention. Reserve a ce qui ne doit jamais circuler :
     * recompenses de quete, objets narratifs, et (a terme) le stuff produit sur
     * commande d'artisan (ECO-08).
     */
    case BindOnPickup = 'bind_on_pickup';

    /**
     * Forme heritee : `Item.boundToPlayer` etait un booleen valant « lie des
     * l'obtention ». Conserve pour les fixtures et la migration.
     */
    public static function fromLegacyFlag(bool $boundToPlayer): self
    {
        return $boundToPlayer ? self::BindOnPickup : self::None;
    }

    public function label(): string
    {
        return match ($this) {
            self::None => 'Echangeable',
            self::BindOnEquip => 'Lie a l\'equipement',
            self::BindOnPickup => 'Lie a l\'obtention',
        };
    }

    /** Un objet de ce type peut-il etre echange entre joueurs avant usage ? */
    public function isTradableBeforeUse(): bool
    {
        return self::BindOnPickup !== $this;
    }
}
