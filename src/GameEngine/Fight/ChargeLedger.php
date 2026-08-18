<?php

namespace App\GameEngine\Fight;

use App\Entity\App\Fight;
use App\Entity\App\Player;
use App\Entity\Game\Spell;

/**
 * Ce que chaque joueur a accumule dans cette rencontre (ARC-18e).
 *
 * Le compteur vit dans les **metadonnees du combat**, le meme endroit que le
 * registre des gestes d'ARC-06b et pour la meme raison : *ce qui n'a de sens
 * que le temps d'une rencontre n'a pas besoin d'une colonne*. C'est aussi ce
 * qui tient le garde-fou du canon sans qu'on ait rien a effacer — ***la charge
 * meurt avec la rencontre*** parce qu'elle n'existe nulle part ailleurs.
 *
 * Une entree par joueur : en coop, le tour d'un joueur n'ecrit jamais la ligne
 * d'un autre.
 */
class ChargeLedger
{
    /**
     * Ce que ce joueur a accumule.
     */
    public function of(Fight $fight, Player $player): int
    {
        $charges = $fight->getMetadataValue(ChargeLaw::METADATA_KEY, []) ?? [];
        if (!\is_array($charges)) {
            return 0;
        }

        $value = $charges[(string) $player->getId()] ?? 0;

        return \is_int($value) ? max(0, $value) : 0;
    }

    /**
     * Ce geste peut-il etre joue par ce joueur ?
     *
     * Rendu ici plutot que laisse a l'appelant parce que **la question se pose
     * avant le geste** : un geste qu'on ne peut pas payer doit etre refuse, pas
     * joue en moins fort (`ChargeLaw::canSpend`).
     */
    public function affords(Fight $fight, Player $player, Spell $spell): bool
    {
        return ChargeLaw::canSpend($this->of($fight, $player), $spell->getChargeCost());
    }

    /**
     * Appliquer ce que le geste fait au compteur, et rendre son nouvel etat.
     *
     * Un seul point d'ecriture pour les deux sens — generer et depenser —,
     * parce que la loi refuse qu'un geste fasse les deux : les separer en deux
     * methodes laisserait croire qu'on peut les appeler ensemble.
     */
    public function apply(Fight $fight, Player $player, Spell $spell): int
    {
        $current = $this->of($fight, $player);

        $next = $spell->getChargeGain() > 0
            ? ChargeLaw::after($current, $spell->getChargeGain())
            : ChargeLaw::spend($current, $spell->getChargeCost());

        $this->write($fight, $player, $next);

        return $next;
    }

    private function write(Fight $fight, Player $player, int $value): void
    {
        $charges = $fight->getMetadataValue(ChargeLaw::METADATA_KEY, []) ?? [];
        if (!\is_array($charges)) {
            $charges = [];
        }

        $key = (string) $player->getId();
        if ($value <= 0) {
            unset($charges[$key]);
        } else {
            $charges[$key] = $value;
        }

        $fight->setMetadataValue(ChargeLaw::METADATA_KEY, $charges);
    }
}
