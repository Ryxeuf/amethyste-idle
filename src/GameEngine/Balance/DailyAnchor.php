<?php

namespace App\GameEngine\Balance;

use App\Enum\CombatRegister;

/**
 * La seconde ancre : ce qu'une rencontre coute, rapporte a la journee (ARC-05b).
 *
 * `EncounterAnchor` (ARC-05a) ancre l'equilibrage sur la **duree d'un combat**.
 * GAME_ARCHETYPES § 6.4 previent que c'est une moitie seulement, et le § 9 ter
 * l'a mesure : un Soldat et un Guerisseur tiennent **onze tours tous les deux**
 * et sortent avec une barre comparable — mais le premier n'a **rien depense** et
 * le second a vide ~108 PM sur 120. Sur un combat ils sont equivalents ; sur les
 * ~16 combats qu'une journee d'energie autorise, ils n'ont rien a voir.
 *
 * > **Un archetype ne se juge pas sur un combat, il se juge sur la journee que
 * > la barre d'energie autorise.**
 *
 * D'ou la monnaie commune du § 9 septies.2 : **le temps**. Les PV paient les
 * coups recus, les PM paient les gestes faits, les deux se rechargent en temps
 * reel — et c'est ce temps d'attente, cumule sur une journee, qui met les quatre
 * fonctions sur la meme ligne. C'est aussi la seule facon de comparer un
 * archetype qui paie en fragilite a un archetype qui paie en ressource.
 *
 * **Cette classe ne deplace aucune valeur de jeu**, exactement comme sa sœur.
 * Elle rend calculable ce que la regle exige, en lisant les curseurs reels
 * (`zone.life.regen_seconds`, `zone.mana.regen_seconds`, `zone.energy.*`)
 * plutot qu'une table : deplacer un curseur deplace la mesure, et les deux ne
 * peuvent pas diverger en silence. GAME_ARCHETYPES § 0.2 previent qu'aucun
 * nombre du canon n'est definitif — ce qui est fige ici, ce sont les **regles**
 * et un **rapport**, jamais une minute.
 */
final class DailyAnchor
{
    /** Une journee reelle, en secondes — l'horizon de la seconde ancre. */
    public const DAY_SECONDS = 86400;

    /**
     * La part du budget d'energie qu'une journee consacre au combat.
     *
     * Une journee ne se joue pas en combats seuls : GAME_ZONE_ACTIONS range les
     * gestes en trois registres (tenter / engager / frequenter), et le budget
     * se partage entre eux. Un tiers reproduit le repere du canon (§ 6.4,
     * « les ~16 combats qu'autorise une journee ») sur les curseurs livres :
     * 240 points d'energie par jour, un tiers au combat, une chasse a 5 points.
     *
     * C'est un **partage d'usage**, pas une regle de moteur : rien n'empeche un
     * joueur de tout mettre au combat. Il sert a rapporter un cout a une
     * journee typique, ce qui est le seul objet de cette classe.
     *
     * En fraction entiere plutot qu'en flottant : `240 * (1/3)` vaut
     * 79,999999999999986 en virgule flottante, ce qui rendrait 15 rencontres au
     * lieu de 16. Une ancre qui perd une rencontre sur un arrondi n'ancre rien.
     */
    public const COMBAT_SHARE_NUMERATOR = 1;
    public const COMBAT_SHARE_DENOMINATOR = 3;

    /**
     * L'ecart d'attente tolere entre deux fonctions — **l'ancre de fonction**.
     *
     * GAME_ARCHETYPES § 9 sexies, correction 16 : *a arbre complet et
     * equipement egal, les quatre fonctions doivent enchainer le meme nombre de
     * rencontres de leur palier par jour, et en sortir dans un etat comparable.
     * Ce qui differe, c'est comment on paie* — le soldat en tours, le guerisseur
     * en PM, l'archer en munitions, le pyromancien en fragilite.
     *
     * « Un etat comparable » se mesure dans la monnaie commune : les minutes
     * d'attente d'une journee. Le seuil est un **rapport**, pas une minute —
     * c'est ce que le § 0.2 range parmi les nombres qui survivent a une
     * recalibration. **Du simple au double**, et pas au-dela : au-dela, une
     * fonction joue deux fois moins de contenu qu'une autre, ce qui n'est plus
     * une facon de payer mais une punition.
     *
     * Repere : les cinq builds tenus du § 9 septies.2 s'etalent de 99 a 179
     * minutes, soit x1,81 — dans la borne. Le sixieme (le Pyromancien, x2,87)
     * en sort, et le canon le dit lui-meme *a recalibrer*.
     */
    public const MAX_REST_SPREAD = 2.0;

    /**
     * Ce qu'une journee rend d'energie d'action.
     *
     * La regeneration est paresseuse et continue (ZON-07) : une journee entiere
     * rend `86400 / regen` points a qui depense au fil de l'eau. Le pool ne
     * borne que ce qu'on peut **tenir**, jamais ce qu'une journee **rend**.
     */
    public static function dailyEnergyBudget(int $regenSeconds): int
    {
        if ($regenSeconds <= 0) {
            return 0;
        }

        return intdiv(self::DAY_SECONDS, $regenSeconds);
    }

