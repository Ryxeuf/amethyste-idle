<?php

namespace App\GameEngine\Bestiary;

use App\Enum\MonsterRank;

/**
 * Le gabarit de stats par case tier × rang (BES-02, GAME_BESTIARY §3).
 *
 * Comme les filons ont des profils de palier et les materia une derivation
 * depuis le sort, les stats d'un monstre ne s'ecrivent pas a la main : elles
 * se derivent de sa case. C'est ce qui rend la courbe lisible et
 * l'equilibrage possible — la faille du milieu venait d'un saut de vie de ×4
 * entre le bloc de depart et le bloc de fin.
 *
 * La grille progresse de ×~2,2 par palier et ×~3 par rang, calee sur les
 * extremes observes (T1 commun ~30, T4 boss ~2400). Elle est un point de
 * depart a valider en jeu, pas une table figee.
 *
 * **La derivation est un defaut, pas une prison** : un monstre peut s'ecarter
 * du gabarit quand la fiction l'exige, mais l'ecart est alors explicite et
 * commente dans la fixture. Ce qui est interdit, c'est l'absence de gabarit.
 */
final class MonsterStatTemplate
{
    /**
     * Points de vie par case tier × rang. T0 ne sert qu'aux mannequins
     * d'entrainement, qui declarent leurs valeurs (ecart pedagogique).
     *
     * @var array<int, array<string, int>>
     */
    public const LIFE = [
        0 => ['common' => 30, 'elite' => 60, 'boss' => 100],
        1 => ['common' => 30, 'elite' => 90, 'boss' => 250],
        2 => ['common' => 70, 'elite' => 200, 'boss' => 550],
        3 => ['common' => 150, 'elite' => 420, 'boss' => 1100],
        4 => ['common' => 300, 'elite' => 850, 'boss' => 2400],
    ];

    public static function lifeFor(int $tier, MonsterRank $rank): int
    {
        return self::LIFE[max(0, min(4, $tier))][$rank->value];
    }

    /**
     * Ce qu'un commun de ce palier frappe — **le huitieme de sa propre vie**.
     *
     * ARC-17a. Le gabarit derivait la vie, la precision et la vitesse, et pas
     * ce que le monstre **fait** : les 65 especes livrees se partagent 17 gestes
     * d'attaque dont les degats vont de 1 a quelques points, si bien qu'un boss
     * de palier 4 et un commun de palier 1 peuvent porter le meme geste et
     * frapper pareil. La vie va de 30 a 2 400 sur la grille — un facteur 80 —
     * quand les degats recus ne bougent pas.
     *
     * **Ce n'est pas un detail d'equilibrage, c'est ce qui empeche de mesurer**
     * : quatre des cinq seuils qu'ARC-17 doit tenir en CI portent sur les degats
     * subis, a commencer par *une elite tue un joueur seul* (§ 9 octies).
     *
     * La grille se **derive de la vie** plutot que de s'ecrire, pour la meme
     * raison que le reste de cette classe — deux tables a la main divergent, une
     * derivation ne peut pas. Le rapport d'un huitieme n'est pas choisi au
     * hasard : il fait tomber le palier 2 sur **9 degats** pour un commun et
     * **26** pour une elite, c'est-a-dire exactement les deux nombres que le
     * § 9 octies a mesures.
     *
     * GAME_ARCHETYPES § 0.2 previent qu'aucun de ces nombres n'est definitif ;
     * ce qui est fige, c'est **le rapport** — une elite frappe pres de trois
     * fois un commun de son palier, pour moins de deux fois ses PV. C'est ce
     * qui fait qu'une elite n'est pas un commun gonfle.
     */
    public const ATTACK_FROM_LIFE_DIVISOR = 8;

    /**
     * Ce que le rang multiplie, en centiemes (§ 9 octies).
     *
     * L'elite frappe ~2,9x le commun de son palier ; le boss frappe plus fort
     * encore, mais son danger vient surtout de la duree — la fourchette de 12 a
     * 20 tours d'`EncounterAnchor` fait le reste.
     *
     * @var array<string, int>
     */
    public const ATTACK_RANK_MULTIPLIER = ['common' => 100, 'elite' => 290, 'boss' => 450];

    /**
     * Ce qu'un monstre de cette case frappe par geste.
     *
     * **Cette methode ne change aucune valeur de jeu** — comme `EncounterAnchor`
     * et `DailyAnchor` avant elle, elle rend une regle *calculable* pour qu'on
     * mesure l'ecart. Brancher la derivation dans la formule de combat est le
     * travail d'ARC-17b, et il deplacera de vraies valeurs.
     */
    public static function attackFor(int $tier, MonsterRank $rank): int
    {
        // Arrondi et non troncature : le palier 2 vaut 70/8 = 8,75, et le
        // tronquer rendrait 8 puis 23 la ou le canon a mesure 9 et 26. Une
        // derivation qui rate sa propre reference ne derive rien.
        $common = (int) round(self::lifeFor($tier, MonsterRank::Common) / self::ATTACK_FROM_LIFE_DIVISOR);

        return (int) round($common * self::ATTACK_RANK_MULTIPLIER[$rank->value] / 100);
    }

    /**
     * Precision : 70 + 5 par palier, +5 pour l'elite et le boss.
     */
    public static function hitFor(int $tier, MonsterRank $rank): int
    {
        return 70 + 5 * max(0, min(4, $tier)) + ($rank === MonsterRank::Common ? 0 : 5);
    }

    /**
     * Vitesse par defaut d'une case — la vitesse reste d'abord un trait
     * d'espece (la chauve-souris file, le zombie traine) : une valeur
     * declaree dans la fixture est l'ecart explicite, le gabarit ne sert
     * que de repli.
     */
    public static function speedFor(int $tier, MonsterRank $rank): int
    {
        return 6 + max(0, min(4, $tier)) + ($rank === MonsterRank::Boss ? 2 : 0);
    }
}
