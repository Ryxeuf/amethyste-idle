<?php

namespace App\GameEngine\Tutorial;

use App\Entity\App\Player;
use App\Entity\App\PlayerQuest;
use App\Enum\TutorialStep;
use App\Repository\PlayerQuestCompletedRepository;
use Doctrine\ORM\EntityManagerInterface;

/**
 * L'etat d'onboarding, lu a un seul endroit (ONB-14).
 *
 * **Ferme la dette D7.** Ce service ecrivait `player.tutorial_step` et faisait
 * avancer un compteur que cinq abonnements alimentaient en parallele de l'arc
 * `intro`. Deux etats, aucun lien : on pouvait terminer le tutoriel sans avoir
 * touche a l'arc, abandonner l'arc en restant « en tutoriel », et « passer le
 * tutoriel » n'abandonnait rien.
 *
 * Il ne conserve plus rien. L'arc est la source ; tout se deduit du nombre de
 * ses quetes terminees, et le seul etat propre a l'onboarding est **le refus** :
 * un joueur qui a dit « passer » l'a dit une fois pour toutes.
 */
class TutorialManager
{
    public const ARC = 'intro';

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly PlayerQuestCompletedRepository $completedRepository,
    ) {
    }

    public function getCurrentStep(Player $player): ?TutorialStep
    {
        if ($player->hasSkippedOnboarding()) {
            return null;
        }

        return TutorialStep::fromCompletedSteps($this->completedSteps($player));
    }

    public function isCompleted(Player $player): bool
    {
        return null === $this->getCurrentStep($player);
    }

    public function isInTutorial(Player $player): bool
    {
        return null !== $this->getCurrentStep($player);
    }

    /**
     * Quetes de l'arc `intro` terminees par ce joueur.
     */
    public function completedSteps(Player $player): int
    {
        return $this->completedRepository->countCompletedInArc($player, self::ARC);
    }

    /**
     * Passer le tutoriel, c'est abandonner l'arc — et c'est le meme geste.
     *
     * C'etait la moitie la plus visible de D7 : le bandeau disparaissait, les
     * quetes restaient au journal, et le joueur gardait une chaine ouverte
     * qu'il venait explicitement de refuser.
     */
    public function skip(Player $player): void
    {
        if ($player->hasSkippedOnboarding()) {
            return;
        }

        $player->skipOnboarding();

        foreach ($this->activeArcQuests($player) as $playerQuest) {
            $this->entityManager->remove($playerQuest);
        }

        $this->entityManager->flush();
    }

    /**
     * @return list<PlayerQuest>
     */
    private function activeArcQuests(Player $player): array
    {
        $active = [];
        foreach ($player->getQuests() as $playerQuest) {
            if ($playerQuest instanceof PlayerQuest && self::ARC === $playerQuest->getQuest()->getStoryArc()) {
                $active[] = $playerQuest;
            }
        }

        return $active;
    }
}
