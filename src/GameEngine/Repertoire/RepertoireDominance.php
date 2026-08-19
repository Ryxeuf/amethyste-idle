<?php

namespace App\GameEngine\Repertoire;

use App\Repository\RepertoireReadingRepository;

/**
 * Ce dont ce monde se souvient le plus (REP-03).
 *
 * GAME_WORLD § 12.3 b : *« l'agregat des lectures a des dominantes (element,
 * provenance, lieu de lecture), et quand un seuil tombe, le geste retrouve est
 * tire du **bassin qui correspond a la dominante** »*.
 *
 * **Les trois axes ne sont pas de meme rang**, et l'ordre est celui du canon :
 * l'element decide, la provenance departage, le lieu departage ce que la
 * provenance n'a pas tranche. Les traiter a egalite ferait qu'un serveur qui lit
 * du feu partout et de l'eau aux Mines pourrait retrouver un geste d'eau parce
 * que « Mines » l'emporte — et *ce qu'il a lu ne serait plus ce dont il se
 * souvient*.
 *
 * **Toutes les semaines comptent.** La dominante se lit sur l'histoire entiere
 * du monde et non sur la semaine en cours : un souvenir qui n'irait pas plus
 * loin que sept jours ne serait pas une memoire, ce serait une humeur.
 */
class RepertoireDominance
{
    public function __construct(
        private readonly RepertoireReadingRepository $readings,
    ) {
    }

    /**
     * L'element le plus lu, ou `null` si le monde n'a encore rien lu.
     */
    public function element(): ?string
    {
        return $this->top($this->readings->tallyByElement());
    }

    public function provenance(): ?string
    {
        return $this->top($this->readings->tallyByProvenance());
    }

    public function place(): ?string
    {
        return $this->top($this->readings->tallyByReadingZone());
    }

    /**
     * Le sommet d'un decompte, departage par la cle.
     *
     * **Le departage est alphabetique, et c'est un choix.** Une egalite doit se
     * trancher de la meme facon a chaque appel : deux mondes rigoureusement
     * identiques doivent retrouver le meme geste, sinon le Repertoire cesse
     * d'etre une lecture de ce qui a ete vecu.
     *
     * @param array<string, int> $tally
     */
    private function top(array $tally): ?string
    {
        if ($tally === []) {
            return null;
        }

        ksort($tally);
        $best = null;
        $bestCount = -1;
        foreach ($tally as $key => $count) {
            if ($count > $bestCount) {
                $best = $key;
                $bestCount = $count;
            }
        }

        return $best;
    }
}
