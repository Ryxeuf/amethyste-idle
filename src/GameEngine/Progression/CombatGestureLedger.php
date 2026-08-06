<?php

namespace App\GameEngine\Progression;

use App\Entity\App\Fight;
use App\Entity\App\Player;
use App\Entity\Game\Domain;

/**
 * Ce que chaque joueur a joue dans cette rencontre (ARC-06b).
 *
 * Le gain se distribue a la mort du monstre (`MobDeadEvent`, comme
 * `MateriaXpGranter`), mais la **case** qu'il credite est decidee au moment du
 * geste, pas au moment de la mort : l'evenement ne porte que le monstre. Ce
 * registre fait le pont, et il vit dans les metadonnees du combat — le meme
 * endroit que la difficulte de donjon ou les cooldowns, c'est-a-dire ce qui
 * n'a de sens que le temps d'une rencontre.
 *
 * **Une entree par joueur, ecrasee a chaque geste**, et c'est la forme qui
 * porte la regle : un joueur credite **un** arbre par rencontre, jamais
 * plusieurs. C'est ce qui interdit la multiplication que la decision refuse —
 * enchainer six gestes de six cases ne rapporte pas six fois, il rapporte une
 * fois, dans la case du dernier. En coop, chacun a la sienne : le tour d'un
 * joueur n'ecrit jamais la ligne d'un autre.
 */
class CombatGestureLedger
{
    public const METADATA_KEY = 'arc06b_gesture_case';

    /**
     * Retenir la case du geste que ce joueur vient de jouer.
     *
     * Un geste sans case (element `None`, mains nues) **efface** la ligne
     * plutot que de laisser la precedente : sans cela, un joueur pourrait
     * ouvrir sur un geste de feu puis finir la rencontre a mains nues en
     * gardant le credit du feu.
     */
    public function record(Fight $fight, Player $player, ?Domain $domain): void
    {
        $cases = $fight->getMetadataValue(self::METADATA_KEY, []) ?? [];
        if (!\is_array($cases)) {
            $cases = [];
        }

        $key = (string) $player->getId();
        if ($domain === null) {
            unset($cases[$key]);
        } else {
            $cases[$key] = $domain->getId();
        }

        $fight->setMetadataValue(self::METADATA_KEY, $cases);
    }

    /**
     * L'arbre que ce joueur a fait travailler dans cette rencontre.
     */
    public function caseFor(Fight $fight, Player $player): ?int
    {
        $cases = $fight->getMetadataValue(self::METADATA_KEY, []) ?? [];
        if (!\is_array($cases)) {
            return null;
        }

        $domainId = $cases[(string) $player->getId()] ?? null;

        return \is_int($domainId) ? $domainId : null;
    }
}
