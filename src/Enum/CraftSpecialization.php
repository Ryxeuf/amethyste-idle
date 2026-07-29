<?php

namespace App\Enum;

/**
 * L'arbre d'artisanat dans lequel une specialisation se prend (DOM-04).
 *
 * **Ce n'est plus un choix unique pour le personnage.** Jusqu'a DOM-04, un
 * joueur ne pouvait etre specialise que dans **un** metier, et le choix etait
 * irreversible : devenir Forgeron fermait a jamais la maitrise du Tanneur.
 * C'etait exactement l'exclusivite *entre* arbres que la doctrine interdit —
 * « interdire un arbre serait interdire un geste » (GAME_DOMAINS § 1). Le
 * renoncement se joue desormais **dans** l'arbre, entre deux branches
 * (`config/game/craft_branches.yaml`).
 *
 * Cet enum designe donc l'arbre, pas la specialisation. La specialisation est
 * la **branche**, et elle se declare en configuration.
 */
enum CraftSpecialization: string
{
    case Forgeron = 'forgeron';
    case Tanneur = 'tanneur';
    case Alchimiste = 'alchimiste';
    case Joaillier = 'joaillier';
    // Les trois metiers de la Piste H (ECO-29/30/31). Ils avaient des arbres et
    // des recettes, mais aucune facon de s'y specialiser : le tailleur pouvait
    // etre le seul de la region a coudre des robes sans que rien ne le dise.
    case Cuisinier = 'cuisinier';
    case Charpentier = 'charpentier';
    case Tailleur = 'tailleur';

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
            self::Cuisinier => 'Maitre Cuisinier',
            self::Charpentier => 'Maitre Charpentier',
            self::Tailleur => 'Maitre Tailleur',
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
            self::Cuisinier => 'Maitre des fourneaux : vivres, festins et effets durables.',
            self::Charpentier => 'Maitre du bois : arcs, batons, fleches et mobilier.',
            self::Tailleur => 'Maitre du tissu : robes de sort et tenues de travail.',
        };
    }
}
