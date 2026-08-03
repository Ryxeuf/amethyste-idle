<?php

namespace App\GameEngine\Fight;

use App\Entity\App\Fight;
use App\Entity\App\Player;
use App\Entity\App\PlayerItem;
use App\Entity\Game\Item;
use App\Entity\Game\Spell;

/**
 * Le carquois, et ce qu'il reste dedans (ARC-04b).
 *
 * GAME_ARCHETYPES § 9 septies, arbitrage rendu : **aucun archetype ne porte un
 * cout recurrent en gils que les autres n'ont pas**. Une premiere redaction
 * faisait de la munition un consommable achete, ce qui faisait payer a l'archer
 * 90 a 230 gils par jour pour +1,8 % de degats face a un pyromancien qui ne
 * paie rien. Le carquois devient donc une **piece d'equipement durable** : il
 * *se vide dans la rencontre et se ramasse apres*.
 *
 * D'ou la seule decision de modele qui compte ici : la consommation vit dans le
 * **combat**, jamais sur l'objet. Un carquois n'a pas d'etat entre deux
 * rencontres, ce qui rend la ressource du registre distance **intra-rencontre**
 * comme les PM, et rend impossible qu'un joueur se retrouve durablement
 * desarme faute d'avoir fait des courses.
 *
 * **Ce que le carquois ne fait pas** : porter l'element. C'est la correction du
 * § 9 quater — une premiere redaction faisait porter l'element par la munition
 * seule, si bien qu'une fleche ordinaire produisait une action **sans element**,
 * donc hors de la case du domaine, donc **sans aucun passif d'arbre** : le filet
 * de securite eteignait l'archetype. L'element vient de la materia ; la munition
 * elementaire le *remplacera* (elle reste a ecrire).
 */
class QuiverResolver
{
    /**
     * Clef de metadonnee de combat, par joueur.
     */
    private const SPENT_KEY = 'ammo_spent_%d';

    /**
     * Ce que le carquois equipe porte, ou `null` si le joueur n'en porte pas.
     */
    public function capacityOf(Player $player): ?int
    {
        foreach ($player->getInventories() as $inventory) {
            foreach ($inventory->getItems() as $playerItem) {
                if (PlayerItem::GEAR_AMMO !== $playerItem->getGear()) {
                    continue;
                }

                $capacity = $playerItem->getGenericItem()->getAmmoCapacity();
                if (null !== $capacity) {
                    return $capacity;
                }
            }
        }

        return null;
    }

    /**
     * Ce qu'il reste de munitions dans la rencontre en cours.
     *
     * Sans carquois, la reserve est nulle : rien n'est refuse pour autant — un
     * geste qui ne coute pas de munition reste jouable, et l'attaque d'arme
     * l'est toujours (regle 10).
     */
    public function remaining(Fight $fight, Player $player): int
    {
        $capacity = $this->capacityOf($player);
        if (null === $capacity) {
            return 0;
        }

        return max(0, $capacity - $this->spent($fight, $player));
    }

    /**
     * Le joueur peut-il payer ce geste ?
     *
     * Un geste qui ne coute pas de munition passe toujours : c'est ce qui
     * garantit qu'un carquois vide **n'est jamais un mur**. L'archer garde son
     * attaque d'arme et tout geste hors registre distance.
     */
    public function canAfford(Fight $fight, Player $player, Spell $spell): bool
    {
        $cost = $spell->getAmmoCost();
        if ($cost <= 0) {
            return true;
        }

        return $this->remaining($fight, $player) >= $cost;
    }

    /**
     * Consomme les munitions du geste. Retourne ce qui a ete depense.
     */
    public function consume(Fight $fight, Player $player, Spell $spell): int
    {
        $cost = $spell->getAmmoCost();
        if ($cost <= 0) {
            return 0;
        }

        $fight->setMetadataValue(sprintf(self::SPENT_KEY, $player->getId()), $this->spent($fight, $player) + $cost);

        return $cost;
    }

    /**
     * Le genre d'objet qui peut porter une capacite.
     *
     * Le carquois occupe l'emplacement `ammo`, qui existait deja et n'avait
     * aucune fonction mecanique — comme les cinq types d'outil qu'OBJ-05 a
     * rendus utiles.
     */
    public function isQuiver(Item $item): bool
    {
        return null !== $item->getAmmoCapacity();
    }

    private function spent(Fight $fight, Player $player): int
    {
        return (int) $fight->getMetadataValue(sprintf(self::SPENT_KEY, $player->getId()), 0);
    }
}
