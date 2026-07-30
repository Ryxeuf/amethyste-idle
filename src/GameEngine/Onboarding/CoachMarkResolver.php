<?php

namespace App\GameEngine\Onboarding;

use App\Entity\App\Player;
use App\Enum\CoachMark;
use App\GameEngine\Tutorial\TutorialManager;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Quel encart de coach un ecran doit montrer, s'il en est un (ONB-17).
 *
 * **C3 — a l'arrivee, jamais au temps ecoule.** L'encart se decide au rendu de
 * l'ecran et nulle part ailleurs. Un encart qui apparaîtrait apres N secondes de
 * lecture se lirait comme une relance : *« vous n'avez pas encore compris ? »*.
 *
 * **C1 — jamais un systeme inutilisable.** Le hub et la guilde attendent la fin
 * de l'acte I ; le marche attend une condition que l'ecran seul connaît. Un
 * encart qui expliquerait un systeme ferme enseigne une frustration, et le
 * joueur retient la porte close, pas l'explication.
 *
 * **Le coach est par personnage**, pas par compte : deux personnages du meme
 * joueur decouvrent le jeu chacun a son rythme, et le second a souvent une
 * raison d'etre — essayer autre chose.
 */
class CoachMarkResolver
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly TutorialManager $tutorialManager,
    ) {
    }

    /**
     * L'encart a montrer sur cet ecran, ou `null`.
     *
     * `$callerCondition` ne sert qu'aux encarts qui en declarent le besoin — le
     * marche aujourd'hui. Le passer ailleurs n'a aucun effet : c'est l'encart
     * qui decide s'il attend quelque chose de l'appelant, pas l'appelant qui
     * decide de se faire confiance.
     */
    public function forScreen(?Player $player, CoachMark $mark, bool $callerCondition = true): ?CoachMark
    {
        if (null === $player || $player->hasSeenCoachMark($mark)) {
            return null;
        }

        if ($mark->waitsForActOne() && !$this->tutorialManager->isCompleted($player)) {
            return null;
        }

        if ($mark->needsCallerCondition() && !$callerCondition) {
            return null;
        }

        return $mark;
    }

    /**
     * L'encart a ete lu — il ne revient jamais seul.
     *
     * Idempotent : deux fermetures rapides, un double-clic, un rechargement en
     * cours de route ne doivent pas produire deux entrees ni une erreur.
     */
    public function dismiss(Player $player, CoachMark $mark): void
    {
        if ($player->markCoachSeen($mark)) {
            $this->entityManager->flush();
        }
    }
}
