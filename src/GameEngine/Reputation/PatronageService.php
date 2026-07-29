<?php

namespace App\GameEngine\Reputation;

use App\Entity\App\Player;
use App\Entity\Game\Faction;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Les couleurs qu'on porte, et le seul moment ou on ne peut pas en changer
 * (FAC-01).
 *
 * GAME_WORLD § 6.4 c : « on porte les couleurs d'une seule faction a la fois
 * (changeable hors combat) ».
 *
 * **Le changement ne se paie pas en gils.** Le renoncement se paie deja en
 * reputation, par la tension : monter chez l'un fait descendre chez l'autre, et
 * un joueur qui alterne ses couleurs n'a jamais les deux reputations hautes.
 * Facturer en plus punirait deux fois le meme choix.
 */
class PatronageService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly FactionTensionCatalog $catalog,
        private readonly ReputationManager $reputationManager,
    ) {
    }

    /**
     * Porter les couleurs d'une faction.
     *
     * @throws PatronageException
     */
    public function choose(Player $player, Faction $faction): void
    {
        $this->assertOutOfCombat($player);

        if (!$this->isEligible($player, $faction)) {
            throw new PatronageException(PatronageException::REASON_TIER, sprintf('Patronage of "%s" requires the "%s" tier.', $faction->getSlug(), $this->catalog->patronageTier()->value));
        }

        // Le champ est unique : reaffecter suffit a retirer les couleurs
        // precedentes. C'est la forme du champ qui tient l'exclusivite, pas
        // une boucle de nettoyage qu'on pourrait oublier d'appeler.
        $player->setPatronFaction($faction);
        $this->entityManager->flush();
    }

    /**
     * N'en porter aucune.
     *
     * Le retrait existe pour lui-meme : sans lui, un joueur ne pourrait quitter
     * une faction qu'en en rejoignant une autre, et le neutre — qui est une
     * position, pas un vide — serait inatteignable apres le premier choix.
     *
     * @throws PatronageException
     */
    public function clear(Player $player): void
    {
        $this->assertOutOfCombat($player);

        $player->setPatronFaction(null);
        $this->entityManager->flush();
    }

    /**
     * Le joueur est-il assez proche de cette faction pour en porter les
     * couleurs ?
     */
    public function isEligible(Player $player, Faction $faction): bool
    {
        $playerFaction = $this->reputationManager->getPlayerFaction($player, $faction);
        if ($playerFaction === null) {
            return false;
        }

        return $playerFaction->getReputation() >= $this->catalog->patronageTier()->threshold();
    }

    /**
     * @throws PatronageException
     */
    private function assertOutOfCombat(Player $player): void
    {
        if (!$this->catalog->patronageForbiddenInCombat() || $player->getFight() === null) {
            return;
        }

        // Changer de couleurs entre deux tours ferait varier les statistiques
        // au milieu d'un geste : le joueur verrait ses points de vie maximum
        // bouger sans qu'aucun coup n'ait ete porte.
        throw new PatronageException(PatronageException::REASON_IN_COMBAT, 'Patronage cannot be changed during a fight.');
    }
}
