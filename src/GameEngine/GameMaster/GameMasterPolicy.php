<?php

namespace App\GameEngine\GameMaster;

use App\Entity\App\Player;

/**
 * Ce qu'un maitre du jeu peut, et surtout ce qu'il ne peut pas.
 *
 * Le statut MJ se decrit en une phrase : **il n'a aucun poids sur le monde**.
 * Tout le reste en decoule, et cette classe est l'endroit ou la phrase devient
 * exploitable. Sans elle, la regle se serait dispersee en autant de `if
 * ($player->isGameMaster())` que d'ecrans, et le jour ou un canal d'echange
 * s'ajoute, personne n'aurait su qu'il fallait aussi le fermer.
 *
 * Trois familles de reponses :
 *
 *  - **Ce qui est ferme** (`assertMayTrade`) : tout ce qui deplace de la valeur
 *    entre joueurs ou entre le joueur et le monde. Le MJ regarde l'hotel des
 *    ventes et les echoppes — voir n'est pas peser —, mais il n'y depose, n'y
 *    achete et n'y vend rien. Donner reste l'affaire d'un administrateur, depuis
 *    l'admin, ou la trace existe.
 *  - **Ce qui est ouvert** (`revealsHiddenInformation`, `bypassesAccessGates`) :
 *    l'information et les portes. Un MJ doit voir le filon qu'un joueur pretend
 *    vide et franchir la porte de faction dont on lui signale un defaut.
 *  - **Ce qui ne compte pas** (`countsTowardWorldActivity`, `appearsInRankings`) :
 *    ni charge mondiale, ni assiduite, ni classement, ni recompense de saison.
 *
 * Ce que le statut ne donne **pas**, et qu'il ne faut pas ajouter ici :
 * l'invulnerabilite. Un MJ meurt comme les autres et passe par le respawn —
 * animer un combat sans jamais rien risquer, c'est arbitrer depuis les gradins.
 */
class GameMasterPolicy
{
    /**
     * Refus oppose a un MJ qui tente d'engager de la valeur.
     *
     * Ecrit en clair et non en cle de traduction : les gestionnaires de commerce
     * levent deja leurs refus en francais litteral, et une cle brute s'afficherait
     * telle quelle sur la moitie de ces ecrans.
     */
    public const REASON_TRADE = 'Un maitre du jeu ne peut ni acheter, ni vendre, ni deposer : il regarde le marche, il n\'y pese pas.';

    public function isGameMaster(?Player $player): bool
    {
        return null !== $player && $player->isGameMaster();
    }

    /**
     * Le MJ peut-il engager de la valeur ? Jamais.
     *
     * Un MJ qui achete draine l'offre, un MJ qui vend l'inonde, et dans les deux
     * cas ce sont des Gils sans contrepartie de jeu qui entrent ou sortent. La
     * lecture, elle, reste ouverte partout : c'est meme l'interet — surveiller
     * un prix truque demande de voir la salle des ventes.
     */
    public function canTrade(?Player $player): bool
    {
        return !$this->isGameMaster($player);
    }

    /**
     * @throws GameMasterRestrictionException
     */
    public function assertMayTrade(?Player $player): void
    {
        if (!$this->canTrade($player)) {
            throw new GameMasterRestrictionException(self::REASON_TRADE);
        }
    }

    /**
     * Le MJ lit ce que le personnage ne saurait pas.
     *
     * `GAME_ZONE_ACTIONS` fait de l'information une mecanique : un filon se
     * repere, une bande de purete se merite, un monstre s'apprend. Ces paliers
     * decrivent ce qu'un **joueur** sait ; un MJ n'est pas la pour progresser
     * mais pour verifier, et une information qu'il ne voit pas est un litige
     * qu'il ne peut pas trancher.
     */
    public function revealsHiddenInformation(?Player $player): bool
    {
        return $this->isGameMaster($player);
    }

    /**
     * Le MJ franchit les portes : decouverte prealable, liaison desactivee,
     * zone en preparation, et les portes de faction quand elles existeront
     * (FAC-09 — les cinq portes). C'est le point d'accroche : une porte qui
     * s'ajoute doit interroger cette methode, jamais reimplementer la regle.
     */
    public function bypassesAccessGates(?Player $player): bool
    {
        return $this->isGameMaster($player);
    }

    /**
     * L'activite du MJ ne pese pas sur le monde : ni charge mondiale (FOY-17),
     * ni assiduite hebdomadaire (RET-04). Son energie etant gratuite, l'y
     * compter mesurerait une pression que personne n'a exercee.
     */
    public function countsTowardWorldActivity(?Player $player): bool
    {
        return !$this->isGameMaster($player);
    }

    /**
     * Le MJ ne figure ni aux classements ni aux podiums de saison. Un animateur
     * en tete du classement de recolte n'est pas une performance : c'est le
     * signe que la mesure ne mesure plus rien.
     */
    public function appearsInRankings(?Player $player): bool
    {
        return !$this->isGameMaster($player);
    }

    /**
     * Le personnage doit-il apparaitre dans une liste ou d'autres joueurs le
     * rencontrent (presence de zone, recherche de joueurs) ?
     *
     * Un MJ incognito reste visible **pour lui-meme et pour les autres MJ** :
     * se voir disparaitre de sa propre liste ferait douter du mode, et deux
     * animateurs sur la meme soiree doivent pouvoir se situer.
     */
    public function isVisibleTo(Player $viewed, ?Player $viewer): bool
    {
        if (!$viewed->isHiddenFromOtherPlayers()) {
            return true;
        }

        if (null === $viewer) {
            return false;
        }

        return $viewer->getId() === $viewed->getId() || $viewer->isGameMaster();
    }

    /**
     * Filtre une liste de joueurs pour le regard d'un autre.
     *
     * @param iterable<Player> $players
     *
     * @return list<Player>
     */
    public function visibleTo(iterable $players, ?Player $viewer): array
    {
        $visible = [];
        foreach ($players as $player) {
            if ($this->isVisibleTo($player, $viewer)) {
                $visible[] = $player;
            }
        }

        return $visible;
    }
}
