<?php

namespace App\GameEngine\Fight;

use App\Entity\App\Fight;
use App\Entity\App\Player;

/**
 * Les gestes qui n'ont pas encore frappe (ARC-18f).
 *
 * La file vit dans les **metadonnees du combat**, comme le compteur de charge
 * (ARC-18e) et le registre des gestes (ARC-06b) — *ce qui n'a de sens que le
 * temps d'une rencontre n'a pas besoin d'une colonne*. C'est ce rangement, et
 * lui seul, qui garantit qu'un differe pose puis fui n'explose pas dans la
 * rencontre suivante : il n'existe nulle part ou survivre.
 *
 * **Chaque entree porte son echeance, pas son compte a rebours.** Un compte a
 * rebours devrait etre decremente a chaque tour — donc par quelqu'un, donc par
 * un appel qu'on peut oublier ; une echeance se compare au tour courant et ne
 * demande rien a personne. *Un etat qui se lit ne derive pas ; un etat qu'il
 * faut entretenir, si.*
 */
class DeferredQueue
{
    /**
     * Poser un geste qui frappera plus tard.
     *
     * Le delai et la valeur passent par la loi : une file qui accepterait ce
     * qu'on lui donne laisserait entrer des differes a zero tour (des gestes
     * ordinaires deguises) ou a trente (des gestes que personne ne relie plus
     * a leur cause).
     */
    public function defer(Fight $fight, Player $player, int $value, int $delay): void
    {
        if ($value <= 0) {
            return;
        }

        $queue = $this->all($fight);
        $queue[] = [
            'player' => (int) $player->getId(),
            'at' => DeferredLaw::resolvesAt($fight->getStep(), $delay),
            'value' => DeferredLaw::payload($value, $delay),
        ];

        $fight->setMetadataValue(DeferredLaw::METADATA_KEY, $queue);
    }

    /**
     * Retirer et rendre les differes dus a ce tour.
     *
     * ***Retirer et rendre en un seul geste*** : les separer laisserait un
     * appelant lire la file, agir, et oublier de la vider — c'est-a-dire une
     * bombe qui explose a chaque tour jusqu'a la fin du combat. La seule facon
     * de ne pas ecrire ce defaut est de rendre impossible de lire sans
     * consommer.
     *
     * @return list<array{player: int, at: int, value: int}>
     */
    public function collectDue(Fight $fight): array
    {
        $due = [];
        $pending = [];

        foreach ($this->all($fight) as $entry) {
            if (DeferredLaw::isDue($entry['at'], $fight->getStep())) {
                $due[] = $entry;
            } else {
                $pending[] = $entry;
            }
        }

        if ($due !== []) {
            $fight->setMetadataValue(DeferredLaw::METADATA_KEY, $pending);
        }

        return $due;
    }

    /**
     * Ce qui attend encore, tous joueurs confondus.
     *
     * @return list<array{player: int, at: int, value: int}>
     */
    public function all(Fight $fight): array
    {
        $queue = $fight->getMetadataValue(DeferredLaw::METADATA_KEY, []) ?? [];
        if (!\is_array($queue)) {
            return [];
        }

        $clean = [];
        foreach ($queue as $entry) {
            if (!\is_array($entry) || !isset($entry['player'], $entry['at'], $entry['value'])) {
                continue;
            }

            $clean[] = [
                'player' => (int) $entry['player'],
                'at' => (int) $entry['at'],
                'value' => (int) $entry['value'],
            ];
        }

        return $clean;
    }
}
