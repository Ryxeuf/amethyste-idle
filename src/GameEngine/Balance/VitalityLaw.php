<?php

namespace App\GameEngine\Balance;

use App\Enum\MonsterRank;
use App\GameEngine\Bestiary\MonsterStatTemplate;

/**
 * La barre de vie du joueur : d'ou elle vient, et ce qu'elle vaut (ARC-20a).
 *
 * GAME_VITALITY § 4. Le personnage n'a **pas de niveau** (regle 6) et **rien ne
 * faisait monter sa barre** : `PlayerFactory::BASE_LIFE` vaut 20, plafonne entre
 * 26 et 40 PV une fois tout appris, quand `MonsterStatTemplate` fait **x80** de
 * T1 a T4 et qu'une elite T4 frappe **110**.
 *
 * Le canon, lui, raisonne depuis le premier jour avec « joueur 120 PV » au
 * palier 2 (GAME_ARCHETYPES § 9 bis, § 9 octies) : **tous ses chiffres reposent
 * sur une echelle que le code ne produit nulle part**, ce qui rend inatteignables
 * quatre des cinq seuils d'ARC-17 — ils portent sur les degats subis.
 *
 * **La loi.** *La barre d'un joueur de palier n vaut ce qu'une elite de son
 * palier lui prend en une rencontre entiere.*
 *
 * Les huit tours ne sont pas un chiffre de gout : c'est le **centre de la bande
 * de duree d'une elite**, deja livree dans `EncounterAnchor::TURN_BANDS`. La
 * barre est donc definie par le **format d'une rencontre**, jamais par une
 * table — et recalibrer le bestiaire la deplace toute seule, comme il deplace
 * deja ce qu'un geste doit valoir.
 *
 * **Cette classe ne change aucune valeur de jeu** : comme `EncounterAnchor`,
 * `DailyAnchor` et `MonsterStatTemplate::attackFor()` avant elle, elle rend une
 * regle *calculable* pour qu'on mesure l'ecart. La brancher est le travail
 * d'ARC-20b (le Socle) et d'ARC-20c (les cascades), et il deplacera de vraies
 * valeurs.
 *
 * GAME_ARCHETYPES § 0.2 previent qu'aucun de ces nombres n'est definitif. Ce qui
 * est fige ici, ce sont les **regles** : la barre se derive du bestiaire, une
 * elite prend une rencontre entiere, et le rapport ne depend pas du palier.
 */
final class VitalityLaw
{
    /**
     * Les paliers de vitalite du personnage.
     *
     * Le palier 0 du bestiaire n'en est pas un : il ne sert qu'aux mannequins
     * d'entrainement, qui ne frappent pas. Le **plancher** du jeu est le
     * palier 1 — un personnage qui sort du tunnel de creation, ou qui ne mene
     * que des arbres de metier, l'a sans rien avoir appris (GAME_VITALITY § 3.4).
     */
    public const FIRST_TIER = 1;
    public const LAST_TIER = 4;

    /** La bande de duree qui definit la barre — celle d'une elite. */
    public const ELITE_BAND = 'elite';

    /**
     * Combien de tours dure la rencontre qui definit la barre.
     *
     * **Derive et jamais ecrit** : c'est le centre de la bande de duree d'une
     * elite. Poser « 8 » en constante ferait diverger la barre de la bande le
     * jour ou l'une des deux bouge, et c'est tout ce que ce jalon cherche a
     * empecher.
     */
    public static function eliteEncounterTurns(): int
    {
        [$min, $max] = EncounterAnchor::TURN_BANDS[self::ELITE_BAND];

        return (int) round(($min + $max) / 2);
    }

    /**
     * La barre de vie d'un joueur de ce palier.
     *
     * *Ce qu'une elite de son palier lui prend en une rencontre entiere.*
     */
    public static function barFor(int $tier): int
    {
        $tier = max(self::FIRST_TIER, min(self::LAST_TIER, $tier));

        return self::eliteEncounterTurns() * MonsterStatTemplate::attackFor($tier, MonsterRank::Elite);
    }

    /**
     * Le plancher : la barre d'un personnage qui n'a ouvert aucun arbre de combat.
     *
     * **On ne peut pas se retrouver sans barre de vie** — meme principe que
     * l'outil de palier 1 offert avec l'arbre de recolte (OBJ-06) et que le
     * plancher du jour 1 de GAME_MATERIA § 3.
     */
    public static function floor(): int
    {
        return self::barFor(self::FIRST_TIER);
    }

    /**
     * La part de la barre qu'un monstre de cette case retire **par tour**.
     *
     * Elle ne depend pas du palier, et ce n'est pas une coincidence : les deux
     * membres du rapport derivent de la meme vie de commun. Monter de palier ne
     * rend donc ni plus ni moins fragile — il rend les rencontres plus longues
     * et plus cheres, ce qui est la promesse du bestiaire.
     */
    public static function shareTakenPerTurn(int $tier, MonsterRank $rank): float
    {
        $bar = self::barFor($tier);

        return $bar > 0 ? MonsterStatTemplate::attackFor($tier, $rank) / $bar : \INF;
    }

    /**
     * En combien de tours ce monstre vient a bout d'un joueur de son palier.
     *
     * Sans mitigation d'armure ni soin — c'est un instrument de mesure, pas une
     * simulation : la mitigation appartient a GAME_ITEMS et a ARC-19, les soins
     * a `MendingAnchor`.
     */
    public static function turnsToFall(int $tier, MonsterRank $rank): int
    {
        $attack = MonsterStatTemplate::attackFor($tier, $rank);

        return $attack > 0 ? (int) ceil(self::barFor($tier) / $attack) : \PHP_INT_MAX;
    }
}
