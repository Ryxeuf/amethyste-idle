<?php

namespace App\GameEngine\Progression;

use App\GameEngine\Balance\DailyAnchor;

/**
 * Ce qu'une rencontre rapporte a un arbre (ARC-06a).
 *
 * GAME_ARCHETYPES § 6.2 :
 *
 * > **Un geste reussi rapporte des points de domaine selon le palier de
 * > l'adversaire** : T1 → 0,25 · T2 → 0,5 · T3 → 1 · T4 → 2.
 *
 * **La propriete recherchee, et c'est elle qui justifie la regle : on ne monte
 * pas un arbre en tapant des rats.** La progression pousse vers le contenu de
 * son palier, jamais vers le farm du contenu trivial — et elle se recalibre en
 * un seul endroit, sans toucher un seul nœud.
 *
 * **En quarts de point**, parce que la table descend a 0,25 et qu'un compteur
 * de progression qui perd ses restes est un compteur qui ment : un joueur de
 * palier 1 gagnerait zero point par rencontre, arrondi apres arrondi. Le quart
 * est la plus petite unite que la table nomme ; tout le reste s'en deduit.
 *
 * Cette classe **ne distribue rien** : elle dit ce qu'une rencontre vaut et ce
 * que le calendrier du § 6.2 exige. Brancher la distribution sur la mort d'un
 * monstre est ARC-06b — le combat ne rapporte aujourd'hui **aucun point de
 * domaine** (seule la materia gagne de l'experience, `MateriaXpGranter`).
 */
final class DomainPointYield
{
    /** Le quart de point : la plus petite unite que la table du canon nomme. */
    public const QUARTERS_PER_POINT = 4;

    /**
     * La table du § 6.2, en quarts de point : 0,25 · 0,5 · 1 · 2.
     *
     * T0 n'y figure pas : c'est le palier des mannequins d'entrainement
     * (GAME_BESTIARY), et un mannequin ne fait pas progresser un arbre.
     *
     * @var array<int, int>
     */
    public const QUARTERS_BY_TIER = [1 => 1, 2 => 2, 3 => 4, 4 => 8];

    /**
     * Ce que vaut une rencontre de ce palier, en quarts de point.
     */
    public static function quartersFor(int $tier): int
    {
        return self::QUARTERS_BY_TIER[$tier] ?? 0;
    }

    /**
     * Ce qu'une journee de chasse a ce palier rapporte, en quarts de point.
     *
     * Le nombre de rencontres se **derive** du budget d'energie reel
     * (`DailyAnchor`, ARC-05b) plutot que de vivre ici : deplacer le cout d'une
     * chasse ou la regeneration de l'energie deplace le calendrier, et les deux
     * ne peuvent pas diverger en silence.
     */
    public static function quartersPerDay(int $encountersPerDay, int $tier): int
    {
        return max(0, $encountersPerDay) * self::quartersFor($tier);
    }

    /**
     * En combien de jours un arbre complet tombe, a ce palier de chasse.
     *
     * C'est la verification que le § 6.2 demande : *pour que 390 points tombent
     * au mois 3*. Le canon annonce ~7 semaines de pratique soutenue sur de la
     * faune T2 — le mois 3 etant la cible d'un joueur **reel**, qui partage son
     * budget entre deux ou trois domaines et ne combat pas tous les jours.
     */
    public static function daysToCompleteTree(int $encountersPerDay, int $tier): int
    {
        $perDay = self::quartersPerDay($encountersPerDay, $tier);
        if ($perDay <= 0) {
            return 0;
        }

        return (int) ceil(SkillCostScale::COMPLETE_TREE * self::QUARTERS_PER_POINT / $perDay);
    }

    /**
     * Le calendrier vise, sur les curseurs livres : chasse de palier 2.
     *
     * Le raccourci qui evite de recopier trois nombres a chaque appel — la
     * journee vient de `DailyAnchor`, jamais d'une constante locale.
     */
    public static function daysToCompleteTreeAtTier(int $tier, int $encounterCost, int $energyRegenSeconds): int
    {
        $budget = DailyAnchor::dailyEnergyBudget($energyRegenSeconds);

        return self::daysToCompleteTree(DailyAnchor::encountersPerDay($budget, $encounterCost), $tier);
    }
}