    /**
     * La part de ce budget qui va au combat.
     */
    public static function combatEnergyPerDay(int $energyBudget): int
    {
        return intdiv(max(0, $energyBudget) * self::COMBAT_SHARE_NUMERATOR, self::COMBAT_SHARE_DENOMINATOR);
    }

    /**
     * Combien de rencontres une journee autorise, au cout d'energie donne.
     *
     * Un cout nul rendrait un nombre infini de rencontres : la journee cesserait
     * d'etre une borne, et la seconde ancre n'aurait plus de sens. On rend 0,
     * qui se lit « la question ne se pose pas ».
     */
    public static function encountersPerDay(int $energyBudget, int $encounterCost): int
    {
        if ($encounterCost <= 0) {
            return 0;
        }

        return intdiv(self::combatEnergyPerDay($energyBudget), $encounterCost);
    }

    /**
     * Le temps d'attente d'une journee, en secondes — la monnaie commune.
     *
     * *Les PV paient les coups recus, les PM paient les gestes faits ; les deux
     * se rechargent en temps reel, et c'est ce temps qui est la vraie monnaie du
     * jeu* (§ 9 septies.2). Le registre melee n'apparait pas ici parce qu'il ne
     * paie **ni l'un ni l'autre** : il paie en tours de combat, immediatement.
     */
    public static function restSeconds(
        int $lifeLost,
        int $manaSpent,
        int $lifeRegenSeconds,
        int $manaRegenSeconds,
    ): int {
        return max(0, $lifeLost) * max(0, $lifeRegenSeconds)
            + max(0, $manaSpent) * max(0, $manaRegenSeconds);
    }

    /**
     * La meme attente, en minutes — l'unite dans laquelle le canon la lit.
     *
     * Arrondi au plus proche, comme la table du § 9 septies.2 : une attente de
     * 98,8 minutes se lit 99, et tronquer la ferait diverger de sa source.
     */
    public static function restMinutes(
        int $lifeLost,
        int $manaSpent,
        int $lifeRegenSeconds,
        int $manaRegenSeconds,
    ): int {
        return (int) round(self::restSeconds($lifeLost, $manaSpent, $lifeRegenSeconds, $manaRegenSeconds) / 60);
    }

    /**
     * Cette ressource se reporte-t-elle d'une rencontre a la suivante ?
     *
     * C'est ce qui rend la seconde ancre necessaire, et ce qui rend le registre
     * melee structurellement different des deux autres (§ 6.4) :
     *
     *  - **Sorts** : oui. Les PM se vident sur la journee et se rechargent en
     *    temps reel (`ManaRegenManager`, ARC-04a).
     *  - **Melee** : non. Le temps de reprise se paie **dans** le combat, jamais
     *    apres — la grille 0/1/2/3/4 d'ARC-04a est intra-rencontre.
     *  - **Distance** : non plus, **depuis ARC-04b**. Le § 6.4 ecrivait « les
     *    munitions contre les gils du jour » ; la correction 17 du § 9 septies
     *    a retire ce cout — le carquois est une piece d'equipement **durable**
     *    qui se vide dans la rencontre et se ramasse apres. Aucun archetype ne
     *    porte un cout recurrent en gils que les autres n'ont pas.
     */
    public static function carriesOverBetweenEncounters(CombatRegister $register): bool
    {
        return $register === CombatRegister::Spell;
    }

    /**
     * L'ecart entre l'attente la plus longue et la plus courte.
     *
     * Une attente nulle rend `INF` : c'est exactement le defaut que le curseur
     * des PM corrige (§ 9 septies.2, « sans le curseur PM, le guerisseur paie
     * 14 minutes »). Une fonction qui ne paie rien n'est pas au bord de la
     * bande, elle est hors de toute bande.
     *
     * @param array<string, int> $restSecondsByBuild
     */
    public static function restSpread(array $restSecondsByBuild): float
    {
        if ([] === $restSecondsByBuild) {
            return 1.0;
        }

        $shortest = min($restSecondsByBuild);
        $longest = max($restSecondsByBuild);

        if ($shortest <= 0) {
            return \INF;
        }

        return $longest / $shortest;
    }

    /**
     * L'ancre de fonction tient-elle sur ce releve ?
     *
     * **C'est le seul invariant qui ne se verifie pas sur un archetype isole**
     * (§ 9 sexies, correction 16) : il exige la table croisee des builds, ce
     * qu'aucun exercice individuel ne pouvait voir. Le simulateur d'ARC-17 la
     * produira sur les vraies donnees ; cette methode est le juge qu'il
     * appellera.
     *
     * @param array<string, int> $restSecondsByBuild
     */
    public static function isWithinFunctionAnchor(array $restSecondsByBuild): bool
    {
        return self::restSpread($restSecondsByBuild) <= self::MAX_REST_SPREAD;
    }
}
