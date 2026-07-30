<?php

namespace App\GameEngine\Bestiary;

use App\Entity\App\Player;
use App\Entity\App\PlayerBestiary;
use App\GameEngine\Race\RaceCapability;
use App\GameEngine\Race\RaceCapabilityResolver;

/**
 * Ce que le bestiaire laisse lire, et pourquoi (ONB-07b).
 *
 * **Le flair de l'Orc** : element et faiblesse d'un monstre lisibles des la
 * **premiere** rencontre, sans attendre le palier de dix mises a mort.
 *
 * La regle qui le rend sur : *une capacite touche ce qu'on **sait**, jamais ce
 * qu'on **produit*** (A11). Le flair ne change ni les degats, ni les points de
 * vie, ni le butin, ni le cout d'une chasse — il avance la lecture d'une
 * information que tout le monde finit par obtenir. Le corollaire : *le passif
 * rattrape l'experience, il ne remplace pas le talent*. Un veteran qui connait
 * le bestiaire par cœur n'en tire rien, et un joueur qui lit le wiki obtient la
 * meme chose. L'ecart se referme avec les heures.
 *
 * **Le palier n'est pas supprime, il est double.** La regle reste « dix mises a
 * mort revelent les faiblesses » pour tout le monde ; le flair est un second
 * chemin vers la meme information. Retirer le palier pour les Orcs aurait fait
 * de la race une condition d'acces au contenu — ce que ce jalon existe pour
 * eviter.
 *
 * La decision vit ici et **pas dans `PlayerBestiary`** : l'entite compte des
 * mises a mort, elle n'a aucune raison de connaitre le peuple de son porteur.
 * L'y mettre aurait fait entrer la race dans le modele de donnees, d'ou elle ne
 * ressort jamais.
 */
class BestiaryRevealPolicy
{
    public function __construct(private readonly RaceCapabilityResolver $capabilities)
    {
    }

    /**
     * Les faiblesses de ce monstre sont-elles lisibles par ce joueur ?
     */
    public function weaknessesRevealed(PlayerBestiary $entry): bool
    {
        if ($entry->hasWeaknessesRevealed()) {
            return true;
        }

        return $this->capabilities->playerHas($entry->getPlayer(), RaceCapability::TheScent);
    }

    /**
     * Les monstres dont ce joueur peut lire les faiblesses, par identifiant.
     *
     * Le gabarit ne rejoue pas la regle : il consulte une liste. Repeter la
     * condition a chaque endroit qui l'affiche — ils sont deja deux — reviendrait
     * a la maintenir en plusieurs exemplaires, dont un finirait par mentir.
     *
     * @param iterable<PlayerBestiary> $entries
     *
     * @return list<int>
     */
    public function readableMonsterIds(iterable $entries): array
    {
        $readable = [];
        foreach ($entries as $entry) {
            if ($this->weaknessesRevealed($entry)) {
                $readable[] = $entry->getMonster()->getId();
            }
        }

        return $readable;
    }

    /**
     * Le joueur lit-il par le flair plutot que par l'habitude ?
     *
     * Sert a le **dire** a l'ecran : une capacite qu'on ne remarque pas n'en est
     * pas une, et un Orc qui verrait les faiblesses des la premiere rencontre
     * conclurait que le palier ne sert a rien.
     */
    public function readsByScent(?Player $player): bool
    {
        return $this->capabilities->playerHas($player, RaceCapability::TheScent);
    }
}
