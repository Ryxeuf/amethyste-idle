<?php

namespace App\GameEngine\Reputation;

use App\Entity\App\Player;
use App\Entity\App\Zone;
use App\Entity\Game\Faction;
use Doctrine\ORM\EntityManagerInterface;

/**
 * La porte : une zone que seule une reputation ouvre (FAC-09).
 *
 * GAME_WORLD § 12.5 : *« chaque Exalte ouvre une porte quelque part »* — cinq
 * zones `interior` cachees, une par maison. Le mecanisme vit ici plutot que
 * dans le service de voyage, parce qu'il repond a **deux** questions et que la
 * seconde n'est pas un refus :
 *
 *  1. **peut-on entrer ?** — `ZoneTravelService`, au point d'accroche que la
 *     regle du MJ avait deja nomme (`bypassesAccessGates()`) ;
 *  2. **doit-on seulement voir la liaison ?** — l'ecran de zone. Une porte
 *     visible mais refusee ne serait plus cachee : *elle dirait son existence a
 *     qui ne l'a pas gagnee*, et une recompense d'exaltation qu'on peut lire
 *     par-dessus l'epaule d'un autre a deja donne la moitie de ce qu'elle
 *     donne.
 *
 * Les deux questions ont **la meme reponse**, et c'est deliberement le meme
 * appel : les separer laisserait un jour l'une des deux derriver — le defaut
 * qu'ARC-08a nomme (*une regle recopiee derive de son original en silence*).
 *
 * **La faction se lit par slug.** Une zone est importee depuis `zones.yaml` par
 * une commande, une faction vient des fixtures : une cle etrangere ferait
 * dependre l'import du graphe de l'ordre de chargement des fixtures. Une garde
 * dont la faction n'existe pas encore reste **inerte** et jamais fermee — meme
 * doctrine que la paire de tension declaree avant que la Fonderie existe
 * (FAC-01) : *on ne ferme pas une porte au nom de quelqu'un qui n'est pas la*.
 */
class FactionGate
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly ReputationManager $reputationManager,
    ) {
    }

    /**
     * Ce joueur peut-il franchir cette porte ?
     *
     * Une zone sans garde est ouverte — la garde est opt-in, et rien de ce qui
     * etait accessible ne se ferme.
     */
    public function isOpenFor(Player $player, Zone $zone): bool
    {
        if (!$zone->isGuarded()) {
            return true;
        }

        $faction = $this->entityManager->getRepository(Faction::class)
            ->findOneBy(['slug' => $zone->getRequiredFaction()]);

        if ($faction === null) {
            // Garde inerte : la maison n'est pas semee. On ne ferme pas une
            // porte au nom de quelqu'un qui n'existe pas.
            return true;
        }

        $required = $zone->getRequiredTier();
        if ($required === null) {
            return true;
        }

        $playerFaction = $this->reputationManager->getPlayerFaction($player, $faction);

        return ($playerFaction?->getReputation() ?? 0) >= $required->threshold();
    }

    /**
     * Les zones ouvertes a ce joueur, parmi celles qu'on s'apprete a lui montrer.
     *
     * @param list<Zone> $zones
     *
     * @return list<Zone>
     */
    public function openTo(Player $player, array $zones): array
    {
        return array_values(array_filter(
            $zones,
            fn (Zone $zone): bool => $this->isOpenFor($player, $zone),
        ));
    }
}
