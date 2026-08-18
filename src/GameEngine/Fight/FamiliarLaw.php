<?php

namespace App\GameEngine\Fight;

use App\GameEngine\Balance\DepositValue;

/**
 * La creature invoquee, et ce qu'elle vaut vraiment (ARC-18h).
 *
 * GAME_ARCHETYPES § 13.1, forme n° 1, telle que l'**arbitrage du § 13.3** l'a
 * tranchee : ***le familier est un depot offensif, pas un acteur***.
 *
 * Le test qui a rendu l'arbitrage tient en une phrase — *retirez le ciblage, que
 * reste-t-il ?* Une chose qui inflige des degats a chaque tour de la rencontre,
 * pendant une duree, posee en un tour : **exactement un depot** (§ 7 bis). Le
 * critere d'admission des formes exigeant qu'une forme occupe une place
 * qu'aucune autre n'occupe, le familier est traite comme tel. On garde ce qui
 * comptait — *il agit sur les tours ou son invocateur est absent* — et la
 * fiction entiere ; on perd le ciblage, on economise un acteur, une IA et une
 * cible supplementaire dans la boucle de tour.
 *
 * ## Ce qu'il vaut, et pourquoi le chiffre a du etre corrige
 *
 * La premiere calibration du canon (40 % du geste sur six tours, soit **x2,4 le
 * tour investi**) etait **cassee en groupe**, et pas ou on l'attendait : le
 * danger n'etait pas la fragilite d'un invocateur en tissu — le familier ne
 * mitige rien —, mais que ***le familier agisse sur les tours de la RENCONTRE
 * quand son invocateur n'a que les SIENS***. En groupe, le taux de change lui
 * est favorable **4 pour 1** : mesure, l'invocateur contribuait **+87 %** avec
 * quatre invocations, et *plus il invoquait, plus il gagnait*.
 *
 * La correction etait deja ecrite et n'avait pas ete appliquee — *la duree etale
 * la valeur, elle ne l'augmente pas* (correction 5). Un familier rend donc une
 * **valeur totale fixee a un tour d'attaque de son invocateur**, etalee sur sa
 * duree, ce qui le met :
 *
 * - **a l'equilibre quand le joueur est present**, solo comme en groupe ;
 * - **a +56 % sur six tours d'absence**, ou il n'y aurait eu que des attaques
 *   de base.
 *
 * > ***Le familier ne vaut rien quand vous jouez. Il vaut tout quand vous ne
 * > jouez pas.*** C'est exactement ce qu'il etait cense reparer, et le geste
 * > devient une decision de joueur : *je pose mon familier avant de fermer
 * > l'onglet*.
 *
 * ## Sa valeur se derive de l'attaque de son invocateur
 *
 * Elle n'est **jamais ecrite sur la fiche** du geste, et c'est ce qui distingue
 * un familier d'un simple poison : un chiffre en base vaudrait la meme chose
 * pour un debutant et pour un personnage fini, donc il serait dominant au jour
 * 1 et decoratif au mois 3. *La borne est un rapport, pas un nombre.*
 */
final class FamiliarLaw
{
    /**
     * Un seul familier a la fois.
     *
     * Le garde-fou n° 2 du § 13.3, et celui qui ferme la mesure du +87 % : sans
     * lui, la correction de valeur ne suffirait pas, puisqu'on empilerait
     * quatre depots a un tour d'attaque chacun.
     */
    public const MAX_ACTIVE = 1;

    /**
     * Ce qu'un familier rend **en tout**, sur toute sa duree.
     *
     * Un tour d'attaque de son invocateur. Pas une fraction, pas un multiple :
     * c'est le chiffre qui le met a l'equilibre quand le joueur est la, ce qui
     * est precisement la condition pour qu'il ne vaille que quand le joueur
     * n'est plus la.
     */
    public static function totalValue(int $oneTurnOfAttack): int
    {
        return max(0, $oneTurnOfAttack);
    }

    /**
     * Ce qu'il rend par tour, une fois etale.
     *
     * L'etalement passe par `DepositLaw`, jamais par une seconde formule : *une
     * regle recopiee derive de son original en silence.*
     */
    public static function perTurn(int $oneTurnOfAttack, int $duration): int
    {
        return DepositLaw::spreadPerTurn(
            self::totalValue($oneTurnOfAttack),
            DepositLaw::durationFor($duration),
        );
    }

    /**
     * La duree opposable a une invocation.
     *
     * La meme que celle d'un depot : un familier d'un seul tour n'a rien
     * depose, il a **reagi** — et reagir est precisement ce qu'un joueur absent
     * ne peut pas faire.
     */
    public static function durationFor(int $declared): int
    {
        return DepositLaw::durationFor($declared);
    }

    /**
     * Cette invocation respecte-t-elle la borne des depots offensifs ?
     *
     * Rendue interrogeable plutot que confiee a la relecture, parce que c'est
     * exactement le garde-fou qui avait ete **ecrit puis pas applique** : la
     * correction 5 existait quand la premiere calibration a ete posee.
     */
    public static function isWithinBound(int $perTurn, int $duration, int $oneTurnOfAttack): bool
    {
        return DepositValue::isWithinOffensiveBound($perTurn * max(0, $duration), $oneTurnOfAttack);
    }

    /**
     * Ce qu'un invocateur ajoute reellement sur une rencontre.
     *
     * Rendu pour que l'affirmation du canon soit **calculable** plutot que
     * decrite : presente, l'invocation remplace un tour d'attaque par un tour
     * d'attaque ; absent, elle remplace une attaque de base — bien plus faible
     * — par la meme valeur.
     *
     * @param int $turnsAway les tours ou l'invocateur n'agit pas lui-meme
     */
    public static function contributionOver(int $oneTurnOfAttack, int $fallbackPerTurn, int $turnsAway): int
    {
        if ($turnsAway <= 0) {
            return 0;
        }

        return max(0, self::totalValue($oneTurnOfAttack) - $fallbackPerTurn * $turnsAway);
    }
}
