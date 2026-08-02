<?php

namespace App\GameEngine\Fight;

use App\Entity\App\Fight;
use App\Entity\App\Mob;
use App\Entity\App\Player;
use App\Entity\Game\Monster;
use App\GameEngine\Fight\Handler\FightHandler;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Dresser un mannequin devant le joueur, et le combattre (ONB-11).
 *
 * **Un combat scripte n'est pas un tirage de rencontre.** C'est toute la
 * raison d'etre de ce service : le Fanal est `safe: true`, ce qui force
 * `mob: 0` dans `ExploreService` — aucun combat ne peut y **survenir**. Mais
 * rien n'interdit d'en **poser** un. Le mannequin s'enseigne donc au Fanal sans
 * toucher a sa surete : « ici, rien ne mord » reste vrai, et rien ne mord
 * effectivement.
 *
 * Le mannequin est **cree a la demande et rattache au combat**, jamais a la
 * zone. S'il peuplait la zone, il apparaitrait dans la liste des proies, dans
 * le bestiaire et dans les compteurs de presence — et il faudrait alors
 * expliquer partout pourquoi il n'est pas une rencontre.
 */
class TrainingFightLauncher
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly FightHandler $fightHandler,
    ) {
    }

    /**
     * @throws \InvalidArgumentException si le monstre vise n'est pas un mannequin
     */
    public function launch(Player $player, Monster $dummy): Fight
    {
        if (!$dummy->isTrainingDummy()) {
            // Le refus est explicite : ce service contourne le tirage de
            // rencontre, et l'ouvrir a un vrai monstre reviendrait a poser un
            // combat en zone sure — exactement ce que le `safe: true` interdit.
            throw new \InvalidArgumentException(sprintf('"%s" n\'est pas un mannequin d\'entrainement.', $dummy->getSlug()));
        }

        $mob = new Mob();
        $mob->setMonster($dummy);
        // `Mob::getMaxLife()` derive du monstre : il n'y a rien a poser.
        $mob->setLife($dummy->getLife());
        $mob->setTier($dummy->getTier());
        // Aucune zone : le mannequin n'appartient a aucun lieu. Il existe le
        // temps du combat, et disparait avec lui.
        $mob->setZone(null);

        $this->entityManager->persist($mob);

        return $this->fightHandler->startFight($player, $mob);
    }
}
