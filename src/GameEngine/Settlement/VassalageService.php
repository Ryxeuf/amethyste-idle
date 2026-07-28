<?php

namespace App\GameEngine\Settlement;

use App\Entity\App\Settlement;
use App\Enum\SettlementRank;
use App\Repository\SettlementRepository;
use App\Repository\ZoneConnectionRepository;

/**
 * Une grande ville boit la croissance de ses voisines (FOY-09).
 *
 * Un foyer de rang N plafonne ses **voisins directs du graphe de zones** au rang
 * N-1. C'est ce qui empeche une region entiere de devenir une grappe de
 * metropoles, et ce qui donne au choix du *lieu* une consequence : bâtir a
 * l'ombre d'une capitale coute plus cher que bâtir au large.
 *
 * **Seule la croissance est plafonnee.** Le vassal garde son marche, son type et
 * son identite : un rang deja tenu n'est jamais retire. C'est la meme regle que
 * la decision A du pilier (FOY-05) — on ne reprend pas ce qui a ete acquis, on
 * borne ce qui reste a acquerir. Un joueur ne doit jamais decouvrir qu'une ville
 * a **recule** parce qu'une autre a grandi.
 *
 * **Pas de cascade.** Le plafond se lit sur les voisins **directs** et sur leur
 * rang **tenu**, une fois. Une metropole ne contraint donc pas les voisins de
 * ses voisins : ceux-la sont bornes par leur propre voisinage, ce qui produit un
 * degrade naturel plutot qu'une onde qui traverse la carte.
 *
 * **La liberation est automatique.** Le plafond est **derive**, jamais stocke :
 * le jour ou la capitale tombe, ses vassales peuvent monter au tick suivant sans
 * qu'aucun champ n'ait a etre remis a zero.
 */
class VassalageService
{
    public function __construct(
        private readonly ZoneConnectionRepository $connectionRepository,
        private readonly SettlementRepository $settlementRepository,
    ) {
    }

    /**
     * Le voisin le plus haut, s'il domine ce foyer.
     *
     * `null` quand aucun voisin ne le depasse — le cas ordinaire, et celui de
     * toute zone de bordure.
     */
    public function overlordOf(Settlement $settlement): ?Settlement
    {
        $highest = null;

        foreach ($this->connectionRepository->findEnabledFrom($settlement->getZone()) as $connection) {
            $neighbour = $this->settlementRepository->findOneByZone($connection->getToZone());
            if ($neighbour === null) {
                continue;
            }

            if ($highest === null || $neighbour->getRank()->level() > $highest->getRank()->level()) {
                $highest = $neighbour;
            }
        }

        // Un voisin de meme rang ne domine pas : il faut le **depasser** pour
        // brider. Sans cela, deux bourgs voisins se plafonneraient mutuellement
        // au Hameau et aucun des deux ne pourrait plus grandir — un blocage
        // reciproque que rien ne viendrait denouer.
        if ($highest === null || $highest->getRank()->level() <= $settlement->getRank()->level()) {
            return null;
        }

        return $highest;
    }

    /**
     * Rang maximal que ce foyer peut atteindre, ou `null` s'il est libre.
     */
    public function capFor(Settlement $settlement): ?SettlementRank
    {
        $overlord = $this->overlordOf($settlement);
        if ($overlord === null) {
            return null;
        }

        return $overlord->getRank()->previous();
    }

    /**
     * Borne une montee par le plafond de vassalite.
     *
     * Ne descend **jamais** en dessous du rang deja tenu : le vassal garde ce
     * qu'il a. Le plafond borne l'avenir, pas le passe.
     */
    public function clamp(Settlement $settlement, SettlementRank $natural): SettlementRank
    {
        $cap = $this->capFor($settlement);
        if ($cap === null || $natural->level() <= $cap->level()) {
            return $natural;
        }

        return $cap->level() < $settlement->getRank()->level() ? $settlement->getRank() : $cap;
    }
}
