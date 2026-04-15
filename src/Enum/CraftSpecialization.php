<?php

namespace App\Enum;

/**
 * Specialisation de metier d'artisanat (tier 2).
 *
 * Un joueur peut choisir une specialisation irreversible apres avoir atteint
 * le seuil d'XP de domaine requis. La specialisation accorde un bonus de qualite
 * sur le craft correspondant et debloquera plus tard des recettes exclusives.
 */
enum CraftSpecialization: string
{
    case Forgeron = 'forgeron';
    case Tanneur = 'tanneur';
    case Alchimiste = 'alchimiste';
    case Joaillier = 'joaillier';

    /**
     * Libelle affiche dans l'interface ("Maitre Forgeron", etc.).
     */
    public function label(): string
    {
        return match ($this) {
            self::Forgeron => 'Maitre Forgeron',
            self::Tanneur => 'Maitre Tanneur',
            self::Alchimiste => 'Maitre Alchimiste',
            self::Joaillier => 'Maitre Joaillier',
        };
    }

    /**
     * Slug du craft associe (cle utilisee dans les recettes et les domaines).
     */
    public function craftSlug(): string
    {
        return $this->value;
    }

    /**
     * Description courte affichee dans le choix de specialisation.
     */
    public function description(): string
    {
        return match ($this) {
            self::Forgeron => 'Maitre de la forge : armes et armures metalliques.',
            self::Tanneur => 'Maitre du cuir : armures legeres et accessoires.',
            self::Alchimiste => 'Maitre des potions : elixirs, baumes et enchantements.',
            self::Joaillier => 'Maitre des gemmes : bijoux et amplifications magiques.',
        };
    }
}
