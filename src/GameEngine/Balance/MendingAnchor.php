<?php

namespace App\GameEngine\Balance;

/**
 * L'ancre des soins : ce qu'un geste de soin doit rendre (ARC-20a).
 *
 * GAME_VITALITY § 5, soeur symetrique d'`EncounterAnchor`. Le canon fixe deja ce
 * qu'un geste **retire** — *un geste de palier n retire 25 % des PV d'un commun
 * de palier n* —, mais rien ne disait ce qu'un geste **rend**, et les soins
 * livres valent 1 a 12 points sur une barre qui va desormais de 96 a 880.
 *
 * **La reponse a « les soins a valeur fixe sont-ils viables ? » est oui**, a une
 * condition : que la grille se **derive** au lieu de s'ecrire. Deux tables tenues
 * a la main divergent au premier ajustement du bestiaire ; une derivation ne
 * peut pas. C'est la ligne Soin / Soin+ / Mega-Soin des JRPG, et c'est
 * exactement ce que la derivation de matiera par palier sait deja produire.
 *
 * **L'obsolescence est une fonctionnalite, pas un defaut** : un soin de palier 1
 * rend 2,7 % d'une barre de palier 4, et c'est ce qui donne un sens a la
 * progression de materia. La seule chose a garantir est le **plancher du jour 1**
 * (GAME_MATERIA § 3) : l'accord d'entree gratuit ouvre un soin de **son** palier,
 * jamais un soin fige au palier 1.
 *
 * **Cette classe ne change aucune valeur de jeu** : elle mesure l'ecart entre ce
 * qu'un soin rend et ce qu'il devrait rendre. Deplacer les valeurs est le travail
 * d'ARC-20c.
 */
final class MendingAnchor
{
    /**
     * Ce qu'un soin direct rend, en part de la barre de son palier.
     *
     * Le meme quart que `EncounterAnchor::SHARE_OF_COMMON_LIFE`, et pour la meme
     * raison : *le direct est l'urgence* (GAME_ARCHETYPES § 7 bis) — le geste qui
     * sauve quelqu'un au bord, pas celui qui entretient une barre.
     */
    public const DIRECT_SHARE_OF_BAR = 0.25;

    /**
     * Ce qu'un depot rend **par tour**, en part de la barre de son palier.
     *
     * Sur les six tours d'un depot ordinaire, il rend pres de la moitie d'une
     * barre — davantage qu'un soin direct, et c'est voulu : *la duree etale la
     * valeur, elle ne l'augmente pas*, mais elle la pose en avance sur des
     * degats qu'on ne verra pas tomber (§ 7 bis). Ce qui borne un depot defensif,
     * c'est la barre de sa cible, qui l'ecrete toute seule.
     */
    public const DEPOSIT_SHARE_OF_BAR_PER_TURN = 0.08;

    /**
     * Ce qu'un soin direct de ce palier doit rendre.
     */
    public static function directHealFor(int $tier): int
    {
        return (int) round(VitalityLaw::barFor($tier) * self::DIRECT_SHARE_OF_BAR);
    }

    /**
     * Ce qu'un depot de ce palier doit rendre, par tour de rencontre.
     */
    public static function depositPerTurnFor(int $tier): int
    {
        return (int) round(VitalityLaw::barFor($tier) * self::DEPOSIT_SHARE_OF_BAR_PER_TURN);
    }

    /**
     * La valeur **totale** d'un depot de soin de ce palier, sur cette duree
     * (ARC-20c-b).
     *
     * *La duree etale la valeur, elle ne l'augmente pas* — mais la valeur par
     * tour, elle, est fixee par le **palier du geste** et jamais par la fiche
     * de l'effet : les statuts sont **partages** (la meme `regeneration` sert
     * des gestes de paliers differents), exactement comme les gestes des
     * monstres etaient partages avant `MonsterDamageLaw`. *Un joueur porte son
     * soin dans le palier de sa materia, pas dans une fiche commune.*
     */
    public static function depositTotalFor(int $tier, int $duration): int
    {
        return self::depositPerTurnFor($tier) * max(1, $duration);
    }

    /**
     * La part de barre que ce soin rend reellement, a ce palier.
     *
     * C'est par elle que se lit l'obsolescence : un soin garde sa valeur en
     * points et perd sa valeur en part, palier apres palier.
     */
    public static function shareOfBar(int $healing, int $tier): float
    {
        $bar = VitalityLaw::barFor($tier);

        return $bar > 0 ? $healing / $bar : 0.0;
    }

    /**
     * Le facteur qui separe ce qu'un soin rend de ce qu'il devrait rendre.
     *
     * Meme instrument que `EncounterAnchor::shortfallFor()`, et pour la meme
     * raison : *on ne recalibre pas ce qu'on ne mesure pas*.
     */
    public static function shortfallFor(int $healing, int $tier): float
    {
        if ($healing <= 0) {
            return \INF;
        }

        return self::directHealFor($tier) / $healing;
    }
}
