<?php

namespace App\GameEngine\Progression;

/**
 * L'echelle de cout d'un nœud d'arbre (ARC-06a).
 *
 * GAME_ARCHETYPES § 6.2 : *l'echelle 0 / 10 / 25 / 50 / 100 n'est pas choisie
 * pour sa beaute, elle est derivee d'un calendrier, qui est la vraie decision
 * de design.*
 *
 * | Palier | Cout | Quand il doit tomber |
 * |---|---:|---|
 * | Entree | 0 | jour 1, avant la premiere materia trouvee |
 * | Palier 1 | 10 | fin de semaine 1 |
 * | Palier 2 | 25 | semaines 3-4 — *le passage critique* |
 * | Palier 3 | 50 | semaines 6-8 |
 * | Capstone | 100 | mois 3 |
 * | *Hybride* | *150* | *l'accord dormant de DOM-07, hors calendrier* |
 *
 * **Un cout exprime un palier, jamais un dosage.** C'est tout l'objet du
 * jalon : les arbres livres portaient 23 valeurs distinctes (5, 15, 20, 30, 35,
 * 45, 55, 70, 85, 90, 110, 120, 200…), dont cinq seulement sur l'echelle. Deux
 * nœuds a 30 et 35 points ne disent pas deux paliers differents — ils disent
 * qu'on a dose a la main, et un dosage ne se calibre pas : il se re-dose.
 *
 * L'echelle est **fermee**, sur le modele de `CombatLever` (ARC-03a) : un cout
 * hors barreau est refus a la lecture, pas corrige en silence.
 */
final class SkillCostScale
{
    public const ENTRY = 0;
    public const TIER_1 = 10;
    public const TIER_2 = 25;
    public const TIER_3 = 50;
    public const CAPSTONE = 100;

    /**
     * Le nœud dormant de DOM-07 — hors budget et hors calendrier tant que la
     * fusion n'ouvre pas. Il est sur l'echelle sans etre un palier : personne
     * ne l'apprend, donc il ne compte pas dans le total d'un arbre.
     */
    public const DORMANT = 150;

    /** @var list<int> */
    public const RUNGS = [self::ENTRY, self::TIER_1, self::TIER_2, self::TIER_3, self::CAPSTONE, self::DORMANT];

    /**
     * Ce qu'un arbre complet coute, dormant exclu : `4x10 + 4x25 + 3x50 + 100`.
     *
     * Le gabarit du § 6.1 ecrit 18 nœuds et en laisse apprendre 15 — la fourche
     * du palier 3 offre six nœuds et n'en laisse prendre que trois (§ 6.1 bis).
     */
    public const COMPLETE_TREE = 390;

    /**
     * Ce cout est-il sur un barreau ?
     */
    public static function isOnScale(int $cost): bool
    {
        return \in_array($cost, self::RUNGS, true);
    }

    /**
     * Les barreaux qu'un personnage paie vraiment.
     *
     * Le dormant n'en est pas : il n'entre dans aucun total d'arbre.
     *
     * @return list<int>
     */
    public static function learnableRungs(): array
    {
        return [self::ENTRY, self::TIER_1, self::TIER_2, self::TIER_3, self::CAPSTONE];
    }

    /**
     * Le barreau au-dessous — la remise d'une accointance `access_discount`
     * (ARC-16b) : *« un palier de moins »*, jamais un nombre en donnees.
     *
     * Deux prudences, et les deux sont des refus de deviner : un cout **hors
     * echelle** est rendu tel quel (le remiser inventerait un barreau que
     * personne n'a decide), et l'entree reste l'entree — au-dessous de gratuit,
     * il n'y a rien.
     */
    public static function rungBelow(int $cost): int
    {
        $rungs = self::learnableRungs();
        $index = array_search($cost, $rungs, true);

        if ($index === false || $index === 0) {
            return $cost;
        }

        return $rungs[$index - 1];
    }
}
