<?php

namespace App\GameEngine\Tutorial;

use App\Entity\App\Player;
use App\Entity\Game\Monster;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Le mannequin que l'acte I reclame, quand il le reclame.
 *
 * **Le defaut repare.** `TrainingFightLauncher` n'avait **aucun appelant** :
 * ni route, ni bouton, ni lien. Or les etapes 3 et 5 de l'acte I exigent de
 * battre un mannequin, et le Fanal est `safe: true` — donc `ExploreService` y
 * force `mob: 0`, et les mannequins n'appartenant a aucune zone, rien ne peut
 * les faire apparaitre. La chaine de l'acte I etait **infranchissable a sa
 * troisieme etape**, en silence : le bandeau demandait un combat que le jeu
 * n'offrait nulle part.
 *
 * L'offre est **portee par la quete**, jamais par la zone : un mannequin qui
 * peuplerait l'ecran de zone entrerait dans la liste des proies, dans le
 * bestiaire et dans les compteurs de presence, et il faudrait alors expliquer
 * partout pourquoi il n'est pas une rencontre.
 */
class TrainingDummyOffer
{
    public function __construct(
        private readonly TutorialGuide $guide,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    /**
     * Le mannequin a dresser maintenant, ou `null`.
     *
     * Rend `null` des que l'etape courante ne demande pas de mannequin : le
     * bouton n'existe donc que le temps de la lecon, et jamais pour un joueur
     * qui a fini l'acte I ou qui l'a passe.
     */
    public function pendingFor(Player $player): ?Monster
    {
        $quest = $this->guide->currentQuest($player);
        if (null === $quest) {
            return null;
        }

        // Une quete non acceptee ne compte pas ses objectifs : offrir le combat
        // avant l'acceptation ferait un combat sans effet, c'est-a-dire la meme
        // impression d'ecran fige qu'on cherche a supprimer.
        if (!$this->guide->hasAccepted($player, $quest)) {
            return null;
        }

        $slug = $quest->getRequirements()['monsters'][0]['slug'] ?? null;
        if (!\is_string($slug) || !str_starts_with($slug, 'training_dummy')) {
            return null;
        }

        $monster = $this->entityManager->getRepository(Monster::class)->findOneBy(['slug' => $slug]);

        // `isTrainingDummy()` est reverifie ici **et** dans le lanceur : c'est
        // lui qui garantit qu'aucun vrai monstre ne peut etre pose en zone sure
        // par ce chemin, et un garde-fou qui n'existe qu'a un bout d'une chaine
        // finit par etre contourne par l'autre.
        return $monster instanceof Monster && $monster->isTrainingDummy() ? $monster : null;
    }
}
