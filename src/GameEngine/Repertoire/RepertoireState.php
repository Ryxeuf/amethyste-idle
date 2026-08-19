<?php

namespace App\GameEngine\Repertoire;

use App\Repository\RepertoireGestureRepository;

/**
 * Ce que le monde sait de son propre souvenir (REP-05).
 *
 * Le Repertoire doit se **voir** pour etre un projet collectif : sans ecran, la
 * campagne que le canon appelle legitime — *« cette maree, lisez du feu »* —
 * n'aurait aucun moyen de savoir si elle marche.
 *
 * **Une seule source.** Le Scriptorium, le repli par le Codex et le Programme
 * du Cercle (FAC-09c) liront tous ceci. Le plan l'exige nommement, et pour la
 * raison qui revient a chaque jalon de ce chantier : *une regle recopiee derive
 * de son original en silence* — deux ecrans qui calculeraient chacun leur
 * dominante finiraient par en montrer deux differentes, et personne ne saurait
 * laquelle croire.
 *
 * ## La non-revelation, et pourquoi elle est dans la forme
 *
 * *« On sait qu'on approche, pas de quoi »* (plan REP-05). Le prochain geste ne
 * doit pas fuir — sinon le suspense de l'horizon de l'an se resout d'un coup, et
 * la campagne collective devient une simple attente.
 *
 * Cette classe **n'a aucune methode qui rende le prochain geste**, alors que
 * `RepertoireUnlocker::nextGesture()` sait le calculer. Ce n'est pas un oubli :
 * c'est le meme geste qu'ARC-16a sur les accointances — *il n'y a pas de champ
 * ou l'ecrire*, donc aucun ecran ne peut le montrer par megarde.
 *
 * Ce qu'on rend, en revanche, est **exact** : le total lu, le seuil suivant, et
 * les dominantes. Un joueur peut en deduire la *famille* de ce qui vient — c'est
 * precisement ce qu'on veut, puisque c'est ce que sa campagne a produit.
 */
class RepertoireState
{
    public function __construct(
        private readonly RepertoireDominance $dominance,
        private readonly RepertoireUnlocker $unlocker,
        private readonly RepertoireCatalog $catalog,
        private readonly RepertoireGestureRepository $gestures,
    ) {
    }

    /**
     * L'etat complet, tel que les ecrans le lisent.
     *
     * @return array{
     *     readings: int,
     *     threshold: int,
     *     progress: int,
     *     rank: int,
     *     dominant_element: ?string,
     *     dominant_provenance: ?string,
     *     dominant_place: ?string,
     *     recovered: list<array{key: string, rank: int, revelation: string}>,
     *     remaining: int
     * }
     */
    public function snapshot(): array
    {
        $recoveredRows = $this->gestures->findBy([], ['discoveryRank' => 'ASC']);
        $pool = $this->catalog->foundGestures();

        $recovered = [];
        foreach ($recoveredRows as $row) {
            $gesture = $pool[$row->getGestureKey()] ?? null;

            $recovered[] = [
                'key' => $row->getGestureKey(),
                'rank' => $row->getDiscoveryRank(),
                // Un geste dont l'entree a disparu du bassin garde sa ligne :
                // le savoir n'est jamais borne, et une donnee retiree du fichier
                // ne doit pas effacer l'histoire du monde. On l'affiche sans sa
                // phrase plutot que de le faire disparaitre.
                'revelation' => $gesture['revelation'] ?? '',
            ];
        }

        $readings = $this->unlocker->totalReadings();
        $threshold = $this->unlocker->thresholdFor(\count($recovered) + 1);

        return [
            'readings' => $readings,
            'threshold' => $threshold,
            // Borne a 100 : au-dela du seuil, le geste est **du** et attend
            // seulement que le calendrier passe. Afficher 140 % ferait croire a
            // un retard, quand c'est une avance.
            'progress' => $threshold > 0 ? min(100, (int) floor($readings * 100 / $threshold)) : 0,
            'rank' => \count($recovered) + 1,
            'dominant_element' => $this->dominance->element(),
            'dominant_provenance' => $this->dominance->provenance(),
            'dominant_place' => $this->dominance->place(),
            'recovered' => $recovered,
            // Combien de gestes le bassin contient encore. Un nombre, jamais
            // une liste : *on sait qu'il en reste, pas lesquels*.
            'remaining' => max(0, \count($pool) - \count($recovered)),
        ];
    }
}
