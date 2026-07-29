<?php

namespace App\GameEngine\Race;

/**
 * ONB-07 — ce qu'un peuple apporte, et ce qu'il n'apporte jamais.
 *
 * Les quatre peuples portaient des modificateurs de statistiques :
 * l'Humain a `0/0/0/0` face a l'Orc a `+8 vie`, soit **+40 % de survie** sur une
 * base de 20. Ce n'etait pas equilibre, et surtout c'etait un arbitrage de
 * puissance demande au pas 3 d'un tunnel ou aucune decision de build ne doit
 * etre prise (decision A8). Un joueur qui ne connait pas encore le jeu ne peut
 * pas arbitrer +8 vie contre +3 precision ; il subit ce choix pendant des mois.
 *
 * La regle qui remplace : **une capacite touche ce qu'on sait, jamais ce qu'on
 * produit** (A11). Aucune ne modifie des degats, des points de vie, un
 * rendement, un cout, un nombre d'actions ni un prix — un test le verrouille.
 * Et le corollaire qui l'equilibre : *le passif rattrape l'experience, il ne
 * remplace pas le talent*. Le Nain lit la purete du filon devant lui ; le
 * prospecteur, lui, sait ou et pour combien de temps — savoir monnayable que
 * personne ne recoit a la naissance.
 *
 * Ce fichier declare les capacites ; **ONB-07b** les branche sur leurs ecrans.
 */
enum RaceCapability: string
{
    /**
     * Nain — Lire la pierre : la bande de purete d'un filon est lisible
     * **avant** la recolte. Ne marche pas sur le prospecteur (RET-06) : le Nain
     * lit le filon devant lui, pas la carte des affleurements.
     */
    case ReadTheStone = 'read-the-stone';

    /**
     * Elfe — L'œil des lisieres : une exploration « rien » rend **un
     * reperage**. Jamais de butin, jamais de reduction de cout — sinon l'energie
     * cesse d'etre le regulateur qu'elle est.
     */
    case EyeOfTheMargins = 'eye-of-the-margins';

    /**
     * Orc — Le flair : element et faiblesse d'un monstre lisibles des la
     * **premiere** rencontre, sans attendre le palier de bestiaire.
     */
    case TheScent = 'the-scent';

    /**
     * Humain — Les usages : sur tout objet, les recettes qui le consomment et
     * les PNJ qui l'achetent, sans l'avoir decouvert.
     */
    case TheUses = 'the-uses';

    /**
     * Le peuple qui porte cette capacite.
     */
    public function raceSlug(): string
    {
        return match ($this) {
            self::ReadTheStone => 'dwarf',
            self::EyeOfTheMargins => 'elf',
            self::TheScent => 'orc',
            self::TheUses => 'human',
        };
    }

    public function nameKey(): string
    {
        return 'game.race.capability.' . $this->value . '.name';
    }

    public function descriptionKey(): string
    {
        return 'game.race.capability.' . $this->value . '.description';
    }

    public static function forRaceSlug(string $raceSlug): ?self
    {
        foreach (self::cases() as $capability) {
            if ($capability->raceSlug() === $raceSlug) {
                return $capability;
            }
        }

        return null;
    }
}
