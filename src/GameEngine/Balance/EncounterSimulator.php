<?php

namespace App\GameEngine\Balance;

use App\Enum\MonsterRank;
use App\GameEngine\Bestiary\MonsterStatTemplate;

/**
 * Le simulateur de rencontre : on joue les tours plutot que de relire une table
 * (ARC-17c-b).
 *
 * GAME_ARCHETYPES § 0.2 previent que la recalibration passe par la mesure et
 * *jamais par une relecture a la main* — quatre exercices manuels ont produit
 * vingt corrections, et a cette echelle ce n'etait deja plus tenable.
 * `BalanceReportCommand` compte et detecte des anomalies ; il ne joue pas.
 * Cette classe est la sœur **dynamique** : elle fait s'affronter une fiche de
 * personnage et une case du bestiaire, et rend ce que la rencontre a coute.
 *
 * ## Deterministe, et sans des du tout
 *
 * Le plan demande un simulateur **deterministe** — *une CI qui clignote ne sert
 * a rien, et un equilibrage qu'on ne peut pas reproduire n'est pas un
 * equilibrage*. Une graine fixee y suffirait ; on va plus loin, et il faut dire
 * pourquoi : **on ne tire rien, on joue l'esperance**. Un jet de des fixe par une
 * graine reste un tirage, c'est-a-dire qu'un seuil de CI finirait par se decider
 * sur un critique qui tombe ou ne tombe pas. L'esperance mesure la meme regle
 * sans lui laisser cette latitude — et c'est bien la regle qu'on equilibre, pas
 * la chance d'un soir.
 *
 * *Ce que l'esperance ne peut pas voir*, en revanche : la variance. Un build qui
 * gagne en moyenne mais meurt une fois sur cinq se lit ici comme un build qui
 * gagne. Le § 9 octies mesure la mortalite en part de barre de vie et non en
 * frequence, si bien que le seuil qu'ARC-17c-c aura a tenir se lit dans cette
 * unite ; le jour ou un seuil demandera une frequence, il demandera des tirages.
 *
 * ## Ce que ce simulateur ne modelise pas, et qui n'est pas un oubli
 *
 *  - **La mitigation d'armure n'existe pas dans le moteur.** GAME_ITEMS § 2.2
 *    la mesure (30 % minimum, 50 % maximum, cible ~40 %) et ARC-19 la reclame
 *    comme prerequis ; aucune ligne du code ne la lit aujourd'hui. La simuler
 *    reviendrait a mesurer un jeu qui n'est pas celui qui tourne.
 *  - **Les statuts, les depots et les marques** ne se jouent pas. `grip`,
 *    `ward` et les gestes d'entrave sont dans la fiche, mais ce qu'ils font
 *    dure et se cumule ; les faire entrer demanderait le moteur de statuts
 *    entier. La consequence est nommee plutot que tue : *le controle est
 *    sous-estime par cet instrument*, et c'est a lire dans toute table qu'il
 *    produit.
 *  - **L'ordre du tour est fixe** : le joueur agit, l'adversaire riposte.
 *    `tempo` deplace l'initiative dans le jeu, mais un simulateur qui
 *    alternerait selon un taux ferait dependre la duree d'un demi-tour, ce qui
 *    n'est pas ce qu'on mesure.
 */
final class EncounterSimulator
{
    /**
     * La borne au-dela de laquelle une rencontre cesse d'en etre une.
     *
     * Trois fois le haut de la bande la plus longue (20 tours pour un boss). Ce
     * n'est pas une regle de jeu mais un garde-fou d'instrument : un personnage
     * qui rend moins de degats que son adversaire ne regenere boucle a l'infini,
     * et un simulateur qui tournerait sans fin ne rendrait aucune mesure.
     */
    public const MAX_TURNS = 60;

    public function simulate(ReferenceCharacter $character, int $tier, MonsterRank $rank): EncounterOutcome
    {
        $monsterLife = (float) MonsterStatTemplate::lifeFor($tier, $rank);
        $strike = (float) MonsterStatTemplate::attackFor($tier, $rank);
        $monsterHit = MonsterStatTemplate::hitFor($tier, $rank) / 100.0;

        $life = (float) $character->maxLife;
        $resource = (float) $character->maxResource;
        $spent = 0.0;
        $turns = 0;

        // Ce que le personnage encaisse par riposte, une fois pour toutes : la
        // precision de l'adversaire, l'esquive, puis `guard` — dans l'ordre ou
        // la formule les applique (`DamageCalculator`), parce que `guard`
        // s'applique apres et non avant l'evitement.
        $incoming = $strike
            * $monsterHit
            * (1.0 - max(0.0, min(100.0, $character->dodgeRate)) / 100.0)
            * max(0.0, $character->guardMultiplier);

        $recovery = $character->recoveryPerTurnPoints();

        while ($monsterLife > 0.0 && $life > 0.0 && $turns < self::MAX_TURNS) {
            ++$turns;

            // **Un registre sans ressource joue toujours son geste.** La melee
            // paie en tours et le tir dans son carquois : rien ne les empeche de
            // frapper, et les faire retomber sur l'attaque de base reviendrait a
            // punir d'un cout qu'ils n'ont pas. Seul un pool qui existe peut se
            // vider.
            $paying = $character->spendsAResource();

            if (!$paying || $resource >= $character->gestureCost) {
                if ($paying) {
                    $resource -= $character->gestureCost;
                    $spent += $character->gestureCost;
                }
                $monsterLife -= $character->expectedDamagePerTurn();
            } else {
                $monsterLife -= $character->expectedFallbackDamagePerTurn();
            }

            if ($monsterLife <= 0.0) {
                break;
            }

            $life -= $incoming;

            if ($life <= 0.0) {
                break;
            }

            // La fin de tour, dans l'ordre du moteur : on regenere ce que les
            // leviers rendent, sans jamais depasser le plafond.
            $life = min((float) $character->maxLife, $life + $recovery);
            $resource = min((float) $character->maxResource, $resource + $character->resourcePerTurn);
        }

        $victory = $monsterLife <= 0.0;
        $lifeRemaining = max(0, (int) round($life));

        return new EncounterOutcome(
            buildLabel: $character->label,
            tier: $tier,
            rank: $rank,
            turns: $turns,
            victory: $victory,
            // Une rencontre est resolue quand quelqu'un tombe. Atteindre la
            // borne sans vainqueur n'est pas une defaite : c'est une mesure
            // absente, et la confondre avec une defaite ferait croire a un
            // equilibrage la ou il n'y a qu'un plafond d'instrument.
            resolved: $victory || $life <= 0.0,
            lifeLost: max(0, $character->maxLife - $lifeRemaining),
            lifeRemaining: $lifeRemaining,
            maxLife: $character->maxLife,
            resourceSpent: (int) round($spent),
        );
    }
}
